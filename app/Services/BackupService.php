<?php

namespace App\Services;

use App\Models\BackupDownloadToken;
use App\Models\User;
use App\Support\PlatformPath;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class BackupService
{
    public function __construct(
        private readonly SecurityAuditService $audit,
        private readonly PgDumpResolver $pgDumpResolver,
        private readonly ZipPackager $zipPackager,
        private readonly SubprocessRunner $subprocess,
    ) {}

    public function createDatabaseBackup(?User $actor = null): array
    {
        $disk = Storage::disk(config('conductor-ledger.backup.disk', 'backup_local'));
        $timestamp = now()->format('Ymd_His');
        $sqlFilename = "conductorledger_{$timestamp}.sql";
        $zipFilename = "conductorledger_{$timestamp}.zip";
        $relativeDir = now()->format('Y/m');
        $sqlRelativePath = "{$relativeDir}/{$sqlFilename}";
        $zipRelativePath = "{$relativeDir}/{$zipFilename}";

        $disk->makeDirectory($relativeDir);

        $sqlAbsolutePath = $this->absoluteBackupPath($relativeDir, $sqlFilename);
        $zipAbsolutePath = $this->absoluteBackupPath($relativeDir, $zipFilename);

        $pgDump = $this->pgDumpResolver->resolve();

        $command = [
            $pgDump,
            '--format=plain',
            '--no-owner',
            '--no-acl',
            '--file='.$sqlAbsolutePath,
            '--host='.config('database.connections.pgsql.host'),
            '--port='.config('database.connections.pgsql.port'),
            '--username='.config('database.connections.pgsql.username'),
            config('database.connections.pgsql.database'),
        ];

        $result = $this->subprocess->run(
            $command,
            dirname($pgDump),
            [
                'PGPASSWORD' => (string) config('database.connections.pgsql.password'),
                'PGCLIENTENCODING' => 'UTF8',
                'PG_BIN' => dirname($pgDump),
            ],
            300,
        );

        if (! $result->successful()) {
            $disk->delete($sqlRelativePath);
            throw new RuntimeException($this->formatProcessFailure($result));
        }

        if (! is_file($sqlAbsolutePath) || filesize($sqlAbsolutePath) === 0) {
            $disk->delete($sqlRelativePath);
            throw new RuntimeException('pg_dump no generó un archivo SQL válido.');
        }

        $sql = file_get_contents($sqlAbsolutePath);
        if ($sql === false || ! str_contains($sql, 'PostgreSQL database dump')) {
            $disk->delete($sqlRelativePath);
            throw new RuntimeException('pg_dump no generó un archivo SQL válido.');
        }

        try {
            $this->zipPackager->createFromString($zipAbsolutePath, $sql, $sqlFilename);
        } catch (\Throwable $exception) {
            $disk->delete($sqlRelativePath);
            throw $exception;
        }

        $disk->delete($sqlRelativePath);

        if (! is_file($zipAbsolutePath) || filesize($zipAbsolutePath) === 0) {
            $disk->delete($zipRelativePath);
            throw new RuntimeException('El archivo ZIP del respaldo quedó vacío.');
        }

        $checksum = hash_file('sha256', $zipAbsolutePath);

        $this->audit->log('backup.created', $actor?->id, null, null, [
            'filename' => $zipFilename,
            'checksum' => $checksum,
            'path' => $zipAbsolutePath,
        ]);

        $this->purgeOldBackups($disk);

        return [
            'filename' => $zipFilename,
            'path' => $zipRelativePath,
            'absolute_path' => $zipAbsolutePath,
            'checksum' => $checksum,
            'size' => filesize($zipAbsolutePath),
        ];
    }

    public function issueDownloadToken(User $user, string $filename, string $checksum): BackupDownloadToken
    {
        $ttl = (int) config('conductor-ledger.backup.download_token_ttl_minutes', 15);

        return BackupDownloadToken::query()->create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'filename' => $filename,
            'checksum' => $checksum,
            'expires_at' => now()->addMinutes($ttl),
        ]);
    }

    public function resolveDownload(string $tokenId): BackupDownloadToken
    {
        $token = BackupDownloadToken::query()->findOrFail($tokenId);

        if (! $token->isValid()) {
            abort(410, 'El enlace de descarga expiró o ya fue utilizado.');
        }

        return $token;
    }

    public function backupFilePath(string $filename): string
    {
        $disk = Storage::disk(config('conductor-ledger.backup.disk', 'backup_local'));

        foreach ($disk->allFiles() as $path) {
            if (basename($path) === $filename) {
                return PlatformPath::normalize($disk->path($path));
            }
        }

        throw new RuntimeException('Archivo de respaldo no encontrado.');
    }

    private function absoluteBackupPath(string $relativeDir, string $filename): string
    {
        $root = PlatformPath::normalize(storage_path('app/backups'));

        return PlatformPath::join($root, str_replace('/', DIRECTORY_SEPARATOR, $relativeDir), $filename);
    }

    private function purgeOldBackups($disk): void
    {
        $retentionMonths = (int) config('conductor-ledger.backup.retention_months', 12);
        $threshold = now()->subMonths($retentionMonths);

        foreach ($disk->allFiles() as $path) {
            if ($disk->lastModified($path) < $threshold->getTimestamp()) {
                $disk->delete($path);
            }
        }
    }

    private function formatProcessFailure(SubprocessResult $result): string
    {
        $details = trim($this->sanitizeCliOutput($result->stderr)."\n".$this->sanitizeCliOutput($result->stdout));

        return $details !== '' ? $details : 'pg_dump falló sin detalle.';
    }

    private function sanitizeCliOutput(string $output): string
    {
        if ($output === '' || mb_check_encoding($output, 'UTF-8')) {
            return $output;
        }

        $converted = mb_convert_encoding($output, 'UTF-8', 'Windows-1252');

        return $converted !== false ? $converted : $output;
    }
}
