<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UsuariosController extends Controller
{
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
            'role' => $user->role === 'admin' ? 'Administrador' : 'Conductor',
            'role_code' => $user->role,
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
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'dui' => ['required', 'string', 'max:10', 'unique:users,dui'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:admin,user'],
            'is_active' => ['required', 'boolean'],
        ]);

        $user = User::query()->create([
            ...$validated,
            'password' => Hash::make($validated['password']),
            'theme_preference' => 'auto',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Usuario creado correctamente.',
            'data' => $user,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::query()->findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'dui' => ['required', 'string', 'max:10', Rule::unique('users', 'dui')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', 'in:admin,user'],
            'is_active' => ['required', 'boolean'],
        ]);

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
        }

        $user->update($payload);

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
}
