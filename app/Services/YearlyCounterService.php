<?php

namespace App\Services;

use App\Models\YearlyCounter;

class YearlyCounterService
{
    public function nextTripNumber(int $userId, int $anio): int
    {
        $counter = YearlyCounter::query()
            ->where('user_id', $userId)
            ->where('anio', $anio)
            ->lockForUpdate()
            ->first();

        if (! $counter) {
            YearlyCounter::query()->create([
                'user_id' => $userId,
                'anio' => $anio,
                'current_trip_number' => 1,
                'current_expense_number' => 0,
            ]);

            return 1;
        }

        $next = $counter->current_trip_number + 1;
        $counter->update(['current_trip_number' => $next]);

        return $next;
    }

    public function nextExpenseNumber(int $userId, int $anio): int
    {
        $counter = YearlyCounter::query()
            ->where('user_id', $userId)
            ->where('anio', $anio)
            ->lockForUpdate()
            ->first();

        if (! $counter) {
            YearlyCounter::query()->create([
                'user_id' => $userId,
                'anio' => $anio,
                'current_trip_number' => 0,
                'current_expense_number' => 1,
            ]);

            return 1;
        }

        $next = $counter->current_expense_number + 1;
        $counter->update(['current_expense_number' => $next]);

        return $next;
    }
}
