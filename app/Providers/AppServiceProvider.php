<?php

namespace App\Providers;

use App\Jobs\AutoAssignTestsJob;
use App\Jobs\AutoClockOutInactiveTestersJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            $schedule->job(new AutoAssignTestsJob)->everyFifteenMinutes();
            $schedule->job(new AutoClockOutInactiveTestersJob)->everyFiveMinutes();
        });
    }
}
