<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\VehicleOwnershipType;
use App\Services\VehicleRentalService;
use App\Support\Select2Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class VehiculosController extends Controller
{
    public function __construct(private readonly VehicleRentalService $rentalService) {}

    public function index(): View
    {
        return view('vehiculos.index');
    }

    public function getDatatableServerSide(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = min((int) $request->input('length', 10), 100);
        $search = trim((string) $request->input('search.value', ''));

        $query = Vehicle::query()
            ->with('ownershipType')
            ->where('user_id', $userId)
            ->when($search !== '', fn ($q) => $q->where('plate_number', 'ilike', "%{$search}%"));

        $recordsTotal = Vehicle::query()->where('user_id', $userId)->count();
        $recordsFiltered = (clone $query)->count();

        $rows = $query->orderByDesc('id')->offset($start)->limit($length)->get();

        $periodLabels = ['daily' => 'Diario', 'weekly' => 'Semanal', 'monthly' => 'Mensual'];

        $data = $rows->map(fn (Vehicle $v) => [
            'id' => $v->id,
            'plate_number' => $v->plate_number,
            'ownership_type' => $v->ownershipType?->name,
            'rental_fee_daily' => number_format((float) $v->rental_fee_daily, 2),
            'rental_period' => $periodLabels[$v->rental_period ?? 'daily'] ?? 'Diario',
            'is_active' => $v->is_active ? 'Activo' : 'Inactivo',
        ]);

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function select2Paginated(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('q', ''));
        $page = max(1, (int) $request->query('page', 1));

        $paginator = VehicleOwnershipType::query()
            ->when($search !== '', fn ($q) => $q->where('name', 'ilike', "%{$search}%"))
            ->orderBy('name')
            ->paginate(15, ['*'], 'page', $page);

        return response()->json(Select2Response::fromPaginator($paginator, fn ($type) => [
            'id' => $type->id,
            'text' => $type->name,
        ]));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ownership_type_id' => ['required', 'integer', 'exists:vehicle_ownership_types,id'],
            'plate_number' => ['required', 'string', 'max:15'],
            'rental_fee_daily' => ['nullable', 'numeric', 'min:0'],
            'rental_period' => ['nullable', 'in:daily,weekly,monthly'],
        ]);

        $ownershipType = VehicleOwnershipType::query()->findOrFail($validated['ownership_type_id']);
        $isRented = strtoupper($ownershipType->name) === 'ALQUILADO';

        $vehicle = Vehicle::query()->create([
            'user_id' => Auth::id(),
            'ownership_type_id' => $validated['ownership_type_id'],
            'plate_number' => $validated['plate_number'],
            'rental_fee_daily' => $isRented ? ($validated['rental_fee_daily'] ?? 0) : 0,
            'rental_period' => $isRented ? ($validated['rental_period'] ?? 'daily') : 'daily',
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Vehículo registrado.',
            'data' => $vehicle->load('ownershipType'),
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $vehicle = Vehicle::query()
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        $validated = $request->validate([
            'ownership_type_id' => ['required', 'integer', 'exists:vehicle_ownership_types,id'],
            'plate_number' => ['required', 'string', 'max:15'],
            'rental_fee_daily' => ['nullable', 'numeric', 'min:0'],
            'rental_period' => ['nullable', 'in:daily,weekly,monthly'],
            'is_active' => ['required', 'boolean'],
        ]);

        $ownershipType = VehicleOwnershipType::query()->findOrFail($validated['ownership_type_id']);
        $isRented = strtoupper($ownershipType->name) === 'ALQUILADO';

        $vehicle->update([
            'ownership_type_id' => $validated['ownership_type_id'],
            'plate_number' => $validated['plate_number'],
            'rental_fee_daily' => $isRented ? ($validated['rental_fee_daily'] ?? 0) : 0,
            'rental_period' => $isRented ? ($validated['rental_period'] ?? 'daily') : 'daily',
            'is_active' => $validated['is_active'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Vehículo actualizado.',
            'data' => $vehicle->fresh('ownershipType'),
        ]);
    }
}
