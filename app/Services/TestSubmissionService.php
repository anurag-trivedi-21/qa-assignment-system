<?php

namespace App\Services;

use App\Models\PendingTest;
use App\Models\TestResult;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TestSubmissionService
{
    public function submit(PendingTest $pendingTest, User $tester, string $result, ?string $notes = null): TestResult
    {
        return DB::transaction(function () use ($pendingTest, $tester, $result, $notes): TestResult {
            $testResult = TestResult::query()->create([
                'test_subject_id' => $pendingTest->test_subject_id,
                'tester_id' => $tester->id,
                'result' => $result,
                'tested_at' => now(),
                'notes' => $notes,
            ]);

            $pendingTest->delete();

            return $testResult;
        });
    }
}
