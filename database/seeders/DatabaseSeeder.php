<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleOwnershipType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            VehicleOwnershipTypeSeeder::class,
            ExpenseCategorySeeder::class,
            UserSeeder::class,
            DemoDataSeeder::class,
        ]);
    }
}
