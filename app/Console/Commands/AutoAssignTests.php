<?php

namespace App\Console\Commands;

use App\Models\PendingTest;
use App\Models\TesterShift;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AutoAssignTests extends Command
{
    protected $signature = 'testers:auto-assign';

    protected $description = 'Automatically assign pending tests to eligible testers using the global scheduled approach.';

    public function handle(): int
    {
        $eligibleShifts = TesterShift::query()
            ->whereNull('clocked_out_at')
            ->where('clocked_in_at', '<=', Carbon::now()->subMinutes(15))
            ->with('user')
            ->orderBy('clocked_in_at')
            ->get();

        $eligibleTesters = $eligibleShifts
            ->filter(fn (TesterShift $shift) => $shift->user && $shift->user->is_tester && $shift->user->is_enabled)
            ->map(fn (TesterShift $shift) => $shift->user)
            ->filter(function (User $user) {
                return ! PendingTest::query()
                    ->where('tester_id', $user->id)
                    ->exists();
            })
            ->values();

        if ($eligibleTesters->isEmpty()) {
            return self::SUCCESS;
        }

        $cooldownDays = (int) config('testing.repeat_test_cooldown_days', 7);
        $cooldownCutoff = now()->subDays($cooldownDays);

        $pendingTests = PendingTest::query()
            ->whereNull('tester_id')
            ->where(function ($query): void {
                $query->whereNull('claimed_at')
                    ->orWhere('claimed_at', '<=', now());
            })
            ->join('test_subjects', 'test_subjects.id', '=', 'pending_tests.test_subject_id')
            ->select('pending_tests.*', 'test_subjects.test_value')
            ->orderByDesc(DB::raw('pending_tests.impact_count * test_subjects.test_value'))
            ->orderByDesc('pending_tests.impact_count')
            ->orderBy('pending_tests.created_at')
            ->get();

        if ($pendingTests->isEmpty()) {
            return self::SUCCESS;
        }

        // If cooldown would block every possible tester/test pairing, ignore it for this run
        // rather than leave every tester without work (per the "no eligible alternative" fallback).
        $enforceCooldown = false;

        foreach ($eligibleTesters as $tester) {
            foreach ($pendingTests as $candidate) {
                if (! $this->isCooldownBlocked($candidate, $tester, $cooldownCutoff)) {
                    $enforceCooldown = true;
                    break 2;
                }
            }
        }

        foreach ($eligibleTesters as $tester) {
            foreach ($pendingTests as $candidate) {
                if ($enforceCooldown && $this->isCooldownBlocked($candidate, $tester, $cooldownCutoff)) {
                    continue;
                }

                $assigned = $this->tryAssign($tester, $candidate);

                if ($assigned) {
                    $pendingTests = $pendingTests
                        ->reject(fn (PendingTest $remaining) => $remaining->getKey() === $candidate->getKey())
                        ->values();

                    break;
                }
            }
        }

        return self::SUCCESS;
    }

    private function isCooldownBlocked(PendingTest $test, User $tester, Carbon $cutoff): bool
    {
        return DB::table('test_results')
            ->where('test_subject_id', $test->test_subject_id)
            ->where('tester_id', $tester->id)
            ->where('tested_at', '>=', $cutoff)
            ->exists();
    }

    /**
     * Atomically claim a pending test for a tester, re-checking eligibility under a lock so
     * two concurrent runs of this command can never assign the same test to different testers.
     */
    private function tryAssign(User $tester, PendingTest $test): bool
    {
        return DB::transaction(function () use ($tester, $test): bool {
            $stillUnclaimed = PendingTest::query()
                ->whereKey($test->getKey())
                ->whereNull('tester_id')
                ->lockForUpdate()
                ->exists();

            if (! $stillUnclaimed) {
                return false;
            }

            $alreadyAssigned = PendingTest::query()
                ->where('tester_id', $tester->id)
                ->lockForUpdate()
                ->exists();

            if ($alreadyAssigned) {
                return false;
            }

            $updated = PendingTest::query()
                ->whereKey($test->getKey())
                ->whereNull('tester_id')
                ->update([
                    'tester_id' => $tester->id,
                    'claimed_at' => now(),
                    'is_auto_assigned' => true,
                ]);

            return $updated > 0;
        });
    }
}
