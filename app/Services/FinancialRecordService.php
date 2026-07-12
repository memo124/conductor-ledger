<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FinancialRecordService
{
    public function __construct(
        private readonly EncryptionService $encryption,
    ) {}

    public function encryptTripPayload(array $amounts, ?int $ownerUserId = null): array
    {
        $dek = $ownerUserId !== null && $ownerUserId !== (int) Auth::id()
            ? $this->dekForUser($ownerUserId)
            : $this->requireSessionDek();

        $payload = [
            'monto_bruto' => (float) ($amounts['monto_bruto'] ?? 0),
            'comision_app' => (float) ($amounts['comision_app'] ?? 0),
            'monto_cobrado' => (float) ($amounts['monto_cobrado'] ?? 0),
            'propina' => (float) ($amounts['propina'] ?? 0),
            'alquiler' => (float) ($amounts['alquiler'] ?? 0),
            'porcentaje_cuota' => (float) ($amounts['porcentaje_cuota'] ?? 0),
            'registration_mode' => $amounts['registration_mode'] ?? 'per_trip',
        ];

        return [
            'encrypted_payload' => $this->encryption->encryptPayload($dek, $payload),
            'encryption_version' => 2,
            'indrive' => 0,
            'otros_viajes' => 0,
            'propina' => 0,
            'alquiler' => 0,
            'monto_bruto' => 0,
            'comision_app' => 0,
            'monto_cobrado' => 0,
            'porcentaje_cuota' => 0,
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
        $version = (int) ($row->encryption_version ?? 0);

        if ($version >= 2 && ! empty($row->encrypted_payload)) {
            $payload = $this->encryption->decryptPayload(
                $dek ?? $this->requireSessionDek(),
                $row->encrypted_payload
            );

            return [
                'monto_bruto' => (float) ($payload['monto_bruto'] ?? 0),
                'comision_app' => (float) ($payload['comision_app'] ?? 0),
                'monto_cobrado' => (float) ($payload['monto_cobrado'] ?? 0),
                'propina' => (float) ($payload['propina'] ?? 0),
                'alquiler' => (float) ($payload['alquiler'] ?? 0),
                'porcentaje_cuota' => (float) ($payload['porcentaje_cuota'] ?? 0),
                'registration_mode' => $payload['registration_mode'] ?? ($row->registration_mode ?? 'per_trip'),
            ];
        }

        if ($version === 1 && ! empty($row->encrypted_payload)) {
            $payload = $this->encryption->decryptPayload(
                $dek ?? $this->requireSessionDek(),
                $row->encrypted_payload
            );

            $indrive = (float) ($payload['indrive'] ?? 0);
            $otros = (float) ($payload['otros_viajes'] ?? 0);

            return [
                'monto_bruto' => $indrive + $otros,
                'comision_app' => 0,
                'monto_cobrado' => 0,
                'propina' => (float) ($payload['propina'] ?? 0),
                'alquiler' => (float) ($payload['alquiler'] ?? 0),
                'porcentaje_cuota' => 0,
                'registration_mode' => 'daily',
            ];
        }

        $indrive = (float) ($row->indrive ?? 0);
        $otros = (float) ($row->otros_viajes ?? 0);
        $montoBruto = (float) ($row->monto_bruto ?? 0);
        $montoCobrado = (float) ($row->monto_cobrado ?? 0);

        if ($montoBruto <= 0 && ($indrive > 0 || $otros > 0)) {
            $montoBruto = $indrive + $otros;
        }

        return [
            'monto_bruto' => $montoBruto,
            'comision_app' => (float) ($row->comision_app ?? 0),
            'monto_cobrado' => $montoCobrado,
            'propina' => (float) ($row->propina ?? 0),
            'alquiler' => (float) ($row->alquiler ?? 0),
            'porcentaje_cuota' => (float) ($row->porcentaje_cuota ?? 0),
            'registration_mode' => $row->registration_mode ?? 'per_trip',
        ];
    }

    public function tripIngresos(array $amounts): float
    {
        $mode = $amounts['registration_mode'] ?? 'per_trip';

        if (in_array($mode, ['daily', 'monthly'], true)) {
            return (float) ($amounts['monto_bruto'] ?? 0)
                - (float) ($amounts['comision_app'] ?? 0)
                + (float) ($amounts['propina'] ?? 0);
        }

        return (float) ($amounts['monto_cobrado'] ?? 0)
            + (float) ($amounts['propina'] ?? 0);
    }

    public function tripNeto(array $amounts): float
    {
        return $this->tripIngresos($amounts) - (float) ($amounts['alquiler'] ?? 0);
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
                    'total_ingresos' => $totals['ingresos'],
                    'total_comision' => $totals['comision_app'],
                    'total_propinas' => $totals['propina'],
                    'total_alquiler' => $totals['alquiler'],
                    'total_indrive' => $totals['ingresos'],
                    'total_otros' => 0,
                ];
            })
            ->sortKeys()
            ->values();
    }

    public function chartMetrics(int $userId, int $anio, ?string $dek = null): array
    {
        $trips = DB::table('trips as t')
            ->leftJoin('platforms as p', 'p.id', '=', 't.platform_id')
            ->where('t.user_id', $userId)
            ->where('t.anio', $anio)
            ->select(['t.*', 'p.name as platform_name'])
            ->get();

        $expenses = DB::table('expenses')->where('user_id', $userId)->where('anio', $anio)->get();

        $monthlyTrips = $trips->groupBy(fn ($row) => (int) date('n', strtotime($row->fecha)));
        $monthlyExpenses = $expenses->groupBy(fn ($row) => (int) date('n', strtotime($row->fecha)));

        $ingresos = [];
        $alquileres = [];
        $gastos = [];
        $netos = [];
        $platformTotals = [];

        for ($m = 1; $m <= 12; $m++) {
            $tripTotals = isset($monthlyTrips[$m])
                ? $this->aggregateTripRows($monthlyTrips[$m], $dek)
                : ['ingresos' => 0, 'comision_app' => 0, 'propina' => 0, 'alquiler' => 0];

            $expenseTotal = isset($monthlyExpenses[$m])
                ? $monthlyExpenses[$m]->sum(fn ($row) => $this->decryptExpenseRow($row, $dek)['monto'])
                : 0.0;

            $income = $tripTotals['ingresos'];
            $ingresos[] = round($income, 2);
            $alquileres[] = round($tripTotals['alquiler'], 2);
            $gastos[] = round($expenseTotal, 2);
            $netos[] = round($income - $tripTotals['alquiler'] - $expenseTotal, 2);
        }

        foreach ($trips as $row) {
            $amounts = $this->decryptTripRow($row, $dek);
            $label = $row->platform_name ?? 'Otros';
            $platformTotals[$label] = ($platformTotals[$label] ?? 0) + $this->tripIngresos($amounts);
        }

        $yearTripTotals = $this->aggregateTripRows($trips, $dek);
        $totalGastos = $expenses->sum(fn ($row) => $this->decryptExpenseRow($row, $dek)['monto']);

        $plataformas = [];
        foreach ($platformTotals as $name => $total) {
            $plataformas[$name] = round($total, 2);
        }

        if (empty($plataformas)) {
            $plataformas = ['Sin datos' => 0];
        }

        return [
            'ingresos' => $ingresos,
            'alquileres' => $alquileres,
            'gastos' => $gastos,
            'netos' => $netos,
            'plataformas' => $plataformas,
            'totals' => [
                'ingresos' => round($yearTripTotals['ingresos'], 2),
                'comision' => round($yearTripTotals['comision_app'], 2),
                'alquiler' => round($yearTripTotals['alquiler'], 2),
                'gastos' => round($totalGastos, 2),
                'neto' => round($yearTripTotals['ingresos'] - $yearTripTotals['alquiler'] - $totalGastos, 2),
            ],
        ];
    }

    public function aggregateTripRows(Collection $rows, ?string $dek = null): array
    {
        $totals = [
            'ingresos' => 0.0,
            'monto_bruto' => 0.0,
            'comision_app' => 0.0,
            'monto_cobrado' => 0.0,
            'propina' => 0.0,
            'alquiler' => 0.0,
        ];

        foreach ($rows as $row) {
            $amounts = $this->decryptTripRow($row, $dek);
            $totals['ingresos'] += $this->tripIngresos($amounts);
            $totals['monto_bruto'] += $amounts['monto_bruto'];
            $totals['comision_app'] += $amounts['comision_app'];
            $totals['monto_cobrado'] += $amounts['monto_cobrado'];
            $totals['propina'] += $amounts['propina'];
            $totals['alquiler'] += $amounts['alquiler'];
        }

        return $totals;
    }

    public function migrateTripRow(object $row, string $dek): array
    {
        return [
            'encrypted_payload' => $this->encryption->encryptPayload($dek, [
                'monto_bruto' => (float) ($row->indrive ?? 0) + (float) ($row->otros_viajes ?? 0),
                'comision_app' => 0,
                'monto_cobrado' => 0,
                'propina' => (float) $row->propina,
                'alquiler' => (float) $row->alquiler,
                'porcentaje_cuota' => 0,
                'registration_mode' => 'daily',
            ]),
            'encryption_version' => 2,
            'indrive' => 0,
            'otros_viajes' => 0,
            'propina' => 0,
            'alquiler' => 0,
            'monto_bruto' => 0,
            'comision_app' => 0,
            'monto_cobrado' => 0,
            'porcentaje_cuota' => 0,
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

    public function dekForUser(int $userId): string
    {
        if ($userId === (int) Auth::id()) {
            return $this->requireSessionDek();
        }

        $actor = Auth::user();
        if (! $actor || ! $actor->isAdmin()) {
            throw new \RuntimeException('No tiene permiso para acceder a los datos de otro usuario.');
        }

        $owner = User::query()->findOrFail($userId);

        return $this->encryption->unwrapUserDekWithMasterKey($owner);
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
