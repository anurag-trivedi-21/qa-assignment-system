<?php

use App\Models\PendingTest;
use App\Models\TestResult;
use App\Models\TestSubject;
use App\Models\User;
use App\Services\TestSubmissionService;

it('records the submitted test result', function () {
    $tester = User::factory()->create(['is_tester' => true]);
    $subject = TestSubject::factory()->create();
    $pendingTest = PendingTest::factory()->create([
        'test_subject_id' => $subject->id,
        'tester_id' => $tester->id,
        'claimed_at' => now()->subMinute(),
    ]);

    $result = app(TestSubmissionService::class)->submit(
        $pendingTest,
        $tester,
        'failed',
        'A representative issue description.',
    );

    expect($result)
        ->toBeInstanceOf(TestResult::class)
        ->test_subject_id->toBe($subject->id)
        ->tester_id->toBe($tester->id)
        ->result->toBe('failed')
        ->notes->toBe('A representative issue description.')
        ->tested_at->not->toBeNull();
});

it('maintains a consistent queue after a submission', function () {
    $tester = User::factory()->create(['is_tester' => true]);
    $pendingTest = PendingTest::factory()->create([
        'tester_id' => $tester->id,
        'claimed_at' => now()->subMinute(),
    ]);

    app(TestSubmissionService::class)->submit($pendingTest, $tester, 'passed');

    expect(PendingTest::query()->whereNull('tester_id')->pluck('id'))
        ->not->toContain($pendingTest->id);
});
