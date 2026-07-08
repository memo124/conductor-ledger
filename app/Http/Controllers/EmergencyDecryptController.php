<?php

namespace App\Http\Controllers;

use App\DTO\FormalNotificationData;
use App\Models\User;
use App\Services\EncryptionService;
use App\Services\FinancialRecordService;
use App\Services\NotificationService;
use App\Services\SecurityAuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class EmergencyDecryptController extends Controller
{
    public function __construct(
        private readonly EncryptionService $encryption,
        private readonly FinancialRecordService $financialRecords,
        private readonly SecurityAuditService $audit,
        private readonly NotificationService $notifications,
    ) {}

    public function index(): View
    {
        return view('administracion.emergency-decrypt.index');
    }

    public function decrypt(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
            'ticket_reference' => ['required', 'string', 'max:100'],
            'admin_password' => ['required', 'string'],
        ]);

        $admin = Auth::user();

        if (! Hash::check($validated['admin_password'], $admin->password)) {
            $this->audit->log('emergency_decrypt.denied', $admin->id, $validated['user_id'], 'Contraseña admin inválida', [
                'ticket' => $validated['ticket_reference'],
            ], $request);

            return response()->json([
                'success' => false,
                'message' => 'Contraseña de administrador incorrecta.',
            ], 422);
        }

        $target = User::query()->findOrFail($validated['user_id']);

        try {
            $dek = $this->encryption->unwrapUserDekWithMasterKey($target);
            $anio = (int) date('Y');
            $tripTotals = $this->financialRecords->monthlyTripTotals($target->id, $anio, null, $dek);
            $expenseTotal = $this->financialRecords->monthlyExpenseTotal($target->id, $anio, null, $dek);

            $this->audit->log(
                'emergency_decrypt.success',
                $admin->id,
                $target->id,
                $validated['reason'],
                ['ticket' => $validated['ticket_reference']],
                $request
            );

            $eventAt = now();

            $this->notifications->sendFormal($target->email, new FormalNotificationData(
                subject: 'Acceso de emergencia a sus datos — '.config('app.name'),
                recipientName: $target->name,
                headline: 'Acceso administrativo de emergencia',
                message: 'Un administrador accedió a sus datos financieros por motivo autorizado. Ticket: '.$validated['ticket_reference'],
                eventAt: $eventAt,
            ));

            $this->notifications->notifyAdmins(new FormalNotificationData(
                subject: 'Descifrado de emergencia ejecutado — '.config('app.name'),
                recipientName: $admin->name,
                headline: 'Auditoría de descifrado',
                message: 'Se descifraron datos del usuario '.$target->email.' con ticket '.$validated['ticket_reference'].'.',
                eventAt: $eventAt,
            ));

            return response()->json([
                'success' => true,
                'message' => 'Descifrado de emergencia completado.',
                'data' => [
                    'user' => $target->only(['id', 'name', 'email']),
                    'anio' => $anio,
                    'trip_totals' => $tripTotals,
                    'expense_total' => $expenseTotal,
                    'ingresos' => $tripTotals['indrive'] + $tripTotals['otros_viajes'] + $tripTotals['propina'],
                    'neto' => ($tripTotals['indrive'] + $tripTotals['otros_viajes'] + $tripTotals['propina'])
                        - $tripTotals['alquiler'] - $expenseTotal,
                ],
            ]);
        } catch (\Throwable $exception) {
            $this->audit->log('emergency_decrypt.failed', $admin->id, $target->id, $validated['reason'], [
                'error' => $exception->getMessage(),
                'ticket' => $validated['ticket_reference'],
            ], $request);

            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }
    }
}
