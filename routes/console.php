<?php

use App\Console\Commands\AutoAssignTests;
use App\Console\Commands\AutoClockOutInactiveTesters;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('testers:auto-clock-out', function () {
    $this->call(AutoClockOutInactiveTesters::class);
})->purpose('Auto clock out testers whose inactivity exceeds the configured timeout');

Artisan::command('testers:auto-assign', function () {
    return $this->call(AutoAssignTests::class);
})->purpose('Auto assign queued pending tests to eligible testers');
