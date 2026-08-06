<?php

namespace App\Console\Commands;

use App\Models\TesterShift;
use App\Services\ClockService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class AutoClockOutInactiveTesters extends Command
{
    protected $signature = 'testers:auto-clock-out';

    protected $description = 'Auto clock out testers whose inactivity exceeds the configured timeout.';

    public function handle(ClockService $clockService): int
    {
        $timeoutHours = (int) env('TESTER_INACTIVITY_HOURS', 3);
        $cutoff = Carbon::now()->subHours($timeoutHours);

        $shifts = TesterShift::query()
            ->whereNull('clocked_out_at')
            ->where(function ($query) use ($cutoff): void {
                $query->where('clocked_in_at', '<=', $cutoff)
                    ->orWhere(function ($inner) use ($cutoff): void {
                        $inner->whereNotNull('last_test_submitted_at')
                            ->where('last_test_submitted_at', '<=', $cutoff);
                    });
            })
            ->with('user')
            ->get();

        foreach ($shifts as $shift) {
            if (! $shift->user) {
                continue;
            }

            $clockService->clockOut($shift->user);
        }

        return self::SUCCESS;
    }
}
