<?php

namespace App\Http\Controllers;

use App\DTO\FormalNotificationData;
use App\Models\User;
use App\Models\UserSession;
use App\Services\EncryptionService;
use App\Services\NotificationService;
use App\Services\SecurityAuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class AuthenticationController extends Controller
{
    public function __construct(
        private readonly EncryptionService $encryption,
        private readonly SecurityAuditService $audit,
        private readonly NotificationService $notifications,
    ) {}

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
                $this->audit->log('auth.login_failed', null, $user?->id, null, ['email' => $credentials['email']], $request);

                return response()->json([
                    'success' => false,
                    'message' => 'Credenciales inválidas o usuario inactivo.',
                ], 401);
            }

            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();

            try {
                $this->bootstrapEncryptionSession($user, $credentials['password'], $request);
            } catch (\Throwable $exception) {
                Auth::logout();

                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo iniciar la sesión de cifrado: '.$exception->getMessage(),
                ], 422);
            }

            UserSession::query()->create([
                'user_id' => $user->id,
                'login_ip' => $request->ip(),
                'last_known_ip' => $request->ip(),
                'user_agent' => $request->userAgent() ?? 'unknown',
                'login_at' => now(),
            ]);

            $this->audit->log('auth.login_success', $user->id, $user->id, null, null, $request);

            return response()->json([
                'success' => true,
                'message' => 'Sesión iniciada correctamente.',
                'redirect' => route('dashboard'),
                'theme_preference' => $user->theme_preference ?? 'auto',
                'locale_preference' => $user->locale_preference ?? 'es',
                'currency_preference' => $user->currency_preference ?? 'USD',
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

            $this->audit->log('auth.logout', $user->id, $user->id, null, null, $request);
        }

        $this->encryption->clearDekFromSession($request->session());
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
                'locale_preference' => $user->locale_preference ?? 'es',
                'currency_preference' => $user->currency_preference ?? 'USD',
            ],
        ]);
    }

    public function updateLocalePreference(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'locale_preference' => ['required', 'in:es,en'],
        ]);

        $user = Auth::user();

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'No autenticado.'], 401);
        }

        $user->update(['locale_preference' => $validated['locale_preference']]);

        return response()->json([
            'success' => true,
            'message' => ui('profile.save'),
            'locale_preference' => $user->locale_preference,
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
                    $payload = ['password' => Hash::make($password)];

                    if ($user->encrypted_dek && $user->admin_wrapped_dek) {
                        $dek = $this->encryption->unwrapUserDekWithMasterKey($user);
                        $payload = array_merge($payload, $this->encryption->rewrapUserDek($user, $dek, $password));
                    } elseif (! $user->encrypted_dek) {
                        $keys = $this->encryption->createUserKeyEnvelope($password);
                        $payload = array_merge($payload, [
                            'encrypted_dek' => $keys['encrypted_dek'],
                            'admin_wrapped_dek' => $keys['admin_wrapped_dek'],
                            'dek_salt' => $keys['dek_salt'],
                            'kdf_params' => $keys['kdf_params'],
                        ]);
                    }

                    $user->update($payload);
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

    private function bootstrapEncryptionSession(User $user, string $password, Request $request): void
    {
        if (! $user->encrypted_dek) {
            $keys = $this->encryption->createUserKeyEnvelope($password);
            $user->update([
                'encrypted_dek' => $keys['encrypted_dek'],
                'admin_wrapped_dek' => $keys['admin_wrapped_dek'],
                'dek_salt' => $keys['dek_salt'],
                'kdf_params' => $keys['kdf_params'],
            ]);
            $dek = $keys['dek'];
        } else {
            $dek = $this->encryption->unwrapUserDek($user, $password);
        }

        $this->encryption->storeDekInSession($request->session(), $dek);
    }
}
