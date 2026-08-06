<?php

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
