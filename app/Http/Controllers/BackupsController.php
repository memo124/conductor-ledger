<?php

namespace App\Http\Controllers;

use App\DTO\FormalNotificationData;
use App\Models\BackupDownloadToken;
use App\Services\BackupService;
use App\Services\NotificationService;
use App\Services\SecurityAuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupsController extends Controller
{
    public function __construct(
        private readonly BackupService $backups,
        private readonly SecurityAuditService $audit,
        private readonly NotificationService $notifications,
    ) {}

    public function index(): View
    {
        return view('administracion.backups.index');
    }

    public function generate(Request $request): JsonResponse
    {
        $this->audit->log('backup.requested', Auth::id(), null, null, null, $request);

        try {
            $result = $this->backups->createDatabaseBackup(Auth::user());

            return response()->json([
                'success' => true,
                'message' => 'Respaldo generado correctamente.',
                'data' => $result,
            ]);
        } catch (\Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422, [], JSON_INVALID_UTF8_SUBSTITUTE);
        }
    }

    public function issueDownloadLink(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'filename' => ['required', 'string', 'max:255'],
            'checksum' => ['required', 'string', 'size:64'],
        ]);

        $token = $this->backups->issueDownloadToken(
            Auth::user(),
            $validated['filename'],
            $validated['checksum']
        );

        $this->audit->log('backup.download_token_issued', Auth::id(), null, null, [
            'filename' => $validated['filename'],
            'token_id' => $token->id,
        ], $request);

        $this->notifications->sendFormal(Auth::user()->email, new FormalNotificationData(
            subject: 'Enlace de descarga de respaldo — '.config('app.name'),
            recipientName: Auth::user()->name,
            headline: 'Descarga de respaldo autorizada',
            message: 'Se generó un enlace temporal para descargar el respaldo '.$validated['filename'].'.',
            eventAt: now(),
            actionUrl: route('admin.backups.download', $token->id),
            actionLabel: 'Descargar respaldo',
        ));

        return response()->json([
            'success' => true,
            'download_url' => route('admin.backups.download', $token->id),
            'expires_at' => $token->expires_at->toIso8601String(),
        ]);
    }

    public function download(string $tokenId): BinaryFileResponse
    {
        /** @var BackupDownloadToken $token */
        $token = $this->backups->resolveDownload($tokenId);

        if ($token->user_id !== Auth::id() && ! Auth::user()->isAdmin()) {
            abort(403);
        }

        $path = $this->backups->backupFilePath($token->filename);

        if (hash_file('sha256', $path) !== $token->checksum) {
            abort(409, 'Checksum inválido.');
        }

        $token->update(['used_at' => now()]);

        $this->audit->log('backup.downloaded', Auth::id(), null, null, [
            'filename' => $token->filename,
            'token_id' => $token->id,
        ], request());

        return response()->download($path, $token->filename, [
            'Content-Type' => 'application/zip',
        ]);
    }
}
