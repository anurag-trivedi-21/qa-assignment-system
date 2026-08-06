<?php

use App\Exceptions\TesterNotClockedInException;
use App\Models\PendingTest;
use App\Models\TesterShift;
use App\Models\TestResult;
use App\Models\TestSubject;
use App\Models\User;
use App\Services\TestSubmissionService;

it('records the submitted test result', function () {
    $tester = User::factory()->create(['is_tester' => true]);
    TesterShift::create([
        'user_id' => $tester->id,
        'clocked_in_at' => now()->subHour(),
        'clocked_out_at' => null,
    ]);

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
    TesterShift::create([
        'user_id' => $tester->id,
        'clocked_in_at' => now()->subHour(),
        'clocked_out_at' => null,
    ]);

    $pendingTest = PendingTest::factory()->create([
        'tester_id' => $tester->id,
        'claimed_at' => now()->subMinute(),
    ]);

    app(TestSubmissionService::class)->submit($pendingTest, $tester, 'passed');

    expect(PendingTest::query()->whereNull('tester_id')->pluck('id'))
        ->not->toContain($pendingTest->id);
});

it('rejects a submission when the tester is not clocked in', function () {
    $tester = User::factory()->create(['is_tester' => true]);

    $pendingTest = PendingTest::factory()->create([
        'tester_id' => $tester->id,
        'claimed_at' => now()->subMinute(),
    ]);

    expect(fn () => app(TestSubmissionService::class)->submit($pendingTest, $tester, 'passed'))
        ->toThrow(TesterNotClockedInException::class);
});

it('records the last submission timestamp on the tester active shift', function () {
    $tester = User::factory()->create(['is_tester' => true]);

    $shift = TesterShift::create([
        'user_id' => $tester->id,
        'clocked_in_at' => now()->subHours(2),
        'clocked_out_at' => null,
    ]);

    $pendingTest = PendingTest::factory()->create([
        'tester_id' => $tester->id,
        'claimed_at' => now()->subMinute(),
    ]);

    app(TestSubmissionService::class)->submit($pendingTest, $tester, 'passed');

    expect($shift->fresh()->last_test_submitted_at)->not->toBeNull();
});
