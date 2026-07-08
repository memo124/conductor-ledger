<?php

namespace App\Jobs;

use App\DTO\FormalNotificationData;
use App\Services\BackupService;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class DatabaseBackupJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function handle(BackupService $backupService, NotificationService $notifications): void
    {
        try {
            $result = $backupService->createDatabaseBackup();

            $email = config('conductor-ledger.backup.notify_email');

            if ($email) {
                $notifications->sendFormal($email, new FormalNotificationData(
                    subject: 'Respaldo mensual completado — '.config('app.name'),
                    recipientName: 'Administrador',
                    headline: 'Respaldo de base de datos exitoso',
                    message: 'Se generó el respaldo '.$result['filename'].' con checksum '.$result['checksum'].'.',
                    eventAt: now(),
                ));
            }
        } catch (\Throwable $exception) {
            Log::channel('security')->error('backup.failed', ['error' => $exception->getMessage()]);

            $email = config('conductor-ledger.backup.notify_email');

            if ($email) {
                $notifications->sendFormal($email, new FormalNotificationData(
                    subject: 'Fallo en respaldo mensual — '.config('app.name'),
                    recipientName: 'Administrador',
                    headline: 'Error al generar respaldo',
                    message: 'El respaldo programado falló: '.$exception->getMessage(),
                    eventAt: now(),
                ));
            }

            throw $exception;
        }
    }
}
