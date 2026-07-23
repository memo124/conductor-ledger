<?php

namespace Database\Seeders;

use App\Models\AppOption;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('role_permissions') || ! Schema::hasTable('roles')) {
            $this->command?->warn('Tablas RBAC no existen. Ejecuta la migración de seguridad primero.');

            return;
        }

        $conductor = Role::query()->where('slug', 'conductor')->first();
        $admin = Role::query()->where('slug', 'administrador')->first();

        if (! $conductor || ! $admin) {
            $this->command?->warn('Roles conductor/administrador no encontrados. Ejecuta RoleSeeder primero.');

            return;
        }

        $conductorSlugs = [
            'dashboard', 'viajes', 'gastos', 'vehiculos', 'graficos', 'conversor', 'clientes', 'microbus-rutas',
            'tipos-propiedad', 'categorias-gasto', 'plataformas', 'tipos-viaje', 'perfil',
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
