<?php

namespace App\Support;

final class PlatformPath
{
    public static function normalize(string $path): string
    {
        $normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

        $directory = dirname($normalized);
        $resolvedDirectory = realpath($directory);

        if ($resolvedDirectory !== false) {
            return $resolvedDirectory.DIRECTORY_SEPARATOR.basename($normalized);
        }

        return $normalized;
    }

    public static function join(string ...$segments): string
    {
        $parts = [];

        foreach ($segments as $segment) {
            if ($segment === '') {
                continue;
            }

            $parts[] = trim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $segment), DIRECTORY_SEPARATOR);
        }

        return self::normalize(implode(DIRECTORY_SEPARATOR, $parts));
    }
}
