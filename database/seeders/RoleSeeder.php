<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
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
