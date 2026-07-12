<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlatformSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['InDrive', 'Uber', 'DiDi', 'Cabify', 'Otro'] as $name) {
            DB::table('platforms')->updateOrInsert(
                ['name' => $name],
                ['is_active' => true]
            );
        }
    }
}
