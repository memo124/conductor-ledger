<?php

namespace App\Http\Controllers;

use App\Services\ExchangeRateService;
use App\Services\MoneyFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CurrencyConverterController extends Controller
{
    public function __construct(
        private readonly ExchangeRateService $exchangeRates,
        private readonly MoneyFormatter $money,
    ) {}

    public function index(): View
    {
        $sync = $this->exchangeRates->lastSuccessfulSync();

        return view('conversor.index', [
            'fiatCurrencies' => $this->exchangeRates->activeCurrencies('fiat'),
            'cryptoCurrencies' => $this->exchangeRates->activeCurrencies('crypto'),
            'lastSyncAt' => $sync?->created_at,
            'lastSyncDate' => $sync?->source_date,
        ]);
    }

    public function convert(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => ['required', 'string', 'max:10'],
            'to' => ['required', 'string', 'max:10'],
            'amount' => ['required', 'numeric', 'min:0'],
        ]);

        $from = strtoupper($validated['from']);
        $to = strtoupper($validated['to']);
        $amount = (float) $validated['amount'];

        if (! $this->exchangeRates->hasRate($from) || ! $this->exchangeRates->hasRate($to)) {
            return response()->json([
                'success' => false,
                'message' => ui('pages.conversor.convert_error'),
            ], 422);
        }

        $result = $this->exchangeRates->convert($amount, $from, $to);
        $rate = $this->exchangeRates->crossRate($from, $to);
        $inverse = $this->exchangeRates->crossRate($to, $from);

        return response()->json([
            'success' => true,
            'data' => [
                'from' => $from,
                'to' => $to,
                'amount' => $amount,
                'result' => $result,
                'result_formatted' => $this->money->formatInCurrency($result, $to),
                'rate' => $rate,
                'inverse' => $inverse,
            ],
        ]);
    }

    public function rates(Request $request): JsonResponse
    {
        $category = $request->query('category', 'fiat_fiat');

        return response()->json([
            'success' => true,
            'data' => $this->exchangeRates->ratePairs($category),
        ]);
    }
}
