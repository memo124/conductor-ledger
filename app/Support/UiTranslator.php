<?php

namespace App\Support;

use Illuminate\Support\Arr;

class UiTranslator
{
    private static array $cache = [];

    public static function locale(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $supported = array_keys(config('conductor-ledger.locales', []));

        return in_array($locale, $supported, true) ? $locale : config('app.locale', 'es');
    }

    public static function all(?string $locale = null): array
    {
        $locale = self::locale($locale);

        if (! isset(self::$cache[$locale])) {
            $path = resource_path("lang/ui/{$locale}.json");

            if (! is_file($path)) {
                self::$cache[$locale] = [];
            } else {
                self::$cache[$locale] = json_decode((string) file_get_contents($path), true) ?: [];
            }
        }

        return self::$cache[$locale];
    }

    public static function get(string $key, array $replace = [], ?string $locale = null): string
    {
        $value = Arr::get(self::all($locale), $key, $key);

        if (! is_string($value)) {
            return $key;
        }

        foreach ($replace as $search => $replacement) {
            $value = str_replace(':'.$search, (string) $replacement, $value);
        }

        return $value;
    }
}
