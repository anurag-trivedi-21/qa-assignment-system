<?php

namespace App\Filament\Resources\TestResults\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TestResultForm
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
                    ->relationship('tester', 'username')
                    ->searchable()
                    ->required(),
                TextInput::make('result')
                    ->required(),
                DateTimePicker::make('tested_at')
                    ->required(),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
