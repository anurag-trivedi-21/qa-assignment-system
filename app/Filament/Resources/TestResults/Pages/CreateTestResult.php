<?php

namespace App\Filament\Resources\TestResults\Pages;

use App\Filament\Resources\TestResults\TestResultResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTestResult extends CreateRecord
{
    protected static string $resource = TestResultResource::class;
}
