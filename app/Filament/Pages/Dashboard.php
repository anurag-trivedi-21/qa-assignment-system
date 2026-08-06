<?php

namespace App\Filament\Pages;

use App\Services\ClockService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard as BaseDashboard;
use Livewire\Attributes\On;

class Dashboard extends BaseDashboard
{
    #[On('tester-shift-changed')]
    public function refresh(): void
    {
        // Re-render this page whenever any other component reports a shift change.
    }

    protected function getHeaderActions(): array
    {
        $user = auth()->user();

        if (! $user || ! $user->is_tester) {
            return [];
        }

        return [
            Action::make('clockIn')
                ->label('Clock In')
                ->icon('heroicon-o-arrow-right-start-on-rectangle')
                ->color('success')
                ->visible(fn (): bool => ! $user->isClockedIn())
                ->action(function () use ($user): void {
                    app(ClockService::class)->clockIn($user);

                    Notification::make()
                        ->title('Clocked in')
                        ->success()
                        ->send();

                    $this->dispatch('tester-shift-changed');
                }),
        ];
    }
}
