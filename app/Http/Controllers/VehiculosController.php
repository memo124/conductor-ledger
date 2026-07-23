<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FormatsMoney;
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
    use FormatsMoney;

    public function __construct(private readonly VehicleRentalService $rentalService) {}

    public function index(): View
    {
        return view('vehiculos.index', [
            'vehicleKinds' => Vehicle::kinds(),
        ]);
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
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('alias', 'ilike', "%{$search}%")
                        ->orWhere('brand', 'ilike', "%{$search}%")
                        ->orWhere('model', 'ilike', "%{$search}%")
                        ->orWhere('color', 'ilike', "%{$search}%");
                });
            });

        $recordsTotal = Vehicle::query()->where('user_id', $userId)->count();
        $recordsFiltered = (clone $query)->count();

        $rows = $query->orderByDesc('id')->offset($start)->limit($length)->get();

        $periodLabels = [
            'daily' => ui('pages.vehiculos.period_daily'),
            'weekly' => ui('pages.vehiculos.period_weekly'),
            'biweekly' => ui('pages.vehiculos.period_biweekly'),
            'monthly' => ui('pages.vehiculos.period_monthly'),
        ];

        $data = $rows->map(fn (Vehicle $v) => [
            'id' => $v->id,
            'alias' => $v->alias,
            'display_label' => $v->displayLabel(),
            'vehicle_kind' => ui('pages.vehiculos.kind_'.$v->vehicle_kind),
            'vehicle_kind_code' => $v->vehicle_kind,
            'brand' => $v->brand ?? '—',
            'model' => $v->model ?? '—',
            'model_year' => $v->model_year ?? '—',
            'color' => $v->color ?? '—',
            'brand_raw' => $v->brand,
            'model_raw' => $v->model,
            'model_year_raw' => $v->model_year,
            'color_raw' => $v->color,
            'notes_raw' => $v->notes,
            'ownership_type' => $v->ownershipType?->name,
            'ownership_type_id' => $v->ownership_type_id,
            'ownership_requires_fee' => $this->rentalService->ownershipRequiresFee($v->ownershipType?->name),
            'ownership_is_rented' => $this->rentalService->ownershipRequiresFee($v->ownershipType?->name),
            'rental_fee_daily' => $this->money((float) $v->rental_fee_daily),
            'rental_fee_raw' => (float) $v->rental_fee_daily,
            'rental_period' => $periodLabels[$v->rental_period ?? 'daily'] ?? ui('pages.vehiculos.period_daily'),
            'rental_period_code' => $v->rental_period ?? 'daily',
            'quota_percentage' => number_format((float) $v->quota_percentage, 2),
            'quota_percentage_raw' => (float) $v->quota_percentage,
            'quota_reserve_amount' => $this->money((float) $v->quota_reserve_amount),
            'quota_reserve_raw' => (float) $v->quota_reserve_amount,
            'notes' => $v->notes,
            'is_active' => $v->is_active ? ui('common.active') : ui('common.inactive'),
            'is_active_bool' => $v->is_active,
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
            'requires_fee' => $this->rentalService->ownershipRequiresFee($type->name),
            'is_rented' => $this->rentalService->ownershipRequiresFee($type->name),
        ]));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateVehicle($request);

        $ownershipType = VehicleOwnershipType::query()->findOrFail($validated['ownership_type_id']);
        $requiresFee = $this->rentalService->ownershipRequiresFee($ownershipType->name);

        if ($requiresFee) {
            $request->validate([
                'rental_fee_daily' => ['required', 'numeric', 'min:0.01'],
                'rental_period' => ['required', 'in:daily,weekly,biweekly,monthly'],
            ], $this->validationMessages());
            $validated = array_merge($validated, $request->only(['rental_fee_daily', 'rental_period']));
        }

        $vehicle = Vehicle::query()->create(array_merge($validated, [
            'user_id' => Auth::id(),
            'rental_fee_daily' => $requiresFee ? ($validated['rental_fee_daily'] ?? 0) : 0,
            'rental_period' => $requiresFee ? ($validated['rental_period'] ?? 'daily') : 'daily',
            'is_active' => true,
        ]));

        return response()->json([
            'success' => true,
            'message' => ui('pages.vehiculos.saved'),
            'data' => $vehicle->load('ownershipType'),
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $vehicle = Vehicle::query()
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        $this->normalizeBooleanFields($request, ['is_active']);

        $validated = $this->validateVehicle($request);
        $validated['is_active'] = (bool) $request->input('is_active');

        $ownershipType = VehicleOwnershipType::query()->findOrFail($validated['ownership_type_id']);
        $requiresFee = $this->rentalService->ownershipRequiresFee($ownershipType->name);

        if ($requiresFee) {
            $request->validate([
                'rental_fee_daily' => ['required', 'numeric', 'min:0.01'],
                'rental_period' => ['required', 'in:daily,weekly,biweekly,monthly'],
            ], $this->validationMessages());
            $validated = array_merge($validated, $request->only(['rental_fee_daily', 'rental_period']));
        }

        $vehicle->update(array_merge($validated, [
            'rental_fee_daily' => $requiresFee ? ($validated['rental_fee_daily'] ?? 0) : 0,
            'rental_period' => $requiresFee ? ($validated['rental_period'] ?? 'daily') : 'daily',
        ]));

        return response()->json([
            'success' => true,
            'message' => ui('pages.vehiculos.updated'),
            'data' => $vehicle->fresh('ownershipType'),
        ]);
    }

    private function validateVehicle(Request $request): array
    {
        return $request->validate([
            'ownership_type_id' => ['required', 'integer', 'exists:vehicle_ownership_types,id'],
            'alias' => ['required', 'string', 'max:40'],
            'vehicle_kind' => ['required', 'in:'.implode(',', array_keys(Vehicle::kinds()))],
            'brand' => ['nullable', 'string', 'max:60'],
            'model' => ['nullable', 'string', 'max:60'],
            'model_year' => ['nullable', 'integer', 'min:1980', 'max:'.(date('Y') + 1)],
            'color' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'rental_fee_daily' => ['nullable', 'numeric', 'min:0'],
            'rental_period' => ['nullable', 'in:daily,weekly,biweekly,monthly'],
            'quota_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'quota_reserve_amount' => ['nullable', 'numeric', 'min:0'],
        ], $this->validationMessages());
    }

    private function normalizeBooleanFields(Request $request, array $fields): void
    {
        foreach ($fields as $field) {
            if (! $request->has($field)) {
                continue;
            }

            $value = $request->input($field);

            if (is_bool($value)) {
                $request->merge([$field => $value ? '1' : '0']);
            } elseif (in_array($value, ['true', 'false'], true)) {
                $request->merge([$field => $value === 'true' ? '1' : '0']);
            }
        }
    }

    private function validationMessages(): array
    {
        return [
            'ownership_type_id.required' => ui('pages.vehiculos.validation_ownership'),
            'alias.required' => ui('pages.vehiculos.validation_alias'),
            'rental_fee_daily.required' => ui('pages.vehiculos.validation_quota'),
            'rental_fee_daily.min' => ui('pages.vehiculos.validation_quota_min'),
            'rental_period.required' => ui('pages.vehiculos.validation_period'),
            'rental_period.in' => ui('pages.vehiculos.validation_period_invalid'),
            'is_active.required' => ui('pages.vehiculos.validation_status'),
        ];
    }
}
