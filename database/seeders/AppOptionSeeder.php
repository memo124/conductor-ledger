<?php

namespace Database\Seeders;

use App\Models\AppOption;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class AppOptionSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('app_options')) {
            $this->command?->warn('Tabla app_options no existe. Ejecuta la migración RBAC primero.');

            return;
        }

        $tree = [
            [
                'slug' => 'operaciones',
                'label' => 'Operaciones',
                'route_name' => null,
                'icon' => 'fa-solid fa-layer-group',
                'sort_order' => 1,
                'children' => [
                    ['slug' => 'dashboard', 'label' => 'Dashboard', 'route_name' => 'dashboard', 'icon' => 'fa-solid fa-gauge-high', 'sort_order' => 1],
                    ['slug' => 'viajes', 'label' => 'Viajes', 'route_name' => 'viajes.index', 'icon' => 'fa-solid fa-road', 'sort_order' => 2],
                    ['slug' => 'gastos', 'label' => 'Gastos', 'route_name' => 'gastos.index', 'icon' => 'fa-solid fa-wallet', 'sort_order' => 3],
                    ['slug' => 'vehiculos', 'label' => 'Vehículos', 'route_name' => 'vehiculos.index', 'icon' => 'fa-solid fa-car', 'sort_order' => 4],
                    ['slug' => 'graficos', 'label' => 'Gráficos', 'route_name' => 'graficos.index', 'icon' => 'fa-solid fa-chart-pie', 'sort_order' => 5],
                ],
            ],
            [
                'slug' => 'maestros',
                'label' => 'Maestros',
                'route_name' => null,
                'icon' => 'fa-solid fa-database',
                'sort_order' => 2,
                'children' => [
                    ['slug' => 'tipos-propiedad', 'label' => 'Tipos Propiedad', 'route_name' => 'tipos-propiedad.index', 'icon' => 'fa-solid fa-key', 'sort_order' => 1],
                    ['slug' => 'categorias-gasto', 'label' => 'Categorías Gasto', 'route_name' => 'categorias-gasto.index', 'icon' => 'fa-solid fa-tags', 'sort_order' => 2],
                ],
            ],
            [
                'slug' => 'cuenta',
                'label' => 'Cuenta',
                'route_name' => null,
                'icon' => 'fa-solid fa-user',
                'sort_order' => 3,
                'children' => [
                    ['slug' => 'perfil', 'label' => 'Mi Perfil', 'route_name' => 'perfil.index', 'icon' => 'fa-solid fa-user', 'sort_order' => 1],
                ],
            ],
            [
                'slug' => 'administracion',
                'label' => 'Administración',
                'route_name' => null,
                'icon' => 'fa-solid fa-shield-halved',
                'sort_order' => 4,
                'children' => [
                    ['slug' => 'usuarios', 'label' => 'Usuarios', 'route_name' => 'usuarios.index', 'icon' => 'fa-solid fa-users-gear', 'sort_order' => 1],
                    ['slug' => 'admin.backups', 'label' => 'Respaldos', 'route_name' => 'admin.backups.index', 'icon' => 'fa-solid fa-database', 'sort_order' => 2],
                    ['slug' => 'admin.emergency-decrypt', 'label' => 'Descifrado emergencia', 'route_name' => 'admin.emergency-decrypt.index', 'icon' => 'fa-solid fa-unlock-keyhole', 'sort_order' => 3],
                ],
            ],
        ];

        foreach ($tree as $node) {
            $this->seedNode($node);
        }
    }

    private function seedNode(array $node, ?int $parentId = null): void
    {
        $children = $node['children'] ?? [];
        unset($node['children']);

        $option = AppOption::query()->updateOrCreate(
            ['slug' => $node['slug']],
            array_merge($node, [
                'parent_id' => $parentId,
                'is_menu' => $node['is_menu'] ?? ($node['route_name'] !== null || ! empty($children)),
                'is_active' => true,
            ])
        );

        foreach ($children as $child) {
            $this->seedNode($child, $option->id);
        }
    }
}
