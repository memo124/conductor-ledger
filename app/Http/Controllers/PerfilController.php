<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\EncryptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PerfilController extends Controller
{
    public function __construct(private readonly EncryptionService $encryption) {}

    public function index(): View
    {
        $exchangeRates = app(\App\Services\ExchangeRateService::class);

        return view('perfil.index', [
            'user' => Auth::user(),
            'fiatCurrencies' => $exchangeRates->activeCurrencies('fiat'),
            'cryptoCurrencies' => $exchangeRates->activeCurrencies('crypto'),
            'locales' => config('conductor-ledger.locales', []),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'dui' => ['nullable', 'string', 'max:10', Rule::unique('users', 'dui')->ignore($user->id)],
            'theme_preference' => ['nullable', 'in:light,dark,auto'],
            'locale_preference' => ['nullable', 'in:es,en'],
            'currency_preference' => ['nullable', 'string', 'max:10', Rule::exists('currencies', 'code')],
        ]);

        $validated['dui'] = ! empty($validated['dui']) ? $validated['dui'] : null;
        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Perfil actualizado correctamente.',
            'data' => $user->fresh(),
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        /** @var User $user */
        $user = Auth::user();

        if (! Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'La contraseña actual no es correcta.',
            ], 422);
        }

        $payload = ['password' => Hash::make($validated['password'])];

        if ($user->encrypted_dek) {
            $dek = $this->encryption->unwrapUserDek($user, $validated['current_password']);
            $payload = array_merge($payload, $this->encryption->rewrapUserDek($user, $dek, $validated['password']));
            $this->encryption->storeDekInSession($request->session(), $dek);
        }

        $user->update($payload);

        return response()->json([
            'success' => true,
            'message' => 'Contraseña actualizada correctamente.',
        ]);
    }
}
