<?php

use App\Models\PendingTest;
use App\Models\TesterShift;
use App\Models\TestSubject;
use App\Models\User;

it('assigns a pending test to an eligible tester', function () {
    $tester = User::factory()->create(['is_tester' => true]);

    TesterShift::create([
        'user_id' => $tester->id,
        'clocked_in_at' => now()->subMinutes(20),
        'clocked_out_at' => null,
    ]);

    $subject = TestSubject::factory()->create();
    $pendingTest = PendingTest::factory()->create([
        'test_subject_id' => $subject->id,
        'tester_id' => null,
        'reason' => 'Regression',
        'impact_count' => 500,
        'is_auto_assigned' => false,
    ]);

    $this->artisan('testers:auto-assign')->assertSuccessful();

    $pendingTest->refresh();

    expect($pendingTest->tester_id)->toBe($tester->id);
});
