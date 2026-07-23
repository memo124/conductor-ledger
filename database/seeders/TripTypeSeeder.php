<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TripTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['code' => 'PLATAFORMA', 'name' => 'Plataforma', 'allowed_modes' => 'daily', 'is_active' => true],
            ['code' => 'PERSONAL', 'name' => 'Viaje aparte', 'allowed_modes' => 'per_trip', 'is_active' => true],
            ['code' => 'MICROBUS_RUTA', 'name' => 'Microbús / ruta', 'allowed_modes' => 'daily,monthly', 'is_active' => true],
            ['code' => 'ESCOLAR', 'name' => 'Transporte escolar', 'allowed_modes' => 'daily,monthly', 'is_active' => false],
            ['code' => 'INTERURBANO', 'name' => 'Interurbano', 'allowed_modes' => 'per_trip', 'is_active' => false],
            ['code' => 'INTERNACIONAL', 'name' => 'Internacional', 'allowed_modes' => 'per_trip', 'is_active' => false],
        ];

        foreach ($types as $type) {
            DB::table('trip_types')->updateOrInsert(
                ['code' => $type['code']],
                [
                    'name' => $type['name'],
                    'allowed_modes' => $type['allowed_modes'],
                    'is_active' => $type['is_active'],
                ]
            );
        }
    }
}
