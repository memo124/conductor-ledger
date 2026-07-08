<?php

namespace Database\Seeders;

use App\Models\AppOption;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $conductor = Role::query()->where('slug', 'conductor')->firstOrFail();
        $admin = Role::query()->where('slug', 'administrador')->firstOrFail();

        $conductorSlugs = [
            'dashboard', 'viajes', 'gastos', 'vehiculos', 'graficos',
            'tipos-propiedad', 'categorias-gasto', 'perfil',
        ];

        $adminSlugs = AppOption::query()->pluck('slug')->all();

        DB::table('role_permissions')->whereIn('role_id', [$conductor->id, $admin->id])->delete();

        foreach ($conductorSlugs as $slug) {
            $this->grant($conductor->id, $slug, true, true, true, false);
        }

        foreach ($adminSlugs as $slug) {
            $this->grant($admin->id, $slug, true, true, true, true);
        }
    }

    private function grant(int $roleId, string $slug, bool $view, bool $create, bool $update, bool $delete): void
    {
        $option = AppOption::query()->where('slug', $slug)->first();

        if (! $option) {
            return;
        }

        DB::table('role_permissions')->updateOrInsert(
            ['role_id' => $roleId, 'app_option_id' => $option->id],
            [
                'can_view' => $view,
                'can_create' => $create,
                'can_update' => $update,
                'can_delete' => $delete,
            ]
        );
    }
}
