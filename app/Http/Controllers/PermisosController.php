<?php

namespace App\Http\Controllers;

use App\Models\AppOption;
use App\Models\Role;
use App\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PermisosController extends Controller
{
    public function __construct(private readonly PermissionService $permissions) {}

    public function index(): View
    {
        return view('admin.permisos.index', [
            'roles' => Role::query()->orderBy('name')->get(),
        ]);
    }

    public function getMatrix(Request $request): JsonResponse
    {
        $roleId = (int) $request->query('role_id');
        $role = Role::query()->findOrFail($roleId);

        $options = AppOption::query()
            ->where('is_menu', true)
            ->whereNotNull('route_name')
            ->orderBy('sort_order')
            ->get();

        $permissions = DB::table('role_permissions')
            ->where('role_id', $roleId)
            ->get()
            ->keyBy('app_option_id');

        $data = $options->map(function (AppOption $option) use ($permissions) {
            $perm = $permissions->get($option->id);

            return [
                'app_option_id' => $option->id,
                'slug' => $option->slug,
                'label' => ui_menu($option->slug, $option->label),
                'can_view' => (bool) ($perm->can_view ?? false),
                'can_create' => (bool) ($perm->can_create ?? false),
                'can_update' => (bool) ($perm->can_update ?? false),
                'can_delete' => (bool) ($perm->can_delete ?? false),
            ];
        });

        return response()->json([
            'success' => true,
            'role' => ['id' => $role->id, 'name' => $role->name, 'slug' => $role->slug],
            'data' => $data,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'permissions' => ['required', 'array'],
            'permissions.*.app_option_id' => ['required', 'integer', 'exists:app_options,id'],
            'permissions.*.can_view' => ['required', 'boolean'],
            'permissions.*.can_create' => ['required', 'boolean'],
            'permissions.*.can_update' => ['required', 'boolean'],
            'permissions.*.can_delete' => ['required', 'boolean'],
        ]);

        $roleId = $validated['role_id'];

        DB::transaction(function () use ($validated, $roleId) {
            foreach ($validated['permissions'] as $perm) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'app_option_id' => $perm['app_option_id']],
                    [
                        'can_view' => $perm['can_view'],
                        'can_create' => $perm['can_create'],
                        'can_update' => $perm['can_update'],
                        'can_delete' => $perm['can_delete'],
                    ]
                );
            }
        });

        $this->permissions->clearCacheForRoleUsers($roleId);

        return response()->json(['success' => true, 'message' => 'Permisos actualizados correctamente.']);
    }
}
