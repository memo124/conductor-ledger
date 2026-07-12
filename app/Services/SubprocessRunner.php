<?php

namespace App\Services;

use RuntimeException;

final class SubprocessResult
{
    public function __construct(
        public readonly int $exitCode,
        public readonly string $stdout,
        public readonly string $stderr,
    ) {}

    public function successful(): bool
    {
        return $this->exitCode === 0;
    }
}

class SubprocessRunner
{
    /**
     * @param  list<string>  $command
     */
    public function run(array $command, ?string $cwd = null, array $extraEnv = [], int $timeout = 300): SubprocessResult
    {
        if (! function_exists('proc_open')) {
            throw new RuntimeException('proc_open no está disponible en este entorno PHP.');
        }

        $env = PHP_OS_FAMILY === 'Windows'
            ? $this->minimalWindowsEnv($extraEnv)
            : array_merge($this->unixEnv(), $extraEnv);

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = @proc_open($command, $descriptors, $pipes, $cwd, $env);

        if (! is_resource($process)) {
            throw new RuntimeException('No se pudo iniciar el proceso externo.');
        }

        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $stdout = '';
        $stderr = '';
        $start = time();

        while (true) {
            $stdout .= (string) stream_get_contents($pipes[1]);
            $stderr .= (string) stream_get_contents($pipes[2]);

            $status = proc_get_status($process);

            if (! $status['running']) {
                $stdout .= (string) stream_get_contents($pipes[1]);
                $stderr .= (string) stream_get_contents($pipes[2]);
                break;
            }

            if ((time() - $start) > $timeout) {
                proc_terminate($process);
                fclose($pipes[1]);
                fclose($pipes[2]);
                proc_close($process);
                throw new RuntimeException('El proceso externo excedió el tiempo límite.');
            }

            usleep(50_000);
        }

        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        return new SubprocessResult($exitCode, $stdout, $stderr);
    }

    /**
     * Entorno mínimo para Windows: evita bloques de entorno enormes que cuelgan proc_open.
     *
     * @param  array<string, string>  $extra
     * @return array<string, string>
     */
    private function minimalWindowsEnv(array $extra): array
    {
        $pgBin = $extra['PG_BIN'] ?? null;
        unset($extra['PG_BIN']);

        $systemRoot = (string) (getenv('SystemRoot') ?: 'C:\\Windows');
        $path = array_filter([
            $pgBin,
            $systemRoot.'\\System32',
            $systemRoot,
            $pgBin,
        ]);

        return array_merge([
            'SystemRoot' => $systemRoot,
            'ComSpec' => (string) (getenv('ComSpec') ?: $systemRoot.'\\System32\\cmd.exe'),
            'PATH' => implode(';', $path),
            'TEMP' => (string) (getenv('TEMP') ?: getenv('TMP') ?: $systemRoot.'\\Temp'),
            'TMP' => (string) (getenv('TMP') ?: getenv('TEMP') ?: $systemRoot.'\\Temp'),
        ], $extra);
    }

    /**
     * @return array<string, string>
     */
    private function unixEnv(): array
    {
        $env = [];

        foreach (array_merge($_ENV, $_SERVER) as $key => $value) {
            if (is_string($key) && is_scalar($value)) {
                $env[$key] = (string) $value;
            }
        }

        return $env;
    }
}
