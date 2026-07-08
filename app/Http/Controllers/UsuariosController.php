<?php

namespace App\Http\Controllers;

use App\DTO\FormalNotificationData;
use App\Models\Role;
use App\Models\User;
use App\Services\EncryptionService;
use App\Services\NotificationService;
use App\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UsuariosController extends Controller
{
    public function __construct(
        private readonly EncryptionService $encryption,
        private readonly NotificationService $notifications,
        private readonly PermissionService $permissions,
    ) {}

    public function index(): View
    {
        return view('usuarios.index');
    }

    public function getDatatableServerSide(Request $request): JsonResponse
    {
        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = min((int) $request->input('length', 10), 100);
        $search = trim((string) $request->input('search.value', ''));

        $query = User::query()
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'ilike', "%{$search}%")
                        ->orWhere('email', 'ilike', "%{$search}%")
                        ->orWhere('dui', 'ilike', "%{$search}%");
                });
            });

        $recordsTotal = User::query()->count();
        $recordsFiltered = (clone $query)->count();

        $rows = $query->orderByDesc('id')->offset($start)->limit($length)->get();

        $data = $rows->map(fn (User $user) => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'dui' => $user->dui,
            'role' => $user->isAdmin() ? 'Administrador' : 'Conductor',
            'role_code' => $user->isAdmin() ? 'admin' : 'user',
            'is_active' => $user->is_active ? 'Activo' : 'Inactivo',
            'is_active_bool' => $user->is_active,
            'is_self' => $user->id === Auth::id(),
        ]);

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->normalizeBooleanFields($request, ['is_active']);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'dui' => ['required', 'string', 'max:10', 'unique:users,dui'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:admin,user'],
            'is_active' => ['required', 'boolean'],
        ], $this->validationMessages());

        $keys = $this->encryption->createUserKeyEnvelope($validated['password']);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'dui' => $validated['dui'],
            'password' => Hash::make($validated['password']),
            'is_active' => $validated['is_active'],
            'role' => $validated['role'],
            'theme_preference' => 'auto',
            'email_verified_at' => now(),
            'encrypted_dek' => $keys['encrypted_dek'],
            'admin_wrapped_dek' => $keys['admin_wrapped_dek'],
            'dek_salt' => $keys['dek_salt'],
            'kdf_params' => $keys['kdf_params'],
        ]);

        $this->syncUserRole($user, $validated['role']);

        return response()->json([
            'success' => true,
            'message' => 'Usuario creado correctamente.',
            'data' => $user,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::query()->findOrFail($id);
        $wasActive = $user->is_active;

        $this->normalizeBooleanFields($request, ['is_active']);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'dui' => ['required', 'string', 'max:10', Rule::unique('users', 'dui')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', 'in:admin,user'],
            'is_active' => ['required', 'boolean'],
        ], $this->validationMessages());

        if ($user->id === Auth::id() && $validated['role'] !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'No puedes quitarte el rol de administrador a ti mismo.',
            ], 422);
        }

        if ($user->id === Auth::id() && ! $validated['is_active']) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes desactivarte a ti mismo.',
            ], 422);
        }

        $payload = collect($validated)->except('password')->all();

        if (! empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);

            if ($user->encrypted_dek && $user->admin_wrapped_dek) {
                $dek = $this->encryption->unwrapUserDekWithMasterKey($user);
                $payload = array_merge($payload, $this->encryption->rewrapUserDek($user, $dek, $validated['password']));
            } else {
                $keys = $this->encryption->createUserKeyEnvelope($validated['password']);
                $payload = array_merge($payload, [
                    'encrypted_dek' => $keys['encrypted_dek'],
                    'admin_wrapped_dek' => $keys['admin_wrapped_dek'],
                    'dek_salt' => $keys['dek_salt'],
                    'kdf_params' => $keys['kdf_params'],
                ]);
            }
        }

        $user->update($payload);
        $this->syncUserRole($user, $validated['role']);
        $this->permissions->clearCacheForUser($user->id);

        if (! $wasActive && $validated['is_active']) {
            $this->notifications->sendFormal($user->email, new FormalNotificationData(
                subject: 'Cuenta activada — '.config('app.name'),
                recipientName: $user->name,
                headline: 'Su cuenta ha sido activada',
                message: 'Ya puede iniciar sesión en ConductorLedger.',
                eventAt: now(),
                actionUrl: route('login'),
                actionLabel: 'Iniciar sesión',
            ));
        }

        return response()->json([
            'success' => true,
            'message' => 'Usuario actualizado correctamente.',
            'data' => $user->fresh(),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        if ($id === Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes eliminar tu propia cuenta de administrador.',
            ], 422);
        }

        $user = User::query()->findOrFail($id);
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Usuario eliminado correctamente.',
        ]);
    }

    private function syncUserRole(User $user, string $legacyRole): void
    {
        $slug = $legacyRole === 'admin' ? 'administrador' : 'conductor';
        $role = Role::query()->where('slug', $slug)->first();

        if ($role) {
            $user->roles()->sync([$role->id]);
        }
    }

    private function normalizeBooleanFields(Request $request, array $fields): void
    {
        foreach ($fields as $field) {
            if (! $request->has($field)) {
                continue;
            }

            $value = $request->input($field);

            if (is_bool($value)) {
                $request->merge([$field => $value ? '1' : '0']);
            } elseif (in_array($value, ['true', 'false'], true)) {
                $request->merge([$field => $value === 'true' ? '1' : '0']);
            }
        }
    }

    private function validationMessages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'Ingrese un correo válido.',
            'email.unique' => 'Este correo ya está registrado.',
            'dui.required' => 'El DUI es obligatorio.',
            'dui.unique' => 'Este DUI ya está registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'role.required' => 'Seleccione un rol.',
            'role.in' => 'El rol seleccionado no es válido.',
            'is_active.required' => 'Seleccione el estado del usuario.',
            'is_active.boolean' => 'El estado debe ser Activo o Inactivo.',
        ];
    }
}
