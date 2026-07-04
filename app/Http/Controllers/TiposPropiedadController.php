<?php

namespace App\Http\Controllers;

use App\Models\VehicleOwnershipType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TiposPropiedadController extends Controller
{
    public function index(): View
    {
        return view('maestros.tipos-propiedad.index');
    }

    public function getDatatableServerSide(Request $request): JsonResponse
    {
        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = min((int) $request->input('length', 10), 100);
        $search = trim((string) $request->input('search.value', ''));

        $query = VehicleOwnershipType::query()
            ->when($search !== '', fn ($q) => $q->where('name', 'ilike', "%{$search}%"));

        $recordsTotal = VehicleOwnershipType::query()->count();
        $recordsFiltered = (clone $query)->count();
        $rows = $query->orderBy('name')->offset($start)->limit($length)->get();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows->map(fn ($r) => ['id' => $r->id, 'name' => $r->name]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:vehicle_ownership_types,name'],
        ]);

        $type = VehicleOwnershipType::query()->create($validated);

        return response()->json(['success' => true, 'message' => 'Tipo creado.', 'data' => $type]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $type = VehicleOwnershipType::query()->findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:vehicle_ownership_types,name,'.$id],
        ]);

        $type->update($validated);

        return response()->json(['success' => true, 'message' => 'Tipo actualizado.', 'data' => $type]);
    }
}
