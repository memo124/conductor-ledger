<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $seeders = [
            VehicleOwnershipTypeSeeder::class,
            ExpenseCategorySeeder::class,
        ];

        if (Schema::hasTable('currencies')) {
            array_unshift($seeders, CurrencySeeder::class);
        }

        if (Schema::hasTable('roles')) {
            array_unshift($seeders,
                RoleSeeder::class,
                AppOptionSeeder::class,
                RolePermissionSeeder::class,
            );
        }

        if ($this->puedeSembrarUsuarios()) {
            $seeders[] = UserSeeder::class;
            $seeders[] = DemoDataSeeder::class;
        } else {
            $this->command?->warn(
                'Omitiendo UserSeeder/DemoDataSeeder: faltan columnas de seguridad (migración RBAC pendiente).'
            );
        }

        $this->call($seeders);
    }

    private function puedeSembrarUsuarios(): bool
    {
        return Schema::hasTable('users')
            && Schema::hasColumn('users', 'theme_preference')
            && Schema::hasColumn('users', 'role')
            && Schema::hasColumn('users', 'encrypted_dek');
    }
}
