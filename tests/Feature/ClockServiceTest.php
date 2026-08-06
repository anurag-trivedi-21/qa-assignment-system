<?php

use App\Models\PendingTest;
use App\Models\TestSubject;
use App\Models\User;
use App\Services\ClockService;

it('can clock a tester in and out using a dedicated shift record', function () {
    $tester = User::factory()->create([
        'is_tester' => true,
    ]);

    $service = app(ClockService::class);

    $shift = $service->clockIn($tester);

    expect($shift->user_id)->toBe($tester->id)
        ->and($shift->clocked_out_at)->toBeNull()
        ->and($tester->fresh()->isClockedIn())->toBeTrue();

    $stoppedShift = $service->clockOut($tester);

    expect($stoppedShift?->id)->toBe($shift->id)
        ->and($stoppedShift?->fresh()->clocked_out_at)->not->toBeNull()
        ->and($tester->fresh()->isClockedIn())->toBeFalse();
});

it('returns auto-assigned pending tests to the queue when a tester clocks out', function () {
    $tester = User::factory()->create(['is_tester' => true]);
    $service = app(ClockService::class);

    $service->clockIn($tester);

    $subject = TestSubject::factory()->create();
    $autoAssigned = PendingTest::factory()->create([
        'test_subject_id' => $subject->id,
        'tester_id' => $tester->id,
        'reason' => 'Regression',
        'impact_count' => 500,
        'is_auto_assigned' => true,
    ]);

    $manualClaim = PendingTest::factory()->create([
        'test_subject_id' => $subject->id,
        'tester_id' => $tester->id,
        'reason' => 'Support',
        'impact_count' => 200,
        'is_auto_assigned' => false,
    ]);

    $service->clockOut($tester);

    $autoAssigned->refresh();
    $manualClaim->refresh();

    expect($autoAssigned->tester_id)->toBeNull()
        ->and($autoAssigned->claimed_at)->toBeNull()
        ->and($autoAssigned->is_auto_assigned)->toBeFalse()
        ->and($manualClaim->tester_id)->toBe($tester->id)
        ->and($manualClaim->is_auto_assigned)->toBeFalse();
});

it('delays returning a new subject test to the queue until the grace period expires', function () {
    $tester = User::factory()->create(['is_tester' => true]);
    $service = app(ClockService::class);

    $service->clockIn($tester);

    $subject = TestSubject::factory()->create();
    $newSubject = PendingTest::factory()->create([
        'test_subject_id' => $subject->id,
        'tester_id' => $tester->id,
        'reason' => 'New Subject',
        'impact_count' => 400,
        'claimed_at' => now(),
        'is_auto_assigned' => true,
    ]);

    $service->clockOut($tester);

    $newSubject->refresh();

    expect($newSubject->tester_id)->toBe($tester->id)
        ->and($newSubject->claimed_at)->not->toBeNull()
        ->and($newSubject->return_to_queue_at)->not->toBeNull();
});
