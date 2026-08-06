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

it('prioritizes the tester who has been clocked in the longest', function () {
    $newerTester = User::factory()->create(['is_tester' => true]);
    $olderTester = User::factory()->create(['is_tester' => true]);

    TesterShift::create([
        'user_id' => $newerTester->id,
        'clocked_in_at' => now()->subMinutes(10),
        'clocked_out_at' => null,
    ]);

    TesterShift::create([
        'user_id' => $olderTester->id,
        'clocked_in_at' => now()->subMinutes(30),
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

    expect($pendingTest->tester_id)->toBe($olderTester->id);
});

it('does not auto-assign a second test to a tester who already has one auto-assigned', function () {
    $tester = User::factory()->create(['is_tester' => true]);

    TesterShift::create([
        'user_id' => $tester->id,
        'clocked_in_at' => now()->subMinutes(20),
        'clocked_out_at' => null,
    ]);

    $existingAutoAssigned = PendingTest::factory()->create([
        'tester_id' => $tester->id,
        'reason' => 'Regression',
        'impact_count' => 500,
        'is_auto_assigned' => true,
    ]);

    $newPendingTest = PendingTest::factory()->create([
        'tester_id' => null,
        'reason' => 'Support',
        'impact_count' => 200,
        'is_auto_assigned' => false,
    ]);

    $this->artisan('testers:auto-assign')->assertSuccessful();

    $newPendingTest->refresh();

    expect($newPendingTest->tester_id)->toBeNull();
});

it('does not overwrite a manual claim when auto-assigning', function () {
    $tester = User::factory()->create(['is_tester' => true]);

    TesterShift::create([
        'user_id' => $tester->id,
        'clocked_in_at' => now()->subMinutes(20),
        'clocked_out_at' => null,
    ]);

    PendingTest::factory()->create([
        'tester_id' => $tester->id,
        'reason' => 'Manual Claim',
        'impact_count' => 300,
        'is_auto_assigned' => false,
    ]);

    $newPendingTest = PendingTest::factory()->create([
        'tester_id' => null,
        'reason' => 'Support',
        'impact_count' => 200,
        'is_auto_assigned' => false,
    ]);

    $this->artisan('testers:auto-assign')->assertSuccessful();

    $newPendingTest->refresh();

    expect($newPendingTest->tester_id)->toBeNull();
});
