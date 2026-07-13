<?php

namespace App\Http\Controllers\Concerns;

use App\Services\MoneyFormatter;

trait FormatsMoney
{
    protected function money(float $amount): string
    {
        return app(MoneyFormatter::class)->format($amount);
    }

    /** Monto en USD sin formatear (para DataTables / JSON). */
    protected function moneyUsd(float $amount): float
    {
        return round($amount, 8);
    }
}
