<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FinancialRecordService
{
    public function __construct(
        private readonly EncryptionService $encryption,
    ) {}

    public function encryptTripPayload(array $amounts): array
    {
        $dek = $this->requireSessionDek();

        return [
            'encrypted_payload' => $this->encryption->encryptPayload($dek, [
                'indrive' => (float) ($amounts['indrive'] ?? 0),
                'otros_viajes' => (float) ($amounts['otros_viajes'] ?? 0),
                'propina' => (float) ($amounts['propina'] ?? 0),
                'alquiler' => (float) ($amounts['alquiler'] ?? 0),
            ]),
            'encryption_version' => 1,
            'indrive' => 0,
            'otros_viajes' => 0,
            'propina' => 0,
            'alquiler' => 0,
        ];
    }

    public function encryptExpensePayload(array $data): array
    {
        $dek = $this->requireSessionDek();

        return [
            'encrypted_payload' => $this->encryption->encryptPayload($dek, [
                'monto' => (float) ($data['monto'] ?? 0),
                'descripcion' => $data['descripcion'] ?? null,
            ]),
            'encryption_version' => 1,
            'monto' => 0,
            'descripcion' => null,
        ];
    }

    public function decryptTripRow(object $row, ?string $dek = null): array
    {
        if ((int) ($row->encryption_version ?? 0) === 1 && ! empty($row->encrypted_payload)) {
            $payload = $this->encryption->decryptPayload(
                $dek ?? $this->requireSessionDek(),
                $row->encrypted_payload
            );

            return [
                'indrive' => (float) ($payload['indrive'] ?? 0),
                'otros_viajes' => (float) ($payload['otros_viajes'] ?? 0),
                'propina' => (float) ($payload['propina'] ?? 0),
                'alquiler' => (float) ($payload['alquiler'] ?? 0),
            ];
        }

        return [
            'indrive' => (float) ($row->indrive ?? 0),
            'otros_viajes' => (float) ($row->otros_viajes ?? 0),
            'propina' => (float) ($row->propina ?? 0),
            'alquiler' => (float) ($row->alquiler ?? 0),
        ];
    }

    public function decryptExpenseRow(object $row, ?string $dek = null): array
    {
        if ((int) ($row->encryption_version ?? 0) === 1 && ! empty($row->encrypted_payload)) {
            $payload = $this->encryption->decryptPayload(
                $dek ?? $this->requireSessionDek(),
                $row->encrypted_payload
            );

            return [
                'monto' => (float) ($payload['monto'] ?? 0),
                'descripcion' => $payload['descripcion'] ?? null,
            ];
        }

        return [
            'monto' => (float) ($row->monto ?? 0),
            'descripcion' => $row->descripcion ?? null,
        ];
    }

    public function monthlyTripTotals(int $userId, int $anio, ?int $mes = null, ?string $dek = null): array
    {
        $query = DB::table('trips')
            ->where('user_id', $userId)
            ->where('anio', $anio)
            ->when($mes !== null, fn ($q) => $q->whereRaw('EXTRACT(MONTH FROM fecha) = ?', [$mes]));

        return $this->aggregateTripRows($query->get(), $dek);
    }

    public function monthlyExpenseTotal(int $userId, int $anio, ?int $mes = null, ?string $dek = null): float
    {
        $query = DB::table('expenses')
            ->where('user_id', $userId)
            ->where('anio', $anio)
            ->when($mes !== null, fn ($q) => $q->whereRaw('EXTRACT(MONTH FROM fecha) = ?', [$mes]));

        return $query->get()->sum(function ($row) use ($dek) {
            return $this->decryptExpenseRow($row, $dek)['monto'];
        });
    }

    public function tripComparativaByMonth(int $userId, int $anio, ?string $dek = null): Collection
    {
        $rows = DB::table('trips')
            ->where('user_id', $userId)
            ->where('anio', $anio)
            ->orderBy('fecha')
            ->get();

        return $rows->groupBy(fn ($row) => (int) date('n', strtotime($row->fecha)))
            ->map(function (Collection $monthRows, int $mes) use ($dek) {
                $totals = $this->aggregateTripRows($monthRows, $dek);

                return (object) [
                    'mes' => $mes,
                    'total_indrive' => $totals['indrive'],
                    'total_otros' => $totals['otros_viajes'],
                    'total_propinas' => $totals['propina'],
                    'total_alquiler' => $totals['alquiler'],
                ];
            })
            ->sortKeys()
            ->values();
    }

    public function chartMetrics(int $userId, int $anio, ?string $dek = null): array
    {
        $trips = DB::table('trips')->where('user_id', $userId)->where('anio', $anio)->get();
        $expenses = DB::table('expenses')->where('user_id', $userId)->where('anio', $anio)->get();

        $monthlyTrips = $trips->groupBy(fn ($row) => (int) date('n', strtotime($row->fecha)));
        $monthlyExpenses = $expenses->groupBy(fn ($row) => (int) date('n', strtotime($row->fecha)));

        $ingresos = [];
        $alquileres = [];
        $gastos = [];
        $netos = [];
        $platformTotals = ['indrive' => 0.0, 'otros' => 0.0, 'propinas' => 0.0];

        for ($m = 1; $m <= 12; $m++) {
            $tripTotals = isset($monthlyTrips[$m])
                ? $this->aggregateTripRows($monthlyTrips[$m], $dek)
                : ['indrive' => 0, 'otros_viajes' => 0, 'propina' => 0, 'alquiler' => 0];

            $expenseTotal = isset($monthlyExpenses[$m])
                ? $monthlyExpenses[$m]->sum(fn ($row) => $this->decryptExpenseRow($row, $dek)['monto'])
                : 0.0;

            $income = $tripTotals['indrive'] + $tripTotals['otros_viajes'] + $tripTotals['propina'];
            $ingresos[] = round($income, 2);
            $alquileres[] = round($tripTotals['alquiler'], 2);
            $gastos[] = round($expenseTotal, 2);
            $netos[] = round($income - $tripTotals['alquiler'] - $expenseTotal, 2);
        }

        $yearTripTotals = $this->aggregateTripRows($trips, $dek);
        $platformTotals['indrive'] = $yearTripTotals['indrive'];
        $platformTotals['otros'] = $yearTripTotals['otros_viajes'];
        $platformTotals['propinas'] = $yearTripTotals['propina'];

        $totalIngresos = $platformTotals['indrive'] + $platformTotals['otros'] + $platformTotals['propinas'];
        $totalAlquiler = $yearTripTotals['alquiler'];
        $totalGastos = $expenses->sum(fn ($row) => $this->decryptExpenseRow($row, $dek)['monto']);

        return [
            'ingresos' => $ingresos,
            'alquileres' => $alquileres,
            'gastos' => $gastos,
            'netos' => $netos,
            'plataformas' => [
                'InDrive' => round($platformTotals['indrive'], 2),
                'Otros' => round($platformTotals['otros'], 2),
                'Propinas' => round($platformTotals['propinas'], 2),
            ],
            'totals' => [
                'ingresos' => round($totalIngresos, 2),
                'alquiler' => round($totalAlquiler, 2),
                'gastos' => round($totalGastos, 2),
                'neto' => round($totalIngresos - $totalAlquiler - $totalGastos, 2),
            ],
        ];
    }

    public function aggregateTripRows(Collection $rows, ?string $dek = null): array
    {
        $totals = [
            'indrive' => 0.0,
            'otros_viajes' => 0.0,
            'propina' => 0.0,
            'alquiler' => 0.0,
        ];

        foreach ($rows as $row) {
            $amounts = $this->decryptTripRow($row, $dek);
            foreach ($totals as $key => $value) {
                $totals[$key] += $amounts[$key];
            }
        }

        return $totals;
    }

    public function migrateTripRow(object $row, string $dek): array
    {
        return [
            'encrypted_payload' => $this->encryption->encryptPayload($dek, [
                'indrive' => (float) $row->indrive,
                'otros_viajes' => (float) $row->otros_viajes,
                'propina' => (float) $row->propina,
                'alquiler' => (float) $row->alquiler,
            ]),
            'encryption_version' => 1,
            'indrive' => 0,
            'otros_viajes' => 0,
            'propina' => 0,
            'alquiler' => 0,
        ];
    }

    public function migrateExpenseRow(object $row, string $dek): array
    {
        return [
            'encrypted_payload' => $this->encryption->encryptPayload($dek, [
                'monto' => (float) $row->monto,
                'descripcion' => $row->descripcion,
            ]),
            'encryption_version' => 1,
            'monto' => 0,
            'descripcion' => null,
        ];
    }

    private function requireSessionDek(): string
    {
        $dek = $this->encryption->getDekFromSession(app('session.store'));

        if (! $dek) {
            throw new \RuntimeException('La sesión de cifrado expiró. Vuelva a iniciar sesión.');
        }

        return $dek;
    }
}
