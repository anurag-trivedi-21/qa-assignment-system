<?php

namespace App\Filament\Resources\PendingTests\Pages;

use App\Filament\Resources\PendingTests\PendingTestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPendingTests extends ListRecords
{
    protected static string $resource = PendingTestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
