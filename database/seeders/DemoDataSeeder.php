<?php

namespace Database\Seeders;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleOwnershipType;
use App\Models\YearlyCounter;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->where('email', 'conductor@conductorledger.local')->first();

        if (! $user) {
            return;
        }

        $ownership = VehicleOwnershipType::query()->where('name', 'ALQUILADO')->first();

        if (! $ownership) {
            $this->command?->warn('Tipo de propiedad ALQUILADO no encontrado. Ejecuta VehicleOwnershipTypeSeeder primero.');

            return;
        }

        $vehicle = Vehicle::query()->firstOrCreate(
            ['user_id' => $user->id, 'plate_number' => 'P123456'],
            [
                'ownership_type_id' => $ownership->id,
                'rental_fee_daily' => 25.00,
                'is_active' => true,
            ]
        );

        $anio = (int) date('Y');
        YearlyCounter::query()->firstOrCreate(
            ['user_id' => $user->id, 'anio' => $anio],
            ['current_trip_number' => 0, 'current_expense_number' => 0]
        );

        if (Trip::query()->where('user_id', $user->id)->exists()) {
            return;
        }

        for ($i = 1; $i <= 5; $i++) {
            $fecha = Carbon::now()->subDays($i);
            Trip::query()->create([
                'uuid' => (string) Str::uuid(),
                'user_id' => $user->id,
                'vehicle_id' => $vehicle->id,
                'anio' => $anio,
                'trip_number' => $i,
                'fecha' => $fecha->toDateString(),
                'dia_semana' => $fecha->locale('es')->isoFormat('dddd'),
                'indrive' => 45.00 + $i,
                'otros_viajes' => 10.00,
                'propina' => 3.50,
                'alquiler' => 25.00,
            ]);
        }

        YearlyCounter::query()
            ->where('user_id', $user->id)
            ->where('anio', $anio)
            ->update(['current_trip_number' => 5]);

        $categoriaGasolina = ExpenseCategory::query()->where('name', 'GASOLINA')->first();

        if (! $categoriaGasolina) {
            $this->command?->warn('Categoría GASOLINA no encontrada. Omitiendo gasto demo.');

            return;
        }

        Expense::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'category_id' => $categoriaGasolina->id,
            'anio' => $anio,
            'expense_number' => 1,
            'fecha' => Carbon::now()->subDay()->toDateString(),
            'monto' => 35.00,
            'descripcion' => 'Tanque lleno demo',
        ]);

        YearlyCounter::query()
            ->where('user_id', $user->id)
            ->where('anio', $anio)
            ->update(['current_expense_number' => 1]);
    }
}
