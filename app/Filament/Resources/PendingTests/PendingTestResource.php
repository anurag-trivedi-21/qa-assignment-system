<?php

namespace App\Filament\Resources\PendingTests;

use App\Filament\Resources\PendingTests\Pages\CreatePendingTest;
use App\Filament\Resources\PendingTests\Pages\EditPendingTest;
use App\Filament\Resources\PendingTests\Pages\ListPendingTests;
use App\Filament\Resources\PendingTests\Schemas\PendingTestForm;
use App\Filament\Resources\PendingTests\Tables\PendingTestsTable;
use App\Models\PendingTest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PendingTestResource extends Resource
{
    protected static ?string $model = PendingTest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return PendingTestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PendingTestsTable::configure($table);
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
            'index' => ListPendingTests::route('/'),
            'create' => CreatePendingTest::route('/create'),
            'edit' => EditPendingTest::route('/{record}/edit'),
        ];
    }
}
