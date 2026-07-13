<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FormatsMoney;
use App\Models\Platform;
use App\Models\Trip;
use App\Models\TripType;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\FinancialRecordService;
use App\Services\TripRegistrationService;
use App\Services\VehicleRentalService;
use App\Services\YearlyCounterService;
use App\Support\Select2Response;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ViajesController extends Controller
{
    use FormatsMoney;

    public function __construct(
        private readonly YearlyCounterService $counterService,
        private readonly VehicleRentalService $rentalService,
        private readonly FinancialRecordService $financialRecords,
        private readonly TripRegistrationService $tripRegistration,
    ) {}

    public function index(): View
    {
        $tripTypes = TripType::query()->where('is_active', true)->orderBy('name')->get();

        return view('viajes.index', [
            'tripTypes' => $tripTypes,
            'platforms' => Platform::query()->where('is_active', true)->orderBy('name')->get(),
            'tripTypesJson' => $tripTypes->map(fn ($t) => [
                'id' => $t->id,
                'code' => $t->code,
                'name' => $t->name,
                'allowed_modes' => $t->allowedModesList(),
            ])->values(),
            'isAdmin' => Auth::user()->isAdmin(),
            'conductors' => Auth::user()->isAdmin()
                ? User::query()->where('is_active', true)->orderBy('name')->get()->filter(fn (User $u) => ! $u->isAdmin())->values()
                : collect(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'vehicle_id' => ['required', 'integer'],
            'trip_type_id' => ['required', 'integer', 'exists:trip_types,id'],
            'registration_mode' => ['required', 'in:per_trip,daily,monthly'],
            'platform_id' => ['nullable', 'integer', 'exists:platforms,id'],
            'fecha' => ['required_unless:registration_mode,monthly', 'nullable', 'date', 'before_or_equal:today'],
            'period_year' => ['required_if:registration_mode,monthly', 'nullable', 'integer', 'min:2000', 'max:2100'],
            'period_month' => ['required_if:registration_mode,monthly', 'nullable', 'integer', 'min:1', 'max:12'],
            'monto_bruto' => ['nullable', 'numeric', 'min:0'],
            'comision_app' => ['nullable', 'numeric', 'min:0'],
            'monto_cobrado' => ['nullable', 'numeric', 'min:0'],
            'propina' => ['nullable', 'numeric', 'min:0'],
            'porcentaje_cuota' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'alquiler' => ['nullable', 'numeric', 'min:0'],
        ]);

        $userId = Auth::id();
        $tripType = TripType::query()->findOrFail($validated['trip_type_id']);
        $registrationMode = $validated['registration_mode'];

        if (! $tripType->allowsMode($registrationMode)) {
            throw ValidationException::withMessages([
                'registration_mode' => 'Este tipo de viaje no admite el modo seleccionado.',
            ]);
        }

        if ($tripType->code === 'PLATAFORMA') {
            if ($registrationMode !== 'daily') {
                throw ValidationException::withMessages([
                    'registration_mode' => 'Los viajes de plataforma deben registrarse como resumen del día.',
                ]);
            }
            if (empty($validated['platform_id'])) {
                throw ValidationException::withMessages([
                    'platform_id' => 'Seleccione la plataforma.',
                ]);
            }
        }

        $vehicle = Vehicle::query()
            ->where('id', $validated['vehicle_id'])
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->with('ownershipType')
            ->firstOrFail();

        $fechaInput = isset($validated['fecha'])
            ? Carbon::parse($validated['fecha'])
            : Carbon::today();

        $fecha = $this->tripRegistration->resolveFecha(
            $registrationMode,
            $fechaInput,
            $validated['period_year'] ?? null,
            $validated['period_month'] ?? null,
        );

        $this->tripRegistration->validateTripPeriod(
            $registrationMode,
            $fecha,
            $validated['period_year'] ?? null,
            $validated['period_month'] ?? null,
        );

        $this->tripRegistration->validateUniqueness(
            $userId,
            $vehicle->id,
            $tripType,
            $registrationMode,
            $validated['platform_id'] ?? null,
            $fecha,
            $validated['period_year'] ?? null,
            $validated['period_month'] ?? null,
        );

        $montoBruto = (float) ($validated['monto_bruto'] ?? 0);
        $comisionApp = (float) ($validated['comision_app'] ?? 0);
        $montoCobrado = (float) ($validated['monto_cobrado'] ?? 0);

        if (in_array($registrationMode, ['daily', 'monthly'], true)) {
            if ($montoBruto <= 0) {
                throw ValidationException::withMessages([
                    'monto_bruto' => 'Ingrese el monto bruto del período.',
                ]);
            }
            if ($comisionApp > $montoBruto) {
                throw ValidationException::withMessages([
                    'comision_app' => 'La comisión no puede ser mayor al monto bruto.',
                ]);
            }
        } else {
            if ($montoCobrado <= 0) {
                throw ValidationException::withMessages([
                    'monto_cobrado' => 'Ingrese el monto cobrado en el viaje.',
                ]);
            }
        }

        $porcentajeCuota = (float) ($validated['porcentaje_cuota'] ?? $vehicle->quota_percentage);
        $alquiler = (float) ($validated['alquiler'] ?? 0);
        $this->rentalService->validateTripRental($vehicle, $alquiler);

        $anio = (int) $fecha->format('Y');

        $amounts = [
            'monto_bruto' => $montoBruto,
            'comision_app' => $comisionApp,
            'monto_cobrado' => $montoCobrado,
            'propina' => (float) ($validated['propina'] ?? 0),
            'alquiler' => $alquiler,
            'porcentaje_cuota' => $porcentajeCuota,
            'registration_mode' => $registrationMode,
        ];

        $trip = DB::transaction(function () use ($validated, $userId, $vehicle, $tripType, $fecha, $anio, $amounts, $registrationMode) {
            $tripNumber = $this->counterService->nextTripNumber($userId, $anio);

            return Trip::query()->create([
                'uuid' => (string) Str::uuid(),
                'user_id' => $userId,
                'vehicle_id' => $vehicle->id,
                'trip_type_id' => $tripType->id,
                'platform_id' => $validated['platform_id'] ?? null,
                'registration_mode' => $registrationMode,
                'period_year' => $validated['period_year'] ?? null,
                'period_month' => $validated['period_month'] ?? null,
                'anio' => $anio,
                'trip_number' => $tripNumber,
                'fecha' => $fecha->toDateString(),
                'dia_semana' => $fecha->locale('es')->isoFormat('dddd'),
                ...$this->financialRecords->encryptTripPayload($amounts),
            ]);
        });

        $ingresos = $this->financialRecords->tripIngresos($amounts);
        $neto = $this->financialRecords->tripNeto($amounts);

        return response()->json([
            'success' => true,
            'message' => sprintf(
                'Registrado: ganaste $%s | Comisión: $%s | Cuota vehículo: $%s | Neto: $%s',
                $this->money($ingresos),
                $this->money($comisionApp),
                $this->money($alquiler),
                $this->money($neto),
            ),
            'data' => $trip,
            'summary' => [
                'ingresos' => $ingresos,
                'comision_app' => $comisionApp,
                'alquiler' => $alquiler,
                'neto' => $neto,
            ],
        ]);
    }

    public function getRentalSuggestion(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'vehicle_id' => ['required', 'integer'],
            'fecha' => ['nullable', 'date'],
            'registration_mode' => ['nullable', 'in:per_trip,daily,monthly'],
            'monto_bruto' => ['nullable', 'numeric', 'min:0'],
            'comision_app' => ['nullable', 'numeric', 'min:0'],
            'monto_cobrado' => ['nullable', 'numeric', 'min:0'],
            'porcentaje_cuota' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $vehicle = Vehicle::query()
            ->where('id', $validated['vehicle_id'])
            ->where('user_id', $this->resolveOwnerUserId($request))
            ->with('ownershipType')
            ->firstOrFail();

        $fecha = isset($validated['fecha']) ? Carbon::parse($validated['fecha']) : Carbon::today();
        $mode = $validated['registration_mode'] ?? 'per_trip';
        $base = $this->tripRegistration->baseIngreso($mode, [
            'monto_bruto' => $validated['monto_bruto'] ?? 0,
            'comision_app' => $validated['comision_app'] ?? 0,
            'monto_cobrado' => $validated['monto_cobrado'] ?? 0,
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->rentalService->vehicleMeta(
                $vehicle,
                $fecha,
                $base,
                $mode,
                isset($validated['porcentaje_cuota']) ? (float) $validated['porcentaje_cuota'] : null,
            ),
        ]);
    }

    public function select2Paginated(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('q', ''));
        $page = max(1, (int) $request->query('page', 1));

        $paginator = Vehicle::query()
            ->where('user_id', $this->resolveOwnerUserId($request))
            ->where('is_active', true)
            ->with('ownershipType')
            ->when($search !== '', fn ($q) => $q->where('plate_number', 'ilike', "%{$search}%"))
            ->orderBy('plate_number')
            ->paginate(15, ['*'], 'page', $page);

        return response()->json(Select2Response::fromPaginator($paginator, function (Vehicle $vehicle) {
            return [
                'id' => $vehicle->id,
                'text' => $vehicle->plate_number.' — '.($vehicle->ownershipType?->name ?? 'N/A'),
            ];
        }));
    }

    public function select2Platforms(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('q', ''));
        $page = max(1, (int) $request->query('page', 1));

        $paginator = Platform::query()
            ->where('is_active', true)
            ->when($search !== '', fn ($q) => $q->where('name', 'ilike', "%{$search}%"))
            ->orderBy('name')
            ->paginate(15, ['*'], 'page', $page);

        return response()->json(Select2Response::fromPaginator($paginator, fn (Platform $p) => [
            'id' => $p->id,
            'text' => $p->name,
        ]));
    }

    public function getDatatableServerSide(Request $request): JsonResponse
    {
        $userId = $this->resolveOwnerUserId($request);
        $dek = $this->financialRecords->dekForUser($userId);
        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = min((int) $request->input('length', 10), 100);
        $search = trim((string) $request->input('search.value', ''));

        $fechaDesde = $request->input('fecha_desde');
        $fechaHasta = $request->input('fecha_hasta');
        $platformId = $request->input('platform_id');
        $tripTypeId = $request->input('trip_type_id');
        $registrationMode = $request->input('registration_mode');
        $vehicleId = $request->input('vehicle_id');

        $baseQuery = DB::table('trips as t')
            ->leftJoin('trip_types as tt', 'tt.id', '=', 't.trip_type_id')
            ->leftJoin('platforms as p', 'p.id', '=', 't.platform_id')
            ->where('t.user_id', $userId)
            ->when($fechaDesde, fn ($q) => $q->where('t.fecha', '>=', $fechaDesde))
            ->when($fechaHasta, fn ($q) => $q->where('t.fecha', '<=', $fechaHasta))
            ->when($platformId, fn ($q) => $q->where('t.platform_id', $platformId))
            ->when($tripTypeId, fn ($q) => $q->where('t.trip_type_id', $tripTypeId))
            ->when($registrationMode, fn ($q) => $q->where('t.registration_mode', $registrationMode))
            ->when($vehicleId, fn ($q) => $q->where('t.vehicle_id', $vehicleId))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('t.dia_semana', 'ilike', "%{$search}%")
                        ->orWhere('tt.name', 'ilike', "%{$search}%")
                        ->orWhere('p.name', 'ilike', "%{$search}%")
                        ->orWhereRaw('CAST(t.trip_number AS TEXT) ILIKE ?', ["%{$search}%"])
                        ->orWhereRaw('CAST(t.fecha AS TEXT) ILIKE ?', ["%{$search}%"]);
                });
            });

        $recordsTotal = DB::table('trips')->where('user_id', $userId)->count();
        $recordsFiltered = (clone $baseQuery)->count();

        $rows = $baseQuery
            ->select(['t.*', 'tt.name as trip_type_name', 'p.name as platform_name'])
            ->orderByDesc('t.fecha')
            ->orderByDesc('t.trip_number')
            ->offset($start)
            ->limit($length)
            ->get();

        $vehiclePlates = Vehicle::query()
            ->where('user_id', $userId)
            ->pluck('plate_number', 'id');

        $totals = ['ingresos' => 0, 'comision_app' => 0, 'alquiler' => 0, 'neto' => 0];
        $allFiltered = (clone $baseQuery)->select(['t.*'])->get();
        foreach ($allFiltered as $row) {
            $amounts = $this->financialRecords->decryptTripRow($row, $dek);
            $totals['ingresos'] += $this->financialRecords->tripIngresos($amounts);
            $totals['comision_app'] += $amounts['comision_app'];
            $totals['alquiler'] += $amounts['alquiler'];
            $totals['neto'] += $this->financialRecords->tripNeto($amounts);
        }

        $data = $rows->map(function ($row) use ($vehiclePlates, $dek) {
            $amounts = $this->financialRecords->decryptTripRow($row, $dek);
            $ingresos = $this->financialRecords->tripIngresos($amounts);
            $neto = $this->financialRecords->tripNeto($amounts);

            return [
                'uuid' => $row->uuid,
                'trip_number' => $row->trip_number,
                'fecha' => $row->fecha,
                'dia_semana' => $row->dia_semana,
                'vehicle' => $vehiclePlates[$row->vehicle_id] ?? 'N/A',
                'trip_type' => $row->trip_type_name ?? '—',
                'platform' => $row->platform_name ?? '—',
                'registration_mode' => $this->tripRegistration->registrationModeLabel($row->registration_mode),
                'monto_bruto' => $this->moneyUsd($amounts['monto_bruto']),
                'comision_app' => $this->moneyUsd($amounts['comision_app']),
                'monto_cobrado' => $this->moneyUsd($amounts['monto_cobrado']),
                'propina' => $this->moneyUsd($amounts['propina']),
                'alquiler' => $this->moneyUsd($amounts['alquiler']),
                'ingresos' => $this->moneyUsd($ingresos),
                'neto' => $this->moneyUsd($neto),
            ];
        });

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
            'totals' => [
                'ingresos' => $this->moneyUsd($totals['ingresos']),
                'comision_app' => $this->moneyUsd($totals['comision_app']),
                'alquiler' => $this->moneyUsd($totals['alquiler']),
                'neto' => $this->moneyUsd($totals['neto']),
            ],
        ]);
    }

    public function getComparativaMensual(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $anio = (int) $request->query('anio', date('Y'));

        $rows = $this->financialRecords->tripComparativaByMonth($userId, $anio);

        return response()->json(['success' => true, 'data' => $rows]);
    }

    public function show(string $uuid): JsonResponse
    {
        $trip = $this->findOwnedTrip($uuid);
        $dek = $this->financialRecords->dekForUser((int) $trip->user_id);
        $amounts = $this->financialRecords->decryptTripRow($trip, $dek);

        return response()->json([
            'success' => true,
            'data' => [
                'uuid' => $trip->uuid,
                'trip_number' => $trip->trip_number,
                'user_id' => $trip->user_id,
                'vehicle_id' => $trip->vehicle_id,
                'vehicle_plate' => $trip->vehicle?->plate_number,
                'trip_type_id' => $trip->trip_type_id,
                'platform_id' => $trip->platform_id,
                'registration_mode' => $trip->registration_mode,
                'fecha' => $trip->fecha->format('Y-m-d'),
                'period_year' => $trip->period_year,
                'period_month' => $trip->period_month,
                'monto_bruto' => $amounts['monto_bruto'],
                'comision_app' => $amounts['comision_app'],
                'monto_cobrado' => $amounts['monto_cobrado'],
                'propina' => $amounts['propina'],
                'alquiler' => $amounts['alquiler'],
                'porcentaje_cuota' => $amounts['porcentaje_cuota'],
            ],
        ]);
    }

    public function update(Request $request, string $uuid): JsonResponse
    {
        $trip = $this->findOwnedTrip($uuid);

        $validated = $request->validate([
            'vehicle_id' => ['required', 'integer'],
            'trip_type_id' => ['required', 'integer', 'exists:trip_types,id'],
            'registration_mode' => ['required', 'in:per_trip,daily,monthly'],
            'platform_id' => ['nullable', 'integer', 'exists:platforms,id'],
            'fecha' => ['required_unless:registration_mode,monthly', 'nullable', 'date', 'before_or_equal:today'],
            'period_year' => ['required_if:registration_mode,monthly', 'nullable', 'integer', 'min:2000', 'max:2100'],
            'period_month' => ['required_if:registration_mode,monthly', 'nullable', 'integer', 'min:1', 'max:12'],
            'monto_bruto' => ['nullable', 'numeric', 'min:0'],
            'comision_app' => ['nullable', 'numeric', 'min:0'],
            'monto_cobrado' => ['nullable', 'numeric', 'min:0'],
            'propina' => ['nullable', 'numeric', 'min:0'],
            'porcentaje_cuota' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'alquiler' => ['nullable', 'numeric', 'min:0'],
        ]);

        $ownerId = (int) $trip->user_id;
        $tripType = TripType::query()->findOrFail($validated['trip_type_id']);
        $registrationMode = $validated['registration_mode'];

        if (! $tripType->allowsMode($registrationMode)) {
            throw ValidationException::withMessages([
                'registration_mode' => 'Este tipo de viaje no admite el modo seleccionado.',
            ]);
        }

        $vehicle = Vehicle::query()
            ->where('id', $validated['vehicle_id'])
            ->where('user_id', $ownerId)
            ->where('is_active', true)
            ->with('ownershipType')
            ->firstOrFail();

        $fechaInput = isset($validated['fecha']) ? Carbon::parse($validated['fecha']) : Carbon::parse($trip->fecha);
        $fecha = $this->tripRegistration->resolveFecha(
            $registrationMode,
            $fechaInput,
            $validated['period_year'] ?? null,
            $validated['period_month'] ?? null,
        );

        $this->tripRegistration->validateTripPeriod(
            $registrationMode,
            $fecha,
            $validated['period_year'] ?? null,
            $validated['period_month'] ?? null,
        );

        $this->tripRegistration->validateUniqueness(
            $ownerId,
            $vehicle->id,
            $tripType,
            $registrationMode,
            $validated['platform_id'] ?? null,
            $fecha,
            $validated['period_year'] ?? null,
            $validated['period_month'] ?? null,
            $trip->uuid,
        );

        $montoBruto = (float) ($validated['monto_bruto'] ?? 0);
        $comisionApp = (float) ($validated['comision_app'] ?? 0);
        $montoCobrado = (float) ($validated['monto_cobrado'] ?? 0);
        $alquiler = (float) ($validated['alquiler'] ?? 0);

        $this->rentalService->validateTripRental($vehicle, $alquiler);

        $amounts = [
            'monto_bruto' => $montoBruto,
            'comision_app' => $comisionApp,
            'monto_cobrado' => $montoCobrado,
            'propina' => (float) ($validated['propina'] ?? 0),
            'alquiler' => $alquiler,
            'porcentaje_cuota' => (float) ($validated['porcentaje_cuota'] ?? 0),
            'registration_mode' => $registrationMode,
        ];

        $trip->update([
            'vehicle_id' => $vehicle->id,
            'trip_type_id' => $tripType->id,
            'platform_id' => $validated['platform_id'] ?? null,
            'registration_mode' => $registrationMode,
            'period_year' => $validated['period_year'] ?? null,
            'period_month' => $validated['period_month'] ?? null,
            'fecha' => $fecha->toDateString(),
            'dia_semana' => $fecha->locale('es')->isoFormat('dddd'),
            ...$this->financialRecords->encryptTripPayload($amounts, $ownerId),
        ]);

        $ingresos = $this->financialRecords->tripIngresos($amounts);

        return response()->json([
            'success' => true,
            'message' => 'Viaje actualizado correctamente. Nuevo ingreso: '.$this->money($ingresos),
        ]);
    }

    private function resolveOwnerUserId(Request $request): int
    {
        $targetUserId = (int) $request->input('target_user_id', 0);

        if ($targetUserId > 0 && Auth::user()->isAdmin()) {
            $user = User::query()->where('id', $targetUserId)->where('is_active', true)->firstOrFail();
            if ($user->isAdmin()) {
                abort(422, 'No se pueden consultar viajes de administradores.');
            }

            return $user->id;
        }

        return (int) Auth::id();
    }

    private function findOwnedTrip(string $uuid): Trip
    {
        $trip = Trip::query()->with('vehicle')->where('uuid', $uuid)->firstOrFail();

        if ((int) $trip->user_id === (int) Auth::id()) {
            return $trip;
        }

        if (Auth::user()->isAdmin()) {
            $owner = User::query()->findOrFail($trip->user_id);
            if ($owner->isAdmin()) {
                abort(403, 'No se pueden editar viajes de administradores.');
            }

            return $trip;
        }

        abort(403);
    }
}
