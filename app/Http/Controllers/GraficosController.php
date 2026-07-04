<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GraficosController extends Controller
{
    public function index(): View
    {
        return view('graficos.index');
    }

    public function getMetrics(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $anio = (int) $request->query('anio', date('Y'));

        $monthlyTrips = DB::table('trips')
            ->selectRaw("
                EXTRACT(MONTH FROM fecha)::int AS mes,
                COALESCE(SUM(indrive), 0) AS indrive,
                COALESCE(SUM(otros_viajes), 0) AS otros,
                COALESCE(SUM(propina), 0) AS propinas,
                COALESCE(SUM(alquiler), 0) AS alquiler
            ")
            ->where('user_id', $userId)
            ->where('anio', $anio)
            ->groupByRaw('EXTRACT(MONTH FROM fecha)')
            ->orderBy('mes')
            ->get()
            ->keyBy('mes');

        $monthlyExpenses = DB::table('expenses')
            ->selectRaw('EXTRACT(MONTH FROM fecha)::int AS mes, COALESCE(SUM(monto), 0) AS gastos')
            ->where('user_id', $userId)
            ->where('anio', $anio)
            ->groupByRaw('EXTRACT(MONTH FROM fecha)')
            ->orderBy('mes')
            ->get()
            ->keyBy('mes');

        $meses = [];
        $ingresos = [];
        $alquileres = [];
        $gastos = [];
        $netos = [];

        for ($m = 1; $m <= 12; $m++) {
            $trip = $monthlyTrips->get($m);
            $expense = $monthlyExpenses->get($m);

            $income = $trip
                ? (float) $trip->indrive + (float) $trip->otros + (float) $trip->propinas
                : 0.0;
            $rent = $trip ? (float) $trip->alquiler : 0.0;
            $exp = $expense ? (float) $expense->gastos : 0.0;

            $meses[] = $this->monthLabel($m);
            $ingresos[] = round($income, 2);
            $alquileres[] = round($rent, 2);
            $gastos[] = round($exp, 2);
            $netos[] = round($income - $rent - $exp, 2);
        }

        $platformTotals = DB::table('trips')
            ->selectRaw('
                COALESCE(SUM(indrive), 0) AS indrive,
                COALESCE(SUM(otros_viajes), 0) AS otros,
                COALESCE(SUM(propina), 0) AS propinas
            ')
            ->where('user_id', $userId)
            ->where('anio', $anio)
            ->first();

        $totals = [
            'ingresos' => round((float) $platformTotals->indrive + (float) $platformTotals->otros + (float) $platformTotals->propinas, 2),
            'alquiler' => round((float) DB::table('trips')->where('user_id', $userId)->where('anio', $anio)->sum('alquiler'), 2),
            'gastos' => round((float) DB::table('expenses')->where('user_id', $userId)->where('anio', $anio)->sum('monto'), 2),
        ];
        $totals['neto'] = round($totals['ingresos'] - $totals['alquiler'] - $totals['gastos'], 2);

        return response()->json([
            'success' => true,
            'data' => [
                'meses' => $meses,
                'ingresos' => $ingresos,
                'alquileres' => $alquileres,
                'gastos' => $gastos,
                'netos' => $netos,
                'plataformas' => [
                    'InDrive' => round((float) $platformTotals->indrive, 2),
                    'Otros' => round((float) $platformTotals->otros, 2),
                    'Propinas' => round((float) $platformTotals->propinas, 2),
                ],
                'totals' => $totals,
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
