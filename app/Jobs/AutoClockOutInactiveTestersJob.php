<?php

namespace App\Jobs;

use App\Models\PendingTest;
use App\Models\TesterShift;
use App\Services\ClockService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;

class AutoClockOutInactiveTestersJob implements ShouldQueue
{
    use Queueable;

    public function handle(ClockService $clockService): void
    {
        $timeoutHours = (int) config('testing.inactivity_timeout_hours', 3);
        $cutoff = Carbon::now()->subHours($timeoutHours);

        $shifts = TesterShift::query()
            ->whereNull('clocked_out_at')
            ->where(function ($query) use ($cutoff): void {
                $query->where(function ($inner) use ($cutoff): void {
                    $inner->whereNull('last_test_submitted_at')
                        ->where('clocked_in_at', '<=', $cutoff);
                })->orWhere(function ($inner) use ($cutoff): void {
                    $inner->whereNotNull('last_test_submitted_at')
                        ->where('last_test_submitted_at', '<=', $cutoff);
                });
            })
            ->with('user')
            ->get();

        PendingTest::query()
            ->whereNotNull('return_to_queue_at')
            ->where('return_to_queue_at', '<=', now())
            ->update([
                'tester_id' => null,
                'claimed_at' => null,
                'is_auto_assigned' => false,
                'return_to_queue_at' => null,
            ]);

        foreach ($shifts as $shift) {
            if (! $shift->user) {
                continue;
            }

            $clockService->clockOut($shift->user);
        }
    }
}
