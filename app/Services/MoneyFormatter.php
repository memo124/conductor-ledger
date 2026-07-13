<?php

namespace App\Services;

use App\Models\User;
use NumberFormatter;

class MoneyFormatter
{
    public function __construct(private readonly ExchangeRateService $exchangeRates) {}

    public function format(float $amountUsd, ?User $user = null): string
    {
        $user = $user ?? auth()->user();
        $currency = strtoupper($user?->currency_preference ?? config('conductor-ledger.currency.default', 'USD'));
        $converted = $this->exchangeRates->convertFromBase($amountUsd, $currency);

        return $this->formatInCurrency($converted, $currency, $user);
    }

    public function formatInCurrency(float $amount, string $currency, ?User $user = null): string
    {
        $currency = strtoupper($currency);
        $user = $user ?? auth()->user();
        $locale = $this->resolveLocale($user?->locale_preference ?? config('app.locale', 'es'));
        $digits = $this->decimalPlaces($currency);

        if (class_exists(NumberFormatter::class)) {
            $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
            $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $digits);
            $formatted = $formatter->formatCurrency($amount, $currency);

            if ($formatted !== false) {
                return $formatted;
            }
        }

        return $currency.' '.number_format($amount, $digits);
    }

    private function decimalPlaces(string $currencyCode): int
    {
        static $map = null;

        if ($map === null) {
            $map = $this->exchangeRates->decimalPlacesMap();
        }

        return $map[$currencyCode] ?? 2;
    }

    public function formatPlain(float $amountUsd, ?User $user = null): string
    {
        $user = $user ?? auth()->user();
        $currency = strtoupper($user?->currency_preference ?? config('conductor-ledger.currency.default', 'USD'));
        $converted = $this->exchangeRates->convertFromBase($amountUsd, $currency);

        return number_format($converted, 2);
    }

    public function userCurrency(?User $user = null): string
    {
        $user = $user ?? auth()->user();

        return strtoupper($user?->currency_preference ?? config('conductor-ledger.currency.default', 'USD'));
    }

    public function userLocale(?User $user = null): string
    {
        $user = $user ?? auth()->user();

        return $this->resolveLocale($user?->locale_preference ?? config('app.locale', 'es'));
    }

    private function resolveLocale(string $locale): string
    {
        $supported = config('conductor-ledger.locales', []);

        if (isset($supported[$locale]['intl'])) {
            return $supported[$locale]['intl'];
        }

        return match ($locale) {
            'en' => 'en_US',
            default => 'es_SV',
        };
    }
}
