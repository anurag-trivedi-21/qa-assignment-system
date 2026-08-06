<?php

namespace App\Filament\Resources\Users\Tables;

use App\Jobs\AutoAssignTestsJob;
use App\Models\User;
use App\Services\ClockService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('username')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_tester')
                    ->boolean(),
                IconColumn::make('is_enabled')
                    ->boolean(),
                TextColumn::make('testerShifts')
                    ->label('Shift Status')
                    ->formatStateUsing(fn (User $record): string => $record->isClockedIn() ? 'Clocked In' : 'Clocked Out'),
                TextColumn::make('latestShift.clocked_in_at')
                    ->label('Clocked In At')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('latestShift.clocked_out_at')
                    ->label('Clocked Out At')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->headerActions([
                Action::make('runAssignmentNow')
                    ->label('Run Assignment Now')
                    ->icon('heroicon-o-play')
                    ->action(function (): void {
                        AutoAssignTestsJob::dispatchSync();

                        Notification::make()
                            ->title('Auto-assignment run complete')
                            ->success()
                            ->send();
                    }),
            ])
            ->recordActions([
                Action::make('clockIn')
                    ->label('Clock In')
                    ->icon('heroicon-o-arrow-right-start-on-rectangle')
                    ->visible(fn (User $record): bool => $record->is_tester && ! $record->isClockedIn())
                    ->action(function (User $record): void {
                        app(ClockService::class)->clockIn($record);
                    }),
                Action::make('clockOut')
                    ->label('Clock Out')
                    ->icon('heroicon-o-arrow-left-end-on-rectangle')
                    ->visible(fn (User $record): bool => $record->is_tester && $record->isClockedIn())
                    ->action(function (User $record): void {
                        app(ClockService::class)->clockOut($record);
                    }),
            ]);
    }
}
