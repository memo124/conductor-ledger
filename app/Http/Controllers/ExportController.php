<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function viajes(Request $request): Response|StreamedResponse
    {
        $format = $request->query('format', 'csv');
        $userId = Auth::id();
        $anio = (int) $request->query('anio', date('Y'));

        $rows = DB::table('trips')
            ->where('user_id', $userId)
            ->where('anio', $anio)
            ->orderByDesc('fecha')
            ->orderByDesc('trip_number')
            ->get();

        $headers = ['#', 'Fecha', 'Día', 'InDrive', 'Otros', 'Propina', 'Alquiler', 'Ingresos', 'Neto'];
        $data = $rows->map(function ($row) {
            $ingresos = (float) $row->indrive + (float) $row->otros_viajes + (float) $row->propina;

            return [
                $row->trip_number,
                $row->fecha,
                $row->dia_semana,
                number_format((float) $row->indrive, 2, '.', ''),
                number_format((float) $row->otros_viajes, 2, '.', ''),
                number_format((float) $row->propina, 2, '.', ''),
                number_format((float) $row->alquiler, 2, '.', ''),
                number_format($ingresos, 2, '.', ''),
                number_format($ingresos - (float) $row->alquiler, 2, '.', ''),
            ];
        })->all();

        $filename = "viajes_{$anio}";

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('exports.viajes-pdf', [
                'title' => "Viajes {$anio}",
                'headers' => $headers,
                'rows' => $data,
                'user' => Auth::user(),
            ]);

            return $pdf->download("{$filename}.pdf");
        }

        return $this->csvResponse("{$filename}.csv", $headers, $data);
    }

    public function gastos(Request $request): Response|StreamedResponse
    {
        $format = $request->query('format', 'csv');
        $userId = Auth::id();
        $anio = (int) $request->query('anio', date('Y'));

        $rows = DB::table('expenses')
            ->leftJoin('expense_categories', 'expenses.category_id', '=', 'expense_categories.id')
            ->select('expenses.*', 'expense_categories.name as category_name')
            ->where('expenses.user_id', $userId)
            ->where('expenses.anio', $anio)
            ->orderByDesc('expenses.fecha')
            ->get();

        $headers = ['#', 'Fecha', 'Categoría', 'Monto', 'Descripción'];
        $data = $rows->map(fn ($row) => [
            $row->expense_number,
            $row->fecha,
            $row->category_name,
            number_format((float) $row->monto, 2, '.', ''),
            $row->descripcion,
        ])->all();

        $filename = "gastos_{$anio}";

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('exports.viajes-pdf', [
                'title' => "Gastos {$anio}",
                'headers' => $headers,
                'rows' => $data,
                'user' => Auth::user(),
            ]);

            return $pdf->download("{$filename}.pdf");
        }

        return $this->csvResponse("{$filename}.csv", $headers, $data);
    }

    public function resumen(Request $request): Response|StreamedResponse
    {
        $format = $request->query('format', 'csv');
        $userId = Auth::id();
        $anio = (int) $request->query('anio', date('Y'));

        $ingresos = (float) DB::table('trips')
            ->where('user_id', $userId)
            ->where('anio', $anio)
            ->sum(DB::raw('indrive + otros_viajes + propina'));

        $alquiler = (float) DB::table('trips')->where('user_id', $userId)->where('anio', $anio)->sum('alquiler');
        $gastos = (float) DB::table('expenses')->where('user_id', $userId)->where('anio', $anio)->sum('monto');
        $neto = $ingresos - $alquiler - $gastos;

        $headers = ['Concepto', 'Monto'];
        $data = [
            ['Ingresos', number_format($ingresos, 2, '.', '')],
            ['Alquiler', number_format($alquiler, 2, '.', '')],
            ['Gastos', number_format($gastos, 2, '.', '')],
            ['Ganancia neta', number_format($neto, 2, '.', '')],
        ];

        $filename = "resumen_{$anio}";

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('exports.viajes-pdf', [
                'title' => "Resumen financiero {$anio}",
                'headers' => $headers,
                'rows' => $data,
                'user' => Auth::user(),
            ]);

            return $pdf->download("{$filename}.pdf");
        }

        return $this->csvResponse("{$filename}.csv", $headers, $data);
    }

    private function csvResponse(string $filename, array $headers, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, $headers);
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
