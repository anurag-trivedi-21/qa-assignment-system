<?php

namespace App\Filament\Resources\PendingTests\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PendingTestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('test_subject_id')
                    ->relationship('subject', 'name')
                    ->required()
                    ->searchable(),
                Select::make('tester_id')
                    ->relationship(
                        'tester',
                        'username',
                        modifyQueryUsing: fn ($query) => $query->whereHas(
                            'testerShifts',
                            fn ($shiftQuery) => $shiftQuery->whereNull('clocked_out_at'),
                        ),
                    )
                    ->helperText('Only clocked-in testers can be selected.')
                    ->searchable(),
                TextInput::make('reason')
                    ->required(),
                TextInput::make('impact_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                DateTimePicker::make('claimed_at'),
            ]);
    }
}
