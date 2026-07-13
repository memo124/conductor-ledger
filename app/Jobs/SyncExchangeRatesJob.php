<?php

namespace App\Jobs;

use App\Services\ExchangeRateService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncExchangeRatesJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function handle(ExchangeRateService $exchangeRates): void
    {
        $result = $exchangeRates->sync();

        if ($result->status === 'failed') {
            Log::channel('security')->error('exchange_rates.job_failed', [
                'error' => $result->error_message,
            ]);

            throw new \RuntimeException($result->error_message ?? 'Fallo al sincronizar tipos de cambio.');
        }
    }
}
