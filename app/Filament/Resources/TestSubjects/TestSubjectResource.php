<?php

namespace App\Filament\Resources\TestSubjects;

use App\Filament\Resources\TestSubjects\Pages\CreateTestSubject;
use App\Filament\Resources\TestSubjects\Pages\EditTestSubject;
use App\Filament\Resources\TestSubjects\Pages\ListTestSubjects;
use App\Filament\Resources\TestSubjects\Schemas\TestSubjectForm;
use App\Filament\Resources\TestSubjects\Tables\TestSubjectsTable;
use App\Models\TestSubject;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TestSubjectResource extends Resource
{
    protected static ?string $model = TestSubject::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return TestSubjectForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TestSubjectsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTestSubjects::route('/'),
            'create' => CreateTestSubject::route('/create'),
            'edit' => EditTestSubject::route('/{record}/edit'),
        ];
    }
}
