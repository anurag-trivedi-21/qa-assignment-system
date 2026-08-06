<?php

use App\Models\PendingTest;
use App\Models\TesterShift;
use App\Models\TestSubject;
use App\Models\User;

it('prioritizes pending tests by impact, test value, and age', function () {
    $tester = User::factory()->create(['is_tester' => true]);

    TesterShift::create([
        'user_id' => $tester->id,
        'clocked_in_at' => now()->subMinutes(20),
        'clocked_out_at' => null,
    ]);

    $highPrioritySubject = TestSubject::factory()->create([
        'test_value' => 10,
    ]);
    $lowerPrioritySubject = TestSubject::factory()->create([
        'test_value' => 5,
    ]);

    $lowerPriorityTest = PendingTest::factory()->create([
        'test_subject_id' => $lowerPrioritySubject->id,
        'tester_id' => null,
        'impact_count' => 100,
        'reason' => 'Support',
        'is_auto_assigned' => false,
    ]);

    $higherPriorityTest = PendingTest::factory()->create([
        'test_subject_id' => $highPrioritySubject->id,
        'tester_id' => null,
        'impact_count' => 500,
        'reason' => 'Regression',
        'is_auto_assigned' => false,
    ]);

    $this->artisan('testers:auto-assign')->assertSuccessful();

    $higherPriorityTest->refresh();
    $lowerPriorityTest->refresh();

    expect($higherPriorityTest->tester_id)->toBe($tester->id)
        ->and($lowerPriorityTest->tester_id)->toBeNull();
});
