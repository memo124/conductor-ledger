<?php

namespace App\Providers;

use App\Services\ExchangeRateService;
use App\Services\MenuService;
use App\Support\UiTranslator;
use App\Services\MoneyFormatter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer('layouts.app', function ($view) {
            $locale = Auth::check()
                ? (Auth::user()->locale_preference ?? 'es')
                : config('app.locale', 'es');

            $view->with('clUiTranslations', UiTranslator::all($locale));
            $view->with('clLocales', config('conductor-ledger.locales', []));

            if (Auth::check()) {
                $user = Auth::user();
                $exchangeRates = app(ExchangeRateService::class);
                $money = app(MoneyFormatter::class);

                $view->with('clMenu', app(MenuService::class)->menuForUser($user));
                $view->with('clMoneyConfig', [
                    'baseCurrency' => $exchangeRates->baseCurrency(),
                    'currency' => $money->userCurrency($user),
                    'locale' => $money->userLocale($user),
                    'rates' => $exchangeRates->ratesMap(),
                    'decimalPlacesMap' => $exchangeRates->decimalPlacesMap(),
                ]);
            }
        });

        if ($this->app->environment('production')) {
            if (config('mail.default') === 'resend' && ! config('services.resend.key')) {
                throw new RuntimeException('RESEND_API_KEY es obligatoria en producción.');
            }

            if (! config('conductor-ledger.encryption.master_key')) {
                throw new RuntimeException('MASTER_ENCRYPTION_KEY es obligatoria en producción.');
            }
        }
    }
}
