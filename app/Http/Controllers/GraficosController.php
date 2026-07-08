<?php

namespace App\Http\Controllers;

use App\Services\FinancialRecordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class GraficosController extends Controller
{
    public function __construct(private readonly FinancialRecordService $financialRecords) {}

    public function index(): View
    {
        return view('graficos.index');
    }

    public function getMetrics(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $anio = (int) $request->query('anio', date('Y'));
        $metrics = $this->financialRecords->chartMetrics($userId, $anio);

        $meses = [];
        for ($m = 1; $m <= 12; $m++) {
            $meses[] = $this->monthLabel($m);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'meses' => $meses,
                'ingresos' => $metrics['ingresos'],
                'alquileres' => $metrics['alquileres'],
                'gastos' => $metrics['gastos'],
                'netos' => $metrics['netos'],
                'plataformas' => $metrics['plataformas'],
                'totals' => $metrics['totals'],
            ],
        ]);
    }

    private function monthLabel(int $month): string
    {
        return [
            1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun',
            7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic',
        ][$month] ?? (string) $month;
    }
}
