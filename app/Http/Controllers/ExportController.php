<?php

namespace App\Http\Controllers;

use App\Services\FinancialRecordService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function __construct(private readonly FinancialRecordService $financialRecords) {}

    public function viajes(Request $request): Response|StreamedResponse
    {
        $format = $request->query('format', 'csv');
        $userId = Auth::id();
        $anio = (int) $request->query('anio', date('Y'));

        $rows = DB::table('trips as t')
            ->leftJoin('trip_types as tt', 'tt.id', '=', 't.trip_type_id')
            ->leftJoin('platforms as p', 'p.id', '=', 't.platform_id')
            ->where('t.user_id', $userId)
            ->where('t.anio', $anio)
            ->select(['t.*', 'tt.name as trip_type_name', 'p.name as platform_name'])
            ->orderByDesc('t.fecha')
            ->orderByDesc('t.trip_number')
            ->get();

        $headers = ['#', 'Fecha', 'Tipo', 'Plataforma', 'Modo', 'Bruto', 'Comisión', 'Cobrado', 'Propina', 'Alquiler', 'Ingresos', 'Neto'];
        $data = $rows->map(function ($row) {
            $amounts = $this->financialRecords->decryptTripRow($row);
            $ingresos = $this->financialRecords->tripIngresos($amounts);

            return [
                $row->trip_number,
                $row->fecha,
                $row->trip_type_name ?? '—',
                $row->platform_name ?? '—',
                $row->registration_mode,
                number_format($amounts['monto_bruto'], 2, '.', ''),
                number_format($amounts['comision_app'], 2, '.', ''),
                number_format($amounts['monto_cobrado'], 2, '.', ''),
                number_format($amounts['propina'], 2, '.', ''),
                number_format($amounts['alquiler'], 2, '.', ''),
                number_format($ingresos, 2, '.', ''),
                number_format($ingresos - $amounts['alquiler'], 2, '.', ''),
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
        $data = $rows->map(function ($row) {
            $amounts = $this->financialRecords->decryptExpenseRow($row);

            return [
                $row->expense_number,
                $row->fecha,
                $row->category_name,
                number_format($amounts['monto'], 2, '.', ''),
                $amounts['descripcion'],
            ];
        })->all();

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

        $tripTotals = $this->financialRecords->monthlyTripTotals($userId, $anio);
        $ingresos = $tripTotals['ingresos'];
        $comision = $tripTotals['comision_app'];
        $alquiler = $tripTotals['alquiler'];
        $gastos = $this->financialRecords->monthlyExpenseTotal($userId, $anio);
        $neto = $ingresos - $alquiler - $gastos;

        $headers = ['Concepto', 'Monto'];
        $data = [
            ['Ingresos', number_format($ingresos, 2, '.', '')],
            ['Comisión app', number_format($comision, 2, '.', '')],
            ['Alquiler / cuota', number_format($alquiler, 2, '.', '')],
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
