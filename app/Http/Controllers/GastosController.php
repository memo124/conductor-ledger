<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Vehicle;
use App\Services\FinancialRecordService;
use App\Services\YearlyCounterService;
use App\Support\Select2Response;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class GastosController extends Controller
{
    public function __construct(
        private readonly YearlyCounterService $counterService,
        private readonly FinancialRecordService $financialRecords,
    ) {}

    public function index(): View
    {
        return view('gastos.index');
    }

    public function select2Categories(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('q', ''));
        $page = max(1, (int) $request->query('page', 1));

        $paginator = ExpenseCategory::query()
            ->when($search !== '', fn ($q) => $q->where('name', 'ilike', "%{$search}%"))
            ->orderBy('name')
            ->paginate(15, ['*'], 'page', $page);

        return response()->json(Select2Response::fromPaginator($paginator, fn ($cat) => [
            'id' => $cat->id,
            'text' => $cat->name,
        ]));
    }

    public function select2Vehicles(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('q', ''));
        $page = max(1, (int) $request->query('page', 1));

        $paginator = Vehicle::query()
            ->where('user_id', Auth::id())
            ->where('is_active', true)
            ->when($search !== '', fn ($q) => $q->where('plate_number', 'ilike', "%{$search}%"))
            ->orderBy('plate_number')
            ->paginate(15, ['*'], 'page', $page);

        return response()->json(Select2Response::fromPaginator($paginator, fn ($vehicle) => [
            'id' => $vehicle->id,
            'text' => $vehicle->plate_number,
        ]));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => ['required', 'integer', 'exists:expense_categories,id'],
            'vehicle_id' => ['nullable', 'integer'],
            'fecha' => ['required', 'date'],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'descripcion' => ['nullable', 'string', 'max:500'],
        ]);

        $userId = Auth::id();

        if (! empty($validated['vehicle_id'])) {
            Vehicle::query()
                ->where('id', $validated['vehicle_id'])
                ->where('user_id', $userId)
                ->firstOrFail();
        }

        $fecha = Carbon::parse($validated['fecha']);
        $anio = (int) $fecha->format('Y');

        $expense = DB::transaction(function () use ($validated, $userId, $fecha, $anio) {
            $expenseNumber = $this->counterService->nextExpenseNumber($userId, $anio);

            return Expense::query()->create([
                'uuid' => (string) Str::uuid(),
                'user_id' => $userId,
                'vehicle_id' => $validated['vehicle_id'] ?? null,
                'category_id' => $validated['category_id'],
                'anio' => $anio,
                'expense_number' => $expenseNumber,
                'fecha' => $fecha->toDateString(),
                ...$this->financialRecords->encryptExpensePayload([
                    'monto' => $validated['monto'],
                    'descripcion' => $validated['descripcion'] ?? null,
                ]),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Gasto registrado correctamente.',
            'data' => $expense,
        ]);
    }

    public function getDatatableServerSide(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = min((int) $request->input('length', 10), 100);
        $search = trim((string) $request->input('search.value', ''));

        $baseQuery = DB::table('expenses as e')
            ->join('expense_categories as c', 'c.id', '=', 'e.category_id')
            ->leftJoin('vehicles as v', 'v.id', '=', 'e.vehicle_id')
            ->where('e.user_id', $userId)
            ->select([
                'e.expense_number',
                'e.fecha',
                'c.name as categoria',
                'e.monto',
                'e.descripcion',
                'v.plate_number',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('c.name', 'ilike', "%{$search}%")
                        ->orWhere('e.descripcion', 'ilike', "%{$search}%")
                        ->orWhereRaw('CAST(e.expense_number AS TEXT) ILIKE ?', ["%{$search}%"]);
                });
            });

        $countQuery = DB::table('expenses as e')
            ->join('expense_categories as c', 'c.id', '=', 'e.category_id')
            ->where('e.user_id', $userId)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('c.name', 'ilike', "%{$search}%")
                        ->orWhere('e.descripcion', 'ilike', "%{$search}%")
                        ->orWhereRaw('CAST(e.expense_number AS TEXT) ILIKE ?', ["%{$search}%"]);
                });
            });

        $recordsTotal = DB::table('expenses')->where('user_id', $userId)->count();
        $recordsFiltered = $countQuery->count();

        $rows = $baseQuery
            ->orderByDesc('e.fecha')
            ->orderByDesc('e.expense_number')
            ->offset($start)
            ->limit($length)
            ->get();

        $data = $rows->map(function ($row) {
            $amounts = $this->financialRecords->decryptExpenseRow($row);

            return [
                'expense_number' => $row->expense_number,
                'fecha' => $row->fecha,
                'categoria' => $row->categoria,
                'vehicle' => $row->plate_number ?? '—',
                'monto' => number_format($amounts['monto'], 2),
                'descripcion' => $amounts['descripcion'] ?? '—',
            ];
        });

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }
}
