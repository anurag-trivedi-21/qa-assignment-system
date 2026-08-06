<?php

namespace App\Services;

use App\Exceptions\TesterNotClockedInException;
use App\Models\PendingTest;
use App\Models\TestResult;
use App\Models\TesterShift;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TestSubmissionService
{
    public function submit(PendingTest $pendingTest, User $tester, string $result, ?string $notes = null): TestResult
    {
        if (! $tester->isClockedIn()) {
            throw new TesterNotClockedInException;
        }

        return DB::transaction(function () use ($pendingTest, $tester, $result, $notes): TestResult {
            $testResult = TestResult::query()->create([
                'test_subject_id' => $pendingTest->test_subject_id,
                'tester_id' => $tester->id,
                'result' => $result,
                'tested_at' => now(),
                'notes' => $notes,
            ]);

            TesterShift::query()
                ->where('user_id', $tester->id)
                ->whereNull('clocked_out_at')
                ->update(['last_test_submitted_at' => now()]);

            $pendingTest->delete();

            return $testResult;
        });
    }
}
