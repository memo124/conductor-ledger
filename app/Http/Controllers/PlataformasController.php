<?php

namespace App\Http\Controllers;

use App\Models\Platform;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlataformasController extends Controller
{
    public function index(): View
    {
        return view('maestros.plataformas.index');
    }

    public function getDatatableServerSide(Request $request): JsonResponse
    {
        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = min((int) $request->input('length', 10), 100);
        $search = trim((string) $request->input('search.value', ''));

        $query = Platform::query()
            ->when($search !== '', fn ($q) => $q->where('name', 'ilike', "%{$search}%"));

        $recordsTotal = Platform::query()->count();
        $recordsFiltered = (clone $query)->count();
        $rows = $query->orderBy('name')->offset($start)->limit($length)->get();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows->map(fn ($r) => [
                'id' => $r->id,
                'name' => $r->name,
                'is_active' => $r->is_active ? 'Activo' : 'Inactivo',
                'is_active_bool' => $r->is_active,
            ]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:platforms,name'],
        ]);

        $platform = Platform::query()->create([
            'name' => $validated['name'],
            'is_active' => true,
        ]);

        return response()->json(['success' => true, 'message' => 'Plataforma creada.', 'data' => $platform]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $platform = Platform::query()->findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:platforms,name,'.$id],
            'is_active' => ['required', 'boolean'],
        ]);

        $platform->update($validated);

        return response()->json(['success' => true, 'message' => 'Plataforma actualizada.', 'data' => $platform]);
    }
}
