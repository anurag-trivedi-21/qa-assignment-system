<?php

namespace App\Filament\Resources\PendingTests\Pages;

use App\Filament\Resources\PendingTests\PendingTestResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPendingTest extends EditRecord
{
    protected static string $resource = PendingTestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
