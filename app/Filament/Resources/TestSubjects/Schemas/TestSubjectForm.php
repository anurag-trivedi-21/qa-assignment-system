<?php

namespace App\Filament\Resources\TestSubjects\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TestSubjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('category')
                    ->required(),
                TextInput::make('test_value')
                    ->required()
                    ->numeric()
                    ->default(0.5),
            ]);
    }
}
