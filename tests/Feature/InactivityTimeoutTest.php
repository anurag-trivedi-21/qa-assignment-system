<?php

use App\Models\TesterShift;
use App\Models\User;

it('auto clocks out inactive testers based on the configured inactivity timeout', function () {
    $tester = User::factory()->create(['is_tester' => true]);

    $shift = TesterShift::create([
        'user_id' => $tester->id,
        'clocked_in_at' => now()->subHours(4),
        'clocked_out_at' => null,
        'last_test_submitted_at' => now()->subHours(4),
    ]);

    $this->artisan('testers:auto-clock-out')->assertSuccessful();

    $shift->refresh();

    expect($shift->clocked_out_at)->not->toBeNull()
        ->and($tester->fresh()->isClockedIn())->toBeFalse();
});
