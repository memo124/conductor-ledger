<?php

namespace App\Services;

use App\Models\BackupDownloadToken;
use App\Models\User;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class BackupService
{
    public function __construct(
        private readonly SecurityAuditService $audit,
    ) {}

    public function createDatabaseBackup(?User $actor = null): array
    {
        $disk = Storage::disk(config('conductor-ledger.backup.disk', 'backup_local'));
        $timestamp = now()->format('Ymd_His');
        $filename = "conductorledger_{$timestamp}.dump";
        $relativePath = now()->format('Y/m')."/{$filename}";
        $absolutePath = $disk->path($relativePath);

        $disk->makeDirectory(dirname($relativePath));

        $command = [
            config('conductor-ledger.backup.pg_dump_binary', 'pg_dump'),
            '--format=custom',
            '--no-owner',
            '--no-acl',
            '--file='.$absolutePath,
            '--host='.config('database.connections.pgsql.host'),
            '--port='.config('database.connections.pgsql.port'),
            '--username='.config('database.connections.pgsql.username'),
            config('database.connections.pgsql.database'),
        ];

        $result = Process::env([
            'PGPASSWORD' => config('database.connections.pgsql.password'),
        ])->run($command);

        if (! $result->successful()) {
            throw new RuntimeException(trim($result->errorOutput()) ?: 'pg_dump falló.');
        }

        $compressedName = $filename.'.gz';
        $compressedRelative = now()->format('Y/m')."/{$compressedName}";
        $compressedAbsolute = $disk->path($compressedRelative);

        $gz = gzopen($compressedAbsolute, 'wb9');
        gzwrite($gz, file_get_contents($absolutePath));
        gzclose($gz);
        $disk->delete($relativePath);

        $checksum = hash_file('sha256', $compressedAbsolute);

        $this->audit->log('backup.created', $actor?->id, null, null, [
            'filename' => $compressedName,
            'checksum' => $checksum,
        ]);

        $this->purgeOldBackups($disk);

        return [
            'filename' => $compressedName,
            'path' => $compressedRelative,
            'checksum' => $checksum,
            'size' => filesize($compressedAbsolute),
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
            throw new RuntimeException('El enlace de descarga expiró o ya fue utilizado.');
        }

        return $token;
    }

    public function backupFilePath(string $filename): string
    {
        $disk = Storage::disk(config('conductor-ledger.backup.disk', 'backup_local'));
        $matches = $disk->allFiles();

        foreach ($matches as $path) {
            if (basename($path) === $filename) {
                return $disk->path($path);
            }
        }

        throw new RuntimeException('Archivo de respaldo no encontrado.');
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
}
