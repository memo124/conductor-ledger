<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('dashboard.index');
    }

    public function getResumen(): JsonResponse
    {
        $userId = Auth::id();
        $anio = (int) date('Y');
        $mes = (int) date('n');

        $viajesMes = DB::table('trips')
            ->where('user_id', $userId)
            ->whereRaw('EXTRACT(YEAR FROM fecha) = ?', [$anio])
            ->whereRaw('EXTRACT(MONTH FROM fecha) = ?', [$mes])
            ->selectRaw('
                COALESCE(SUM(indrive + otros_viajes + propina), 0) AS ingresos,
                COALESCE(SUM(alquiler), 0) AS alquiler
            ')
            ->first();

        $gastosMes = DB::table('expenses')
            ->where('user_id', $userId)
            ->whereRaw('EXTRACT(YEAR FROM fecha) = ?', [$anio])
            ->whereRaw('EXTRACT(MONTH FROM fecha) = ?', [$mes])
            ->sum('monto');

        $ingresos = (float) ($viajesMes->ingresos ?? 0);
        $alquiler = (float) ($viajesMes->alquiler ?? 0);
        $gastos = (float) $gastosMes;
        $neto = $ingresos - $alquiler - $gastos;

        return response()->json([
            'success' => true,
            'data' => [
                'ingresos' => number_format($ingresos, 2),
                'alquiler' => number_format($alquiler, 2),
                'gastos' => number_format($gastos, 2),
                'neto' => number_format($neto, 2),
                'anio' => $anio,
                'mes' => $mes,
            ],
        ]);
    }
}
