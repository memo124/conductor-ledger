<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use App\Models\Vehicle;
use App\Services\FinancialRecordService;
use App\Services\VehicleRentalService;
use App\Services\YearlyCounterService;
use App\Support\Select2Response;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ViajesController extends Controller
{
    public function __construct(
        private readonly YearlyCounterService $counterService,
        private readonly VehicleRentalService $rentalService,
        private readonly FinancialRecordService $financialRecords,
    ) {}

    public function index(): View
    {
        return view('viajes.index');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'vehicle_id' => ['required', 'integer'],
            'fecha' => ['required', 'date'],
            'indrive' => ['nullable', 'numeric', 'min:0'],
            'otros_viajes' => ['nullable', 'numeric', 'min:0'],
            'propina' => ['nullable', 'numeric', 'min:0'],
            'alquiler' => ['nullable', 'numeric', 'min:0'],
        ]);

        $userId = Auth::id();
        $vehicle = Vehicle::query()
            ->where('id', $validated['vehicle_id'])
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->with('ownershipType')
            ->firstOrFail();

        $alquiler = (float) ($validated['alquiler'] ?? 0);
        $this->rentalService->validateTripRental($vehicle, $alquiler);

        $fecha = Carbon::parse($validated['fecha']);
        $anio = (int) $fecha->format('Y');

        $trip = DB::transaction(function () use ($validated, $userId, $vehicle, $fecha, $anio, $alquiler) {
            $tripNumber = $this->counterService->nextTripNumber($userId, $anio);

            return Trip::query()->create([
                'uuid' => (string) Str::uuid(),
                'user_id' => $userId,
                'vehicle_id' => $vehicle->id,
                'anio' => $anio,
                'trip_number' => $tripNumber,
                'fecha' => $fecha->toDateString(),
                'dia_semana' => $fecha->locale('es')->isoFormat('dddd'),
                ...$this->financialRecords->encryptTripPayload([
                    'indrive' => $validated['indrive'] ?? 0,
                    'otros_viajes' => $validated['otros_viajes'] ?? 0,
                    'propina' => $validated['propina'] ?? 0,
                    'alquiler' => $alquiler,
                ]),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Viaje registrado correctamente.',
            'data' => $trip,
        ]);
    }

    public function getRentalSuggestion(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'vehicle_id' => ['required', 'integer'],
            'fecha' => ['nullable', 'date'],
        ]);

        $vehicle = Vehicle::query()
            ->where('id', $validated['vehicle_id'])
            ->where('user_id', Auth::id())
            ->with('ownershipType')
            ->firstOrFail();

        $fecha = isset($validated['fecha']) ? Carbon::parse($validated['fecha']) : Carbon::today();

        return response()->json([
            'success' => true,
            'data' => $this->rentalService->vehicleMeta($vehicle, $fecha),
        ]);
    }

    public function select2Paginated(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('q', ''));
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 15;

        $paginator = Vehicle::query()
            ->where('user_id', Auth::id())
            ->where('is_active', true)
            ->with('ownershipType')
            ->when($search !== '', fn ($q) => $q->where('plate_number', 'ilike', "%{$search}%"))
            ->orderBy('plate_number')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json(Select2Response::fromPaginator($paginator, function (Vehicle $vehicle) {
            return [
                'id' => $vehicle->id,
                'text' => $vehicle->plate_number.' — '.($vehicle->ownershipType?->name ?? 'N/A'),
            ];
        }));
    }

    public function getDatatableServerSide(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = min((int) $request->input('length', 10), 100);
        $search = trim((string) $request->input('search.value', ''));

        $baseQuery = DB::table('trips')
            ->where('user_id', $userId)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('dia_semana', 'ilike', "%{$search}%")
                        ->orWhereRaw('CAST(trip_number AS TEXT) ILIKE ?', ["%{$search}%"])
                        ->orWhereRaw('CAST(fecha AS TEXT) ILIKE ?', ["%{$search}%"]);
                });
            });

        $recordsTotal = DB::table('trips')->where('user_id', $userId)->count();
        $recordsFiltered = (clone $baseQuery)->count();

        $rows = $baseQuery
            ->orderByDesc('fecha')
            ->orderByDesc('trip_number')
            ->offset($start)
            ->limit($length)
            ->get();

        $vehiclePlates = Vehicle::query()
            ->where('user_id', $userId)
            ->pluck('plate_number', 'id');

        $data = $rows->map(function ($row) use ($vehiclePlates) {
            $amounts = $this->financialRecords->decryptTripRow($row);
            $ingresos = $amounts['indrive'] + $amounts['otros_viajes'] + $amounts['propina'];
            $neto = $ingresos - $amounts['alquiler'];

            return [
                'trip_number' => $row->trip_number,
                'fecha' => $row->fecha,
                'dia_semana' => $row->dia_semana,
                'vehicle' => $vehiclePlates[$row->vehicle_id] ?? 'N/A',
                'indrive' => number_format($amounts['indrive'], 2),
                'otros_viajes' => number_format($amounts['otros_viajes'], 2),
                'propina' => number_format($amounts['propina'], 2),
                'alquiler' => number_format($amounts['alquiler'], 2),
                'ingresos' => number_format($ingresos, 2),
                'neto' => number_format($neto, 2),
            ];
        });

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function getComparativaMensual(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $anio = (int) $request->query('anio', date('Y'));

        $rows = $this->financialRecords->tripComparativaByMonth($userId, $anio);

        return response()->json(['success' => true, 'data' => $rows]);
    }
}
