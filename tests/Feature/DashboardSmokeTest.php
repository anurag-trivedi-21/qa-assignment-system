<?php

use App\Models\User;

it('renders the dashboard for a clocked-out tester without errors', function () {
    $tester = User::factory()->create(['is_tester' => true, 'is_enabled' => true]);

    $this->actingAs($tester)->get('/admin')->assertOk();
});

it('renders the dashboard for a clocked-in tester without errors', function () {
    $tester = User::factory()->create(['is_tester' => true, 'is_enabled' => true]);

    app(App\Services\ClockService::class)->clockIn($tester);

    $this->actingAs($tester)->get('/admin')->assertOk();
});

it('renders the dashboard for a manager without errors', function () {
    $manager = User::factory()->create(['is_tester' => false, 'is_enabled' => true]);

    $this->actingAs($manager)->get('/admin')->assertOk();
});
