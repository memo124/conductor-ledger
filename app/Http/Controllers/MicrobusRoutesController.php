<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FormatsMoney;
use App\Http\Controllers\Concerns\ResolvesClientReference;
use App\Models\MicrobusPassenger;
use App\Models\MicrobusPassengerPayment;
use App\Models\MicrobusRoute;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MicrobusRoutesController extends Controller
{
    use FormatsMoney;
    use ResolvesClientReference;

    public function index(): View
    {
        $vehicles = Vehicle::query()
            ->where('user_id', Auth::id())
            ->where('is_active', true)
            ->orderBy('alias')
            ->get(['id', 'alias', 'brand', 'model', 'vehicle_kind']);

        return view('microbus-rutas.index', compact('vehicles'));
    }

    public function getDatatableServerSide(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = min((int) $request->input('length', 10), 100);
        $search = trim((string) $request->input('search.value', ''));

        $query = MicrobusRoute::query()
            ->with(['vehicle'])
            ->withCount('passengers')
            ->where('user_id', $userId)
            ->when($search !== '', fn ($q) => $q->where('name', 'ilike', "%{$search}%"));

        $recordsTotal = MicrobusRoute::query()->where('user_id', $userId)->count();
        $recordsFiltered = (clone $query)->count();
        $rows = $query->orderByDesc('id')->offset($start)->limit($length)->get();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows->map(fn (MicrobusRoute $route) => [
                'id' => $route->id,
                'name' => $route->name,
                'vehicle_label' => $route->vehicle?->displayLabel() ?? '—',
                'vehicle_id' => $route->vehicle_id,
                'passengers_count' => $route->passengers_count,
                'notes' => $route->notes,
                'is_active' => $route->is_active ? ui('common.active') : ui('common.inactive'),
                'is_active_bool' => $route->is_active,
            ]),
        ]);
    }

    public function show(int $id, Request $request): JsonResponse
    {
        $periodYear = (int) $request->query('period_year', date('Y'));
        $periodMonth = (int) $request->query('period_month', date('n'));

        $route = MicrobusRoute::query()
            ->with([
                'vehicle',
                'passengers' => fn ($q) => $q->with(['client', 'dependent', 'payments' => fn ($p) => $p
                    ->where('period_year', $periodYear)
                    ->where('period_month', $periodMonth)]),
            ])
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        $passengers = $route->passengers->sortBy('sort_order')->values()->map(function (MicrobusPassenger $passenger) use ($periodYear, $periodMonth) {
            $payment = $passenger->payments->first();
            $amountDue = $payment?->amount_due ?? $passenger->monthly_fee;

            return [
                'id' => $passenger->id,
                'name' => $passenger->resolvedName(),
                'client_id' => $passenger->client_id,
                'client_dependent_id' => $passenger->client_dependent_id,
                'display_name' => $passenger->display_name,
                'monthly_fee' => $this->money((float) $passenger->monthly_fee),
                'monthly_fee_raw' => (float) $passenger->monthly_fee,
                'pickup_notes' => $passenger->pickup_notes,
                'is_active' => $passenger->is_active,
                'payment' => [
                    'period_year' => $periodYear,
                    'period_month' => $periodMonth,
                    'amount_due' => $this->money((float) $amountDue),
                    'amount_due_raw' => (float) $amountDue,
                    'is_paid' => (bool) ($payment?->is_paid ?? false),
                    'paid_at' => $payment?->paid_at?->format('Y-m-d H:i'),
                ],
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $route->id,
                'name' => $route->name,
                'vehicle_id' => $route->vehicle_id,
                'vehicle_label' => $route->vehicle?->displayLabel(),
                'notes' => $route->notes,
                'is_active' => $route->is_active,
                'passengers' => $passengers,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'name' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->assertVehicleOwnership((int) $validated['vehicle_id']);

        $route = MicrobusRoute::query()->create(array_merge($validated, [
            'user_id' => Auth::id(),
            'is_active' => true,
        ]));

        return response()->json([
            'success' => true,
            'message' => ui('pages.microbus_rutas.saved'),
            'data' => $route->load('vehicle'),
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $route = MicrobusRoute::query()->where('user_id', Auth::id())->findOrFail($id);

        $validated = $request->validate([
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'name' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
        ]);

        $this->assertVehicleOwnership((int) $validated['vehicle_id']);
        $route->update($validated);

        return response()->json([
            'success' => true,
            'message' => ui('pages.microbus_rutas.updated'),
            'data' => $route->fresh('vehicle'),
        ]);
    }

    public function storePassenger(Request $request, int $routeId): JsonResponse
    {
        $route = MicrobusRoute::query()->where('user_id', Auth::id())->findOrFail($routeId);

        $validated = $request->validate([
            'monthly_fee' => ['required', 'numeric', 'min:0'],
            'pickup_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $clientRef = $this->resolveClientReference($request, Auth::id(), true, 'display_name');

        $passenger = $route->passengers()->create(array_merge($clientRef, [
            'monthly_fee' => $validated['monthly_fee'],
            'pickup_notes' => $validated['pickup_notes'] ?? null,
            'sort_order' => (int) $route->passengers()->max('sort_order') + 1,
            'is_active' => true,
        ]));

        return response()->json([
            'success' => true,
            'message' => ui('pages.microbus_rutas.passenger_saved'),
            'data' => $passenger->load(['client', 'dependent']),
        ]);
    }

    public function updatePassenger(Request $request, int $routeId, int $passengerId): JsonResponse
    {
        $route = MicrobusRoute::query()->where('user_id', Auth::id())->findOrFail($routeId);
        $passenger = $route->passengers()->findOrFail($passengerId);

        $validated = $request->validate([
            'monthly_fee' => ['required', 'numeric', 'min:0'],
            'pickup_notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['required', 'boolean'],
        ]);

        $clientRef = $this->resolveClientReference($request, Auth::id(), true, 'display_name');

        $passenger->update(array_merge($clientRef, $validated));

        return response()->json([
            'success' => true,
            'message' => ui('pages.microbus_rutas.passenger_updated'),
            'data' => $passenger->fresh(['client', 'dependent']),
        ]);
    }

    public function upsertPayment(Request $request, int $routeId, int $passengerId): JsonResponse
    {
        $route = MicrobusRoute::query()->where('user_id', Auth::id())->findOrFail($routeId);
        $passenger = $route->passengers()->findOrFail($passengerId);

        $validated = $request->validate([
            'period_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'period_month' => ['required', 'integer', 'min:1', 'max:12'],
            'amount_due' => ['required', 'numeric', 'min:0'],
            'is_paid' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $payment = MicrobusPassengerPayment::query()->updateOrCreate(
            [
                'microbus_passenger_id' => $passenger->id,
                'period_year' => $validated['period_year'],
                'period_month' => $validated['period_month'],
            ],
            [
                'amount_due' => $validated['amount_due'],
                'is_paid' => $validated['is_paid'],
                'paid_at' => $validated['is_paid'] ? now() : null,
                'notes' => $validated['notes'] ?? null,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => ui('pages.microbus_rutas.payment_saved'),
            'data' => $payment,
        ]);
    }

    private function assertVehicleOwnership(int $vehicleId): void
    {
        $owned = Vehicle::query()
            ->where('user_id', Auth::id())
            ->where('id', $vehicleId)
            ->exists();

        if (! $owned) {
            abort(403);
        }
    }
}
