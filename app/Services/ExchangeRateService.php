<?php

namespace App\Services;

use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\ExchangeRateSync;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ExchangeRateService
{
    public function __construct(private readonly CurrencyFreaksClient $client) {}

    public function baseCurrency(): string
    {
        return config('conductor-ledger.currency.base', 'USD');
    }

    public function shouldSync(): bool
    {
        $latest = ExchangeRateSync::query()
            ->where('status', 'success')
            ->latest('id')
            ->first();

        if (! $latest) {
            return true;
        }

        $minHours = (int) config('conductor-ledger.currency.sync_min_hours', 20);

        return $latest->created_at->lte(now()->subHours($minHours));
    }

    public function sync(bool $force = false): ExchangeRateSync
    {
        if (! $force && ! $this->shouldSync()) {
            return ExchangeRateSync::query()->create([
                'status' => 'skipped',
                'currencies_count' => 0,
                'api_calls_used' => 0,
                'error_message' => 'Sincronización omitida: datos recientes en caché.',
            ]);
        }

        try {
            $payload = $this->client->fetchLatestRates();
            $base = strtoupper((string) $payload['base']);
            $sourceDate = $this->parseSourceDate($payload['date']);
            $activeCodes = Currency::query()
                ->where('is_active', true)
                ->pluck('code')
                ->map(fn (string $code) => strtoupper($code));

            $updated = 0;
            $fetchedAt = now();

            DB::transaction(function () use ($payload, $base, $sourceDate, $activeCodes, $fetchedAt, &$updated) {
                foreach ($activeCodes as $code) {
                    if ($code === $base) {
                        $rate = '1';
                    } elseif (! isset($payload['rates'][$code])) {
                        continue;
                    } else {
                        $rate = (string) $payload['rates'][$code];
                    }

                    ExchangeRate::query()->updateOrCreate(
                        [
                            'base_currency' => $base,
                            'target_currency' => $code,
                        ],
                        [
                            'rate' => $rate,
                            'source_date' => $sourceDate,
                            'fetched_at' => $fetchedAt,
                        ]
                    );

                    $updated++;
                }
            });

            $this->forgetRatesCache();

            return ExchangeRateSync::query()->create([
                'status' => 'success',
                'currencies_count' => $updated,
                'api_calls_used' => 1,
                'source_date' => $sourceDate,
            ]);
        } catch (Throwable $exception) {
            Log::channel('security')->error('exchange_rates.sync_failed', [
                'error' => $exception->getMessage(),
            ]);

            return ExchangeRateSync::query()->create([
                'status' => 'failed',
                'currencies_count' => 0,
                'api_calls_used' => 1,
                'error_message' => $exception->getMessage(),
            ]);
        }
    }

    public function ratesMap(): array
    {
        return Cache::remember($this->cacheKey(), now()->addDay(), function () {
            $base = $this->baseCurrency();
            $rates = ExchangeRate::query()
                ->where('base_currency', $base)
                ->pluck('rate', 'target_currency')
                ->map(fn ($rate) => (float) $rate)
                ->all();

            $rates[$base] = 1.0;

            return $rates;
        });
    }

    public function activeCurrencies(?string $type = null): Collection
    {
        return Currency::query()
            ->where('is_active', true)
            ->when($type, fn ($query) => $query->where('currency_type', $type))
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get();
    }

    public function decimalPlacesMap(): array
    {
        return Currency::query()
            ->where('is_active', true)
            ->pluck('decimal_places', 'code')
            ->map(fn ($places) => (int) $places)
            ->all();
    }

    public function convertFromBase(float $amount, string $targetCurrency): float
    {
        return $this->convert($amount, $this->baseCurrency(), $targetCurrency);
    }

    public function hasRate(string $currency): bool
    {
        $currency = strtoupper($currency);

        return array_key_exists($currency, $this->ratesMap());
    }

    public function convert(float $amount, string $from, string $to): float
    {
        $from = strtoupper($from);
        $to = strtoupper($to);

        if ($from === $to) {
            return $amount;
        }

        $rates = $this->ratesMap();
        $base = $this->baseCurrency();

        if (! isset($rates[$from], $rates[$to])) {
            return $amount;
        }

        $usd = $from === $base ? $amount : $amount / $rates[$from];
        $result = $to === $base ? $usd : $usd * $rates[$to];
        $decimals = $this->decimalPlacesMap()[$to] ?? 2;

        return round($result, $decimals);
    }

    public function crossRate(string $from, string $to): float
    {
        return $this->convert(1.0, $from, $to);
    }

    /**
     * @return list<array{from: string, to: string, rate: float, inverse: float}>
     */
    public function ratePairs(string $category): array
    {
        $fiat = $this->activeCurrencies('fiat')->pluck('code')->map(fn (string $c) => strtoupper($c))->all();
        $crypto = $this->activeCurrencies('crypto')->pluck('code')->map(fn (string $c) => strtoupper($c))->all();

        $pairs = match ($category) {
            'crypto_crypto' => $this->buildPairs($crypto, $crypto),
            'cross' => array_merge(
                $this->buildPairs($fiat, $crypto),
                $this->buildPairs($crypto, $fiat),
            ),
            default => $this->buildPairs($fiat, $fiat),
        };

        return $pairs;
    }

    /**
     * @param  list<string>  $fromList
     * @param  list<string>  $toList
     * @return list<array{from: string, to: string, rate: float, inverse: float}>
     */
    private function buildPairs(array $fromList, array $toList): array
    {
        $pairs = [];

        foreach ($fromList as $from) {
            foreach ($toList as $to) {
                if ($from === $to || ! $this->hasRate($from) || ! $this->hasRate($to)) {
                    continue;
                }

                $rate = $this->crossRate($from, $to);
                $pairs[] = [
                    'from' => $from,
                    'to' => $to,
                    'rate' => $rate,
                    'inverse' => $this->crossRate($to, $from),
                ];
            }
        }

        usort($pairs, fn (array $a, array $b) => [$a['from'], $a['to']] <=> [$b['from'], $b['to']]);

        return $pairs;
    }

    public function lastSuccessfulSync(): ?ExchangeRateSync
    {
        return ExchangeRateSync::query()
            ->where('status', 'success')
            ->latest('id')
            ->first();
    }

    private function cacheKey(): string
    {
        return 'cl_exchange_rates_'.$this->baseCurrency();
    }

    private function forgetRatesCache(): void
    {
        Cache::forget($this->cacheKey());
    }

    private function parseSourceDate(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }
}
