<?php

use App\Support\UiTranslator;

if (! function_exists('ui')) {
    function ui(string $key, array $replace = [], ?string $locale = null): string
    {
        return UiTranslator::get($key, $replace, $locale);
    }
}

if (! function_exists('ui_menu')) {
    /** Etiqueta de menú/permiso por slug de app_options (fallback: label en BD). */
    function ui_menu(string $slug, ?string $fallback = null, ?string $locale = null): string
    {
        $key = 'menu.'.$slug;
        $translated = UiTranslator::get($key, [], $locale);

        return $translated !== $key ? $translated : ($fallback ?? $slug);
    }
}
