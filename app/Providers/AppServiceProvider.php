<?php

namespace App\Providers;

use App\Console\Commands\AutoAssignTests;
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
            $this->app->make(Schedule::class)->command('testers:auto-assign')->everyFifteenMinutes();
        });

        $this->commands([
            AutoAssignTests::class,
        ]);
    }
}
