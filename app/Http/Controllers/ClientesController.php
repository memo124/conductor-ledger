<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientDependent;
use App\Support\Select2Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ClientesController extends Controller
{
    public function index(): View
    {
        return view('clientes.index');
    }

    public function getDatatableServerSide(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = min((int) $request->input('length', 10), 100);
        $search = trim((string) $request->input('search.value', ''));

        $query = Client::query()
            ->withCount('dependents')
            ->where('user_id', $userId)
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'ilike', "%{$search}%")
                        ->orWhere('phone', 'ilike', "%{$search}%")
                        ->orWhere('email', 'ilike', "%{$search}%")
                        ->orWhere('address', 'ilike', "%{$search}%");
                });
            });

        $recordsTotal = Client::query()->where('user_id', $userId)->count();
        $recordsFiltered = (clone $query)->count();
        $rows = $query->orderByDesc('id')->offset($start)->limit($length)->get();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows->map(fn (Client $client) => [
                'id' => $client->id,
                'name' => $client->name,
                'phone' => $client->phone ?? '—',
                'email' => $client->email ?? '—',
                'address' => $client->address ?? '—',
                'dependents_count' => $client->dependents_count,
                'has_location' => $client->latitude !== null && $client->longitude !== null,
                'is_active' => $client->is_active ? ui('common.active') : ui('common.inactive'),
                'is_active_bool' => $client->is_active,
            ]),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $client = Client::query()
            ->with(['dependents' => fn ($q) => $q->orderBy('name')])
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $client->id,
                'name' => $client->name,
                'phone' => $client->phone,
                'email' => $client->email,
                'address' => $client->address,
                'latitude' => $client->latitude,
                'longitude' => $client->longitude,
                'notes' => $client->notes,
                'is_active' => $client->is_active,
                'dependents' => $client->dependents->map(fn (ClientDependent $dep) => [
                    'id' => $dep->id,
                    'name' => $dep->name,
                    'relationship_label' => $dep->relationship_label,
                    'phone' => $dep->phone,
                    'birth_date' => $dep->birth_date?->format('Y-m-d'),
                    'notes' => $dep->notes,
                    'is_active' => $dep->is_active,
                ]),
            ],
        ]);
    }

    public function select2Paginated(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('q', ''));
        $page = max(1, (int) $request->query('page', 1));

        $paginator = Client::query()
            ->where('user_id', Auth::id())
            ->where('is_active', true)
            ->when($search !== '', fn ($q) => $q->where('name', 'ilike', "%{$search}%"))
            ->orderBy('name')
            ->paginate(15, ['*'], 'page', $page);

        return response()->json(Select2Response::fromPaginator($paginator, fn (Client $client) => [
            'id' => $client->id,
            'text' => $client->name,
        ]));
    }

    public function select2Dependents(Request $request): JsonResponse
    {
        $clientId = (int) $request->query('client_id', 0);
        $search = trim((string) $request->query('q', ''));

        $dependents = ClientDependent::query()
            ->whereHas('client', fn ($q) => $q->where('user_id', Auth::id())->where('id', $clientId))
            ->where('is_active', true)
            ->when($search !== '', fn ($q) => $q->where('name', 'ilike', "%{$search}%"))
            ->orderBy('name')
            ->limit(30)
            ->get();

        return response()->json([
            'results' => $dependents->map(fn (ClientDependent $dep) => [
                'id' => $dep->id,
                'text' => $dep->name.($dep->relationship_label ? ' ('.$dep->relationship_label.')' : ''),
            ]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateClient($request);

        $client = DB::transaction(function () use ($validated) {
            $client = Client::query()->create(array_merge($validated['client'], [
                'user_id' => Auth::id(),
            ]));

            $this->syncDependents($client, $validated['dependents'] ?? []);

            return $client->load('dependents');
        });

        return response()->json([
            'success' => true,
            'message' => ui('pages.clientes.saved'),
            'data' => $client,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $client = Client::query()->where('user_id', Auth::id())->findOrFail($id);
        $validated = $this->validateClient($request, $id);

        DB::transaction(function () use ($client, $validated) {
            $client->update($validated['client']);
            $this->syncDependents($client, $validated['dependents'] ?? [], true);
        });

        return response()->json([
            'success' => true,
            'message' => ui('pages.clientes.updated'),
            'data' => $client->fresh('dependents'),
        ]);
    }

    private function validateClient(Request $request, ?int $clientId = null): array
    {
        $clientRules = [
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ];

        if ($clientId) {
            $clientRules['is_active'] = ['required', 'boolean'];
        }

        $clientData = $request->validate($clientRules);
        $clientData['is_active'] = $clientId ? (bool) $clientData['is_active'] : true;

        $dependents = $request->input('dependents', []);
        if (! is_array($dependents)) {
            $dependents = [];
        }

        foreach ($dependents as $index => $dependent) {
            $request->validate([
                "dependents.$index.name" => ['required', 'string', 'max:120'],
                "dependents.$index.relationship_label" => ['nullable', 'string', 'max:50'],
                "dependents.$index.phone" => ['nullable', 'string', 'max:30'],
                "dependents.$index.birth_date" => ['nullable', 'date'],
                "dependents.$index.notes" => ['nullable', 'string', 'max:1000'],
                "dependents.$index.is_active" => ['nullable', 'boolean'],
            ]);
        }

        return [
            'client' => $clientData,
            'dependents' => $dependents,
        ];
    }

    private function syncDependents(Client $client, array $dependents, bool $replace = false): void
    {
        $keptIds = [];

        foreach ($dependents as $dependent) {
            if (empty($dependent['name'])) {
                continue;
            }

            $payload = [
                'name' => $dependent['name'],
                'relationship_label' => $dependent['relationship_label'] ?? null,
                'phone' => $dependent['phone'] ?? null,
                'birth_date' => $dependent['birth_date'] ?? null,
                'notes' => $dependent['notes'] ?? null,
                'is_active' => array_key_exists('is_active', $dependent) ? (bool) $dependent['is_active'] : true,
            ];

            if (! empty($dependent['id'])) {
                $model = ClientDependent::query()
                    ->where('client_id', $client->id)
                    ->where('id', $dependent['id'])
                    ->first();

                if ($model) {
                    $model->update($payload);
                    $keptIds[] = $model->id;

                    continue;
                }
            }

            $created = $client->dependents()->create($payload);
            $keptIds[] = $created->id;
        }

        if ($replace) {
            $client->dependents()->whereNotIn('id', $keptIds)->delete();
        }
    }
}
