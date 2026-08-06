<?php

use App\Models\User;
use App\Services\ClockService;

it('shows the floating clock out button for a clocked-in tester on a fresh page load', function () {
    $tester = User::factory()->create(['is_tester' => true, 'is_enabled' => true]);
    app(ClockService::class)->clockIn($tester);

    $this->actingAs($tester)->get('/admin')->assertSee('Clock Out');
});

it('does not show the floating clock out button for a clocked-out tester', function () {
    $tester = User::factory()->create(['is_tester' => true, 'is_enabled' => true]);

    $this->actingAs($tester)->get('/admin')->assertDontSee('Clock Out');
});
