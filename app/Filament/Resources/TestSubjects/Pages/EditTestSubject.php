<?php

namespace App\Filament\Resources\TestSubjects\Pages;

use App\Filament\Resources\TestSubjects\TestSubjectResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTestSubject extends EditRecord
{
    protected static string $resource = TestSubjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
