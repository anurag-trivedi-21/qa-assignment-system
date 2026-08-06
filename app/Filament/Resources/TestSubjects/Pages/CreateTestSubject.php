<?php

namespace App\Filament\Resources\TestSubjects\Pages;

use App\Filament\Resources\TestSubjects\TestSubjectResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTestSubject extends CreateRecord
{
    protected static string $resource = TestSubjectResource::class;
}
