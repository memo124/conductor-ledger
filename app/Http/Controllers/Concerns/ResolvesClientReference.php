<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Client;
use App\Models\ClientDependent;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

trait ResolvesClientReference
{
    /**
     * @return array{client_id: ?int, client_dependent_id: ?int, display_name?: ?string, client_display_name?: ?string}
     */
    protected function resolveClientReference(
        Request $request,
        int $userId,
        bool $required = false,
        string $displayField = 'client_display_name',
    ): array {
        $rawClientId = $request->input('client_id');
        $rawDependentId = $request->input('client_dependent_id');
        $displayName = trim((string) $request->input($displayField, ''));

        $clientId = ($rawClientId !== null && $rawClientId !== '') ? (int) $rawClientId : null;
        $dependentId = ($rawDependentId !== null && $rawDependentId !== '') ? (int) $rawDependentId : null;

        if ($clientId) {
            Client::query()
                ->where('user_id', $userId)
                ->where('id', $clientId)
                ->firstOrFail();
        } else {
            $clientId = null;
            $dependentId = null;
        }

        if ($dependentId) {
            if (! $clientId) {
                throw ValidationException::withMessages([
                    'client_dependent_id' => 'Seleccione primero el cliente titular.',
                ]);
            }

            ClientDependent::query()
                ->where('id', $dependentId)
                ->where('client_id', $clientId)
                ->whereHas('client', fn ($q) => $q->where('user_id', $userId))
                ->firstOrFail();
        } else {
            $dependentId = null;
        }

        if ($clientId) {
            $displayName = '';
        }

        if ($required && ! $clientId && $displayName === '') {
            throw ValidationException::withMessages([
                $displayField => ui('pages.clientes.client_reference_required'),
            ]);
        }

        $result = [
            'client_id' => $clientId,
            'client_dependent_id' => $dependentId,
        ];

        $result[$displayField] = $displayName !== '' ? $displayName : null;

        return $result;
    }

    protected function resolvedClientLabel(?int $clientId, ?int $dependentId, ?string $displayName): string
    {
        if ($dependentId) {
            $dependent = ClientDependent::query()->find($dependentId);
            if ($dependent) {
                return $dependent->name.($dependent->relationship_label ? ' ('.$dependent->relationship_label.')' : '');
            }
        }

        if ($clientId) {
            $client = Client::query()->find($clientId);

            return $client?->name ?? '—';
        }

        return $displayName ?: '—';
    }
}
