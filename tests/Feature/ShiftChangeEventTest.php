<?php

use App\Filament\Pages\Dashboard;
use App\Livewire\FloatingClockOutButton;
use App\Models\User;
use App\Services\ClockService;
use Livewire\Livewire;

it('dispatches a shift-changed event when a tester clocks in from the dashboard', function () {
    $tester = User::factory()->create(['is_tester' => true, 'is_enabled' => true]);
    $this->actingAs($tester);

    Livewire::test(Dashboard::class)
        ->callAction('clockIn')
        ->assertDispatched('tester-shift-changed');
});

it('dispatches a shift-changed event when a tester clocks out from the floating button', function () {
    $tester = User::factory()->create(['is_tester' => true, 'is_enabled' => true]);
    app(ClockService::class)->clockIn($tester);
    $this->actingAs($tester);

    Livewire::test(FloatingClockOutButton::class)
        ->call('clockOut')
        ->assertDispatched('tester-shift-changed');
});
