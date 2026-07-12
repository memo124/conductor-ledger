<?php

namespace App\Http\Controllers;

use App\Models\TripType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TiposViajeController extends Controller
{
    public function index(): View
    {
        return view('maestros.tipos-viaje.index');
    }

    public function getDatatableServerSide(Request $request): JsonResponse
    {
        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = min((int) $request->input('length', 10), 100);
        $search = trim((string) $request->input('search.value', ''));

        $query = TripType::query()
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('code', 'ilike', "%{$search}%");
            }));

        $recordsTotal = TripType::query()->count();
        $recordsFiltered = (clone $query)->count();
        $rows = $query->orderBy('name')->offset($start)->limit($length)->get();

        $modeLabels = [
            'per_trip' => 'Viaje',
            'daily' => 'Día',
            'monthly' => 'Mes',
        ];

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows->map(function ($r) use ($modeLabels) {
                $modes = collect($r->allowedModesList())
                    ->map(fn ($m) => $modeLabels[$m] ?? $m)
                    ->implode(', ');

                return [
                    'id' => $r->id,
                    'code' => $r->code,
                    'name' => $r->name,
                    'allowed_modes' => $modes,
                    'allowed_modes_raw' => $r->allowed_modes,
                    'is_active' => $r->is_active ? 'Activo' : 'Inactivo',
                    'is_active_bool' => $r->is_active,
                ];
            }),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:30', 'unique:trip_types,code'],
            'name' => ['required', 'string', 'max:80'],
            'allowed_modes' => ['required', 'string', 'max:100'],
        ]);

        $type = TripType::query()->create([
            ...$validated,
            'is_active' => true,
        ]);

        return response()->json(['success' => true, 'message' => 'Tipo de viaje creado.', 'data' => $type]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $type = TripType::query()->findOrFail($id);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:30', 'unique:trip_types,code,'.$id],
            'name' => ['required', 'string', 'max:80'],
            'allowed_modes' => ['required', 'string', 'max:100'],
            'is_active' => ['required', 'boolean'],
        ]);

        $type->update($validated);

        return response()->json(['success' => true, 'message' => 'Tipo de viaje actualizado.', 'data' => $type]);
    }
}
