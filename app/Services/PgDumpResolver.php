<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use RuntimeException;

class PgDumpResolver
{
    public function resolve(): string
    {
        $configured = trim((string) config('conductor-ledger.backup.pg_dump_binary', 'pg_dump'));

        if ($resolved = $this->resolvePath($configured)) {
            return $resolved;
        }

        foreach ($this->candidatePaths() as $candidate) {
            if ($resolved = $this->resolvePath($candidate)) {
                return $resolved;
            }
        }

        throw new RuntimeException(
            'No se encontró pg_dump. Instale las herramientas de PostgreSQL o configure PG_DUMP_BINARY en .env '
            .'(ej. C:\\Program Files\\PostgreSQL\\16\\bin\\pg_dump.exe).'
        );
    }

    /**
     * @return list<string>
     */
    private function candidatePaths(): array
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $matches = glob('C:\\Program Files\\PostgreSQL\\*\\bin\\pg_dump.exe') ?: [];
            rsort($matches);

            return array_merge($matches, [
                'C:\\PostgreSQL\\bin\\pg_dump.exe',
                'C:\\xampp\\postgresql\\bin\\pg_dump.exe',
            ]);
        }

        return [
            '/usr/bin/pg_dump',
            '/usr/local/bin/pg_dump',
            '/usr/lib/postgresql/*/bin/pg_dump',
        ];
    }

    private function resolvePath(string $path): ?string
    {
        if ($path === '') {
            return null;
        }

        if (str_contains($path, '*')) {
            $matches = glob($path) ?: [];
            rsort($matches);

            foreach ($matches as $match) {
                if (is_file($match)) {
                    return $match;
                }
            }

            return null;
        }

        if (str_contains($path, '/') || str_contains($path, '\\')) {
            return is_file($path) ? $path : null;
        }

        $finder = PHP_OS_FAMILY === 'Windows'
            ? Process::run(['where', $path])
            : Process::run(['which', $path]);

        if (! $finder->successful()) {
            return null;
        }

        $resolved = trim(strtok($finder->output(), PHP_EOL));

        return $resolved !== '' && is_file($resolved) ? $resolved : null;
    }
}
