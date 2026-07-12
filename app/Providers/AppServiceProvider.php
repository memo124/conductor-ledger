<?php

namespace App\Providers;

use App\Services\MenuService;
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
            if (Auth::check()) {
                $view->with('clMenu', app(MenuService::class)->menuForUser(Auth::user()));
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
