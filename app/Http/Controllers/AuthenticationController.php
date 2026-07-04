<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class AuthenticationController extends Controller
{
    public function login(Request $request): View|JsonResponse|RedirectResponse
    {
        if ($request->isMethod('post')) {
            $credentials = $request->validate([
                'email' => ['required', 'email'],
                'password' => ['required', 'string'],
            ]);

            $user = User::query()
                ->where('email', $credentials['email'])
                ->where('is_active', true)
                ->first();

            if (! $user || ! Auth::validate(['email' => $credentials['email'], 'password' => $credentials['password']])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Credenciales inválidas o usuario inactivo.',
                ], 401);
            }

            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();

            UserSession::query()->create([
                'user_id' => $user->id,
                'login_ip' => $request->ip(),
                'last_known_ip' => $request->ip(),
                'user_agent' => $request->userAgent() ?? 'unknown',
                'login_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Sesión iniciada correctamente.',
                'redirect' => route('dashboard'),
                'theme_preference' => $user->theme_preference ?? 'auto',
            ]);
        }

        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('authentication.login');
    }

    public function logout(Request $request): JsonResponse
    {
        $user = Auth::user();

        if ($user) {
            UserSession::query()
                ->where('user_id', $user->id)
                ->whereNull('logout_at')
                ->latest('login_at')
                ->limit(1)
                ->update([
                    'logout_at' => now(),
                    'last_known_ip' => $request->ip(),
                ]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada.',
            'redirect' => route('login'),
        ]);
    }

    public function getUserById(Request $request): JsonResponse
    {
        $userId = (int) $request->query('id', Auth::id());

        if ($userId !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'No autorizado.'], 403);
        }

        $user = User::query()->findOrFail($userId);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'dui' => $user->dui,
                'theme_preference' => $user->theme_preference ?? 'auto',
            ],
        ]);
    }

    public function updateThemePreference(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'theme_preference' => ['required', 'in:light,dark,auto'],
        ]);

        $user = Auth::user();

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'No autenticado.'], 401);
        }

        $user->update(['theme_preference' => $validated['theme_preference']]);

        return response()->json([
            'success' => true,
            'message' => 'Preferencia de tema guardada.',
            'theme_preference' => $user->theme_preference,
        ]);
    }

    public function actualizarSesion(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'No autenticado.'], 401);
        }

        UserSession::query()
            ->where('user_id', $user->id)
            ->whereNull('logout_at')
            ->latest('login_at')
            ->limit(1)
            ->update(['last_known_ip' => $request->ip()]);

        return response()->json([
            'success' => true,
            'message' => 'Sesión actualizada.',
            'ip' => $request->ip(),
        ]);
    }

    public function forgotPassword(Request $request): View|JsonResponse
    {
        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'email' => ['required', 'email'],
            ]);

            $status = Password::sendResetLink(['email' => $validated['email']]);

            if ($status !== Password::RESET_LINK_SENT) {
                return response()->json([
                    'success' => false,
                    'message' => __($status),
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Si el correo existe, recibirás un enlace para restablecer tu contraseña.',
            ]);
        }

        return view('authentication.forgot-password');
    }

    public function resetPassword(Request $request, ?string $token = null): View|JsonResponse|RedirectResponse
    {
        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'token' => ['required', 'string'],
                'email' => ['required', 'email'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ]);

            $status = Password::reset(
                $validated,
                function (User $user, string $password) {
                    $user->update(['password' => Hash::make($password)]);
                }
            );

            if ($status !== Password::PASSWORD_RESET) {
                return response()->json([
                    'success' => false,
                    'message' => __($status),
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Contraseña restablecida correctamente.',
                'redirect' => route('login'),
            ]);
        }

        return view('authentication.reset-password', [
            'token' => $token ?? $request->query('token'),
            'email' => $request->query('email'),
        ]);
    }
}
