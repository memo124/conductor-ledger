<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('roles')) {
            $this->command?->warn('Tabla roles no existe. Ejecuta las migraciones RBAC antes de sembrar roles.');

            return;
        }

        Role::query()->updateOrCreate(
            ['slug' => 'conductor'],
            ['name' => 'Conductor', 'is_system' => true]
        );

        Role::query()->updateOrCreate(
            ['slug' => 'administrador'],
            ['name' => 'Administrador', 'is_system' => true]
        );
    }
}
