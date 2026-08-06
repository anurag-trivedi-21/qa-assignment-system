<?php

namespace App\Services;

use App\Models\TesterShift;
use App\Models\User;
use Illuminate\Support\Carbon;

class ClockService
{
    public function clockIn(User $user): TesterShift
    {
        $activeShift = $user->testerShifts()
            ->whereNull('clocked_out_at')
            ->latest('clocked_in_at')
            ->first();

        if ($activeShift) {
            return $activeShift;
        }

        return $user->testerShifts()->create([
            'clocked_in_at' => Carbon::now(),
            'clocked_out_at' => null,
            'last_test_submitted_at' => null,
        ]);
    }

    public function clockOut(User $user): ?TesterShift
    {
        $activeShift = $user->testerShifts()
            ->whereNull('clocked_out_at')
            ->latest('clocked_in_at')
            ->first();

        if (! $activeShift) {
            return null;
        }

        $activeShift->update([
            'clocked_out_at' => Carbon::now(),
        ]);

        return $activeShift->fresh();
    }
}
