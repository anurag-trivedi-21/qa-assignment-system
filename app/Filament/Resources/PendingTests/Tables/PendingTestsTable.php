<?php

namespace App\Filament\Resources\PendingTests\Tables;

use App\Exceptions\TesterNotClockedInException;
use App\Models\PendingTest;
use App\Models\User;
use App\Services\TestSubmissionService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PendingTestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('subject.name')
                    ->label('Subject')
                    ->searchable(),
                TextColumn::make('subject.category')
                    ->label('Category')
                    ->toggleable(),
                TextColumn::make('tester.username')
                    ->label('Tester')
                    ->searchable(),
                TextColumn::make('reason')
                    ->searchable(),
                TextColumn::make('impact_count')
                    ->label('Impact')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('subject.test_value')
                    ->label('Test Value')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('claimed_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('submitTest')
                    ->label('Submit Test Result')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('success')
                    ->form([
                        Select::make('tester_id')
                            ->label('Tester')
                            ->options(fn (): array => User::query()
                                ->where('is_tester', true)
                                ->where('is_enabled', true)
                                ->whereHas('testerShifts', fn ($query) => $query->whereNull('clocked_out_at'))
                                ->orderBy('username')
                                ->pluck('username', 'id')
                                ->all())
                            ->helperText('Only clocked-in testers can be selected.')
                            ->default(fn (PendingTest $record): ?int => $record->tester_id)
                            ->searchable()
                            ->required(),
                        Select::make('result')
                            ->options([
                                'passed' => 'Passed',
                                'failed' => 'Failed',
                            ])
                            ->required(),
                        Textarea::make('notes')
                            ->columnSpanFull(),
                    ])
                    ->action(function (PendingTest $record, array $data): void {
                        try {
                            app(TestSubmissionService::class)->submit(
                                $record,
                                User::query()->findOrFail($data['tester_id']),
                                $data['result'],
                                $data['notes'] ?? null,
                            );
                        } catch (TesterNotClockedInException $exception) {
                            Notification::make()
                                ->title('Unable to submit')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
