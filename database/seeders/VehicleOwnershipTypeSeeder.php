<?php

namespace Database\Seeders;

use App\Models\VehicleOwnershipType;
use Illuminate\Database\Seeder;

class VehicleOwnershipTypeSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['ALQUILADO', 'PROPIO', 'FINANCIADO', 'OTRO'] as $name) {
            VehicleOwnershipType::query()->firstOrCreate(['name' => $name]);
        }
    }
}
