<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TripTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['code' => 'PLATAFORMA', 'name' => 'Plataforma', 'allowed_modes' => 'daily'],
            ['code' => 'PERSONAL', 'name' => 'Viaje aparte', 'allowed_modes' => 'per_trip'],
            ['code' => 'MICROBUS_RUTA', 'name' => 'Microbús / ruta', 'allowed_modes' => 'per_trip,daily,monthly'],
            ['code' => 'ESCOLAR', 'name' => 'Transporte escolar', 'allowed_modes' => 'daily,monthly'],
            ['code' => 'INTERURBANO', 'name' => 'Interurbano', 'allowed_modes' => 'per_trip'],
            ['code' => 'INTERNACIONAL', 'name' => 'Internacional', 'allowed_modes' => 'per_trip'],
        ];

        foreach ($types as $type) {
            DB::table('trip_types')->updateOrInsert(
                ['code' => $type['code']],
                array_merge($type, ['is_active' => true])
            );
        }
    }
}
