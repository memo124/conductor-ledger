<?php

namespace App\Http\Controllers;

use App\DTO\FormalNotificationData;
use App\Models\Role;
use App\Models\User;
use App\Services\EncryptionService;
use App\Services\NotificationService;
use App\Services\SecurityAuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function __construct(
        private readonly EncryptionService $encryption,
        private readonly NotificationService $notifications,
        private readonly SecurityAuditService $audit,
    ) {}

    public function show(): View
    {
        return view('authentication.register');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'dui' => ['nullable', 'string', 'max:10', 'unique:users,dui'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'Ingrese un correo válido.',
            'email.unique' => 'Este correo ya está registrado. Use otro o inicie sesión.',
            'dui.unique' => 'Este DUI ya está registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        $keys = $this->encryption->createUserKeyEnvelope($validated['password']);

        $user = DB::transaction(function () use ($validated, $keys) {
            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'dui' => $validated['dui'] ?: null,
                'password' => Hash::make($validated['password']),
                'is_active' => config('conductor-ledger.registration_mode') === 'approval' ? false : true,
                'role' => 'user',
                'encrypted_dek' => $keys['encrypted_dek'],
                'admin_wrapped_dek' => $keys['admin_wrapped_dek'],
                'dek_salt' => $keys['dek_salt'],
                'kdf_params' => $keys['kdf_params'],
            ]);

            $conductorRole = Role::query()->where('slug', 'conductor')->first();

            if ($conductorRole) {
                $user->roles()->sync([$conductorRole->id]);
            }

            return $user;
        });

        $eventAt = now();

        $mailUserSent = $this->notifications->sendFormal($user->email, new FormalNotificationData(
            subject: 'Registro recibido — '.config('app.name'),
            recipientName: $user->name,
            headline: 'Solicitud de registro recibida',
            message: config('conductor-ledger.registration_mode') === 'approval'
                ? 'Su cuenta está pendiente de activación por un administrador.'
                : 'Su cuenta ha sido creada correctamente.',
            eventAt: $eventAt,
        ));

        $this->notifications->notifyAdmins(new FormalNotificationData(
            subject: 'Nuevo registro pendiente — '.config('app.name'),
            recipientName: 'Administrador',
            headline: 'Nuevo usuario registrado',
            message: 'El usuario '.$user->name.' ('.$user->email.') solicitó acceso al sistema.',
            eventAt: $eventAt,
            actionUrl: route('usuarios.index'),
            actionLabel: 'Revisar usuarios',
        ));

        $this->audit->log('registration.created', null, $user->id, null, null, $request);

        $message = config('conductor-ledger.registration_mode') === 'approval'
            ? 'Registro enviado. Un administrador activará su cuenta.'
            : 'Registro completado. Ya puede iniciar sesión.';

        if (! $mailUserSent) {
            $message .= ' No se pudo enviar el correo de confirmación; el registro sí fue guardado.';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'redirect' => route('login'),
        ]);
    }
}
