<?php

namespace App\Console\Commands;

use App\Services\ExchangeRateService;
use Illuminate\Console\Command;

class SyncExchangeRatesCommand extends Command
{
    protected $signature = 'exchange-rates:sync {--force : Forzar llamada a la API aunque existan datos recientes}';

    protected $description = 'Sincroniza tipos de cambio desde CurrencyFreaks y los guarda en la base de datos';

    public function handle(ExchangeRateService $exchangeRates): int
    {
        $force = (bool) $this->option('force');

        if (! $force && ! $exchangeRates->shouldSync()) {
            $this->info('Tipos de cambio ya están actualizados. Use --force para volver a consultar la API.');

            return self::SUCCESS;
        }

        $result = $exchangeRates->sync($force);

        if ($result->status === 'success') {
            $this->info("Sincronización exitosa: {$result->currencies_count} monedas (1 llamada API).");

            return self::SUCCESS;
        }

        if ($result->status === 'skipped') {
            $this->info($result->error_message ?? 'Sincronización omitida.');

            return self::SUCCESS;
        }

        $this->error($result->error_message ?? 'No se pudo sincronizar tipos de cambio.');

        return self::FAILURE;
    }
}
