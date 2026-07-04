<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (['GASOLINA', 'ALQUILER', 'COMIDA', 'MANTENIMIENTO', 'OTROS'] as $name) {
            ExpenseCategory::query()->firstOrCreate(['name' => $name]);
        }
    }
}
