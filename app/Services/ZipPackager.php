<?php

namespace App\Services;

use App\Support\PlatformPath;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use ZipArchive;

class ZipPackager
{
    public function createFromString(string $zipPath, string $content, string $entryName): void
    {
        $zipPath = PlatformPath::normalize($zipPath);
        $directory = dirname($zipPath);

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException('No se pudo crear la carpeta del respaldo ZIP.');
        }

        if (is_file($zipPath)) {
            unlink($zipPath);
        }

        if (class_exists(ZipArchive::class)) {
            $this->createWithZipArchiveFromString($zipPath, $content, $entryName);

            return;
        }

        if (PHP_OS_FAMILY === 'Windows') {
            $this->createWithPowerShellFromString($zipPath, $content, $entryName);

            return;
        }

        if ($this->commandExists('zip')) {
            $this->createWithZipCliFromString($zipPath, $content, $entryName);

            return;
        }

        throw new RuntimeException(
            'No se pudo empaquetar el respaldo en ZIP. Habilite la extensión PHP zip (extension=zip en php.ini) '
            .'o instale el paquete zip del sistema (p. ej. apt install zip en Linux).'
        );
    }

    private function createWithZipArchiveFromString(string $zipPath, string $content, string $entryName): void
    {
        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('No se pudo crear el archivo ZIP del respaldo.');
        }

        $zip->addFromString($entryName, $content);
        $zip->close();
    }

    private function createWithPowerShellFromString(string $zipPath, string $content, string $entryName): void
    {
        $tempSql = PlatformPath::join(sys_get_temp_dir(), 'cl_backup_'.uniqid('', true).'.sql');
        if (file_put_contents($tempSql, $content) === false) {
            throw new RuntimeException('No se pudo preparar el archivo temporal para ZIP.');
        }

        try {
            $script = sprintf(
                "Compress-Archive -LiteralPath '%s' -DestinationPath '%s' -Force",
                str_replace("'", "''", $tempSql),
                str_replace("'", "''", $zipPath),
            );

            $result = Process::timeout(120)->run([
                'powershell',
                '-NoProfile',
                '-NonInteractive',
                '-Command',
                $script,
            ]);

            if (! $result->successful() || ! is_file($zipPath)) {
                $details = trim($result->errorOutput()."\n".$result->output());
                throw new RuntimeException(
                    $details !== '' ? $details : 'PowerShell no pudo crear el archivo ZIP.'
                );
            }
        } finally {
            @unlink($tempSql);
        }
    }

    private function createWithZipCliFromString(string $zipPath, string $content, string $entryName): void
    {
        $tempDir = PlatformPath::join(sys_get_temp_dir(), 'cl_backup_'.uniqid('', true));
        mkdir($tempDir, 0755, true);
        $tempSql = PlatformPath::join($tempDir, $entryName);

        try {
            if (file_put_contents($tempSql, $content) === false) {
                throw new RuntimeException('No se pudo preparar el archivo temporal para ZIP.');
            }

            $result = Process::timeout(120)
                ->cwd($tempDir)
                ->run(['zip', '-j', $zipPath, $entryName]);

            if (! $result->successful() || ! is_file($zipPath)) {
                $details = trim($result->errorOutput()."\n".$result->output());
                throw new RuntimeException(
                    $details !== '' ? $details : 'El comando zip falló sin detalle.'
                );
            }
        } finally {
            @unlink($tempSql);
            @rmdir($tempDir);
        }
    }

    private function commandExists(string $command): bool
    {
        $finder = PHP_OS_FAMILY === 'Windows'
            ? Process::run(['where', $command])
            : Process::run(['which', $command]);

        return $finder->successful();
    }
}
