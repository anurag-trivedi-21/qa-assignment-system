<?php

namespace App\Filament\Resources\TestResults;

use App\Filament\Resources\TestResults\Pages\CreateTestResult;
use App\Filament\Resources\TestResults\Pages\EditTestResult;
use App\Filament\Resources\TestResults\Pages\ListTestResults;
use App\Filament\Resources\TestResults\Schemas\TestResultForm;
use App\Filament\Resources\TestResults\Tables\TestResultsTable;
use App\Models\TestResult;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TestResultResource extends Resource
{
    protected static ?string $model = TestResult::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return TestResultForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TestResultsTable::configure($table);
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
            'index' => ListTestResults::route('/'),
            'create' => CreateTestResult::route('/create'),
            'edit' => EditTestResult::route('/{record}/edit'),
        ];
    }
}
