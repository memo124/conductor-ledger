<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FormatsMoney;
use App\Services\EncryptionService;
use App\Services\FinancialRecordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    use FormatsMoney;
    public function __construct(private readonly FinancialRecordService $financialRecords) {}

    public function index(): View
    {
        return view('dashboard.index');
    }

    public function getResumen(): JsonResponse
    {
        $userId = Auth::id();
        $anio = (int) date('Y');
        $mes = (int) date('n');

        $tripTotals = $this->financialRecords->monthlyTripTotals($userId, $anio, $mes);
        $gastos = $this->financialRecords->monthlyExpenseTotal($userId, $anio, $mes);

        $ingresos = $tripTotals['ingresos'];
        $alquiler = $tripTotals['alquiler'];
        $comision = $tripTotals['comision_app'];
        $neto = $ingresos - $alquiler - $gastos;

        return response()->json([
            'success' => true,
            'data' => [
                'ingresos' => $this->moneyUsd($ingresos),
                'comision' => $this->moneyUsd($comision),
                'alquiler' => $this->moneyUsd($alquiler),
                'gastos' => $this->moneyUsd($gastos),
                'neto' => $this->moneyUsd($neto),
                'anio' => $anio,
                'mes' => $mes,
            ],
        ]);
    }
}
