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
            ->get();

        $eligibleTesters = $eligibleShifts
            ->filter(fn (TesterShift $shift) => $shift->user && $shift->user->is_tester)
            ->map(fn (TesterShift $shift) => $shift->user)
            ->filter(function (User $user) {
                return ! PendingTest::query()
                    ->where('tester_id', $user->id)
                    ->where('is_auto_assigned', true)
                    ->exists();
            })
            ->values();

        if ($eligibleTesters->isEmpty()) {
            return self::SUCCESS;
        }

        $pendingTests = PendingTest::query()
            ->whereNull('tester_id')
            ->where(function ($query): void {
                $query->whereNull('claimed_at')
                    ->orWhere('claimed_at', '<=', now());
            })
            ->orderByDesc(DB::raw('(impact_count * 100)'))
            ->orderByDesc('impact_count')
            ->orderByDesc('created_at')
            ->get();

        if ($pendingTests->isEmpty()) {
            return self::SUCCESS;
        }

        foreach ($eligibleTesters as $tester) {
            $test = $pendingTests->shift();

            if (! $test) {
                break;
            }

            DB::transaction(function () use ($tester, $test): void {
                $lock = DB::select('SELECT GET_LOCK(?, 10) AS lock_result', ["assign:{$tester->id}"]);
                if (($lock[0]->lock_result ?? 0) !== 1) {
                    return;
                }

                $alreadyAssigned = PendingTest::query()
                    ->where('tester_id', $tester->id)
                    ->where('is_auto_assigned', true)
                    ->exists();

                if ($alreadyAssigned) {
                    return;
                }

                PendingTest::query()
                    ->whereKey($test->getKey())
                    ->update([
                        'tester_id' => $tester->id,
                        'claimed_at' => now(),
                        'is_auto_assigned' => true,
                    ]);

                DB::select('SELECT RELEASE_LOCK(?)', ["assign:{$tester->id}"]);
            });
        }

        return self::SUCCESS;
    }
}
