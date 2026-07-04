<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoriasGastoController extends Controller
{
    public function index(): View
    {
        return view('maestros.categorias-gasto.index');
    }

    public function getDatatableServerSide(Request $request): JsonResponse
    {
        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = min((int) $request->input('length', 10), 100);
        $search = trim((string) $request->input('search.value', ''));

        $query = ExpenseCategory::query()
            ->when($search !== '', fn ($q) => $q->where('name', 'ilike', "%{$search}%"));

        $recordsTotal = ExpenseCategory::query()->count();
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
            'name' => ['required', 'string', 'max:50', 'unique:expense_categories,name'],
        ]);

        $category = ExpenseCategory::query()->create($validated);

        return response()->json(['success' => true, 'message' => 'Categoría creada.', 'data' => $category]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $category = ExpenseCategory::query()->findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:expense_categories,name,'.$id],
        ]);

        $category->update($validated);

        return response()->json(['success' => true, 'message' => 'Categoría actualizada.', 'data' => $category]);
    }
}
