<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetUserLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = auth()->user()?->locale_preference ?? config('app.locale', 'es');
        $supported = array_keys(config('conductor-ledger.locales', ['es' => [], 'en' => []]));

        if (! in_array($locale, $supported, true)) {
            $locale = config('app.locale', 'es');
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
