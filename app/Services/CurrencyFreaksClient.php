<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class CurrencyFreaksClient
{
    public function fetchLatestRates(): array
    {
        $apiKey = config('conductor-ledger.currency.api_key');

        if (! $apiKey) {
            throw new RuntimeException('CURRENCYFREAKS_API_KEY no está configurada.');
        }

        $response = Http::timeout(30)
            ->acceptJson()
            ->get(config('conductor-ledger.currency.api_url'), [
                'apikey' => $apiKey,
            ]);

        if ($response->failed()) {
            throw new RequestException($response);
        }

        $payload = $response->json();

        if (! is_array($payload) || empty($payload['rates']) || ! is_array($payload['rates'])) {
            throw new RuntimeException('Respuesta inválida de CurrencyFreaks.');
        }

        return [
            'base' => $payload['base'] ?? config('conductor-ledger.currency.base', 'USD'),
            'date' => $payload['date'] ?? null,
            'rates' => $payload['rates'],
        ];
    }
}
