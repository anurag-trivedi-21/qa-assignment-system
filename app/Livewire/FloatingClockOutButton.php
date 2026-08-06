<?php

namespace App\Livewire;

use App\Services\ClockService;
use Filament\Notifications\Notification;
use Livewire\Attributes\On;
use Livewire\Component;

class FloatingClockOutButton extends Component
{
    #[On('tester-shift-changed')]
    public function refresh(): void
    {
        // Re-render this component whenever any other component reports a shift change.
    }

    public function clockOut(): void
    {
        $user = auth()->user();

        if ($user) {
            app(ClockService::class)->clockOut($user);
        }

        Notification::make()
            ->title('Clocked out')
            ->success()
            ->send();

        $this->dispatch('tester-shift-changed');
    }

    public function render()
    {
        return view('livewire.floating-clock-out-button');
    }
}
