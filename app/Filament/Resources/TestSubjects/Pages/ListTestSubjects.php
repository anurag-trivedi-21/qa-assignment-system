<?php

namespace App\Filament\Resources\TestSubjects\Pages;

use App\Filament\Resources\TestSubjects\TestSubjectResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTestSubjects extends ListRecords
{
    protected static string $resource = TestSubjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
