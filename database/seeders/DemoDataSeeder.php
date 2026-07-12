<?php

namespace Database\Seeders;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Platform;
use App\Models\Trip;
use App\Models\TripType;
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

        $ownership = VehicleOwnershipType::query()->where('name', 'FINANCIADO')->first()
            ?? VehicleOwnershipType::query()->where('name', 'ALQUILADO')->first();

        if (! $ownership) {
            return;
        }

        $vehicle = Vehicle::query()->firstOrCreate(
            ['user_id' => $user->id, 'plate_number' => 'P123456'],
            [
                'ownership_type_id' => $ownership->id,
                'rental_fee_daily' => 200.00,
                'rental_period' => 'monthly',
                'quota_percentage' => 15,
                'quota_reserve_amount' => 50.00,
                'is_active' => true,
            ]
        );

        $platformType = TripType::query()->where('code', 'PLATAFORMA')->first();
        $personalType = TripType::query()->where('code', 'PERSONAL')->first();
        $indrive = Platform::query()->where('name', 'InDrive')->first();

        if (! $platformType || ! $personalType || ! $indrive) {
            return;
        }

        $anio = (int) date('Y');
        YearlyCounter::query()->firstOrCreate(
            ['user_id' => $user->id, 'anio' => $anio],
            ['current_trip_number' => 0, 'current_expense_number' => 0]
        );

        if (Trip::query()->where('user_id', $user->id)->exists()) {
            return;
        }

        $tripNumber = 0;

        for ($i = 1; $i <= 3; $i++) {
            $fecha = Carbon::now()->subDays($i);
            $tripNumber++;
            Trip::query()->create([
                'uuid' => (string) Str::uuid(),
                'user_id' => $user->id,
                'vehicle_id' => $vehicle->id,
                'trip_type_id' => $platformType->id,
                'platform_id' => $indrive->id,
                'registration_mode' => 'daily',
                'anio' => $anio,
                'trip_number' => $tripNumber,
                'fecha' => $fecha->toDateString(),
                'dia_semana' => $fecha->locale('es')->isoFormat('dddd'),
                'monto_bruto' => 50.00 + $i,
                'comision_app' => 10.00,
                'propina' => 3.50,
                'alquiler' => 7.50,
                'porcentaje_cuota' => 15,
            ]);
        }

        $fechaPersonal = Carbon::now()->subDay();
        $tripNumber++;
        Trip::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'trip_type_id' => $personalType->id,
            'registration_mode' => 'per_trip',
            'anio' => $anio,
            'trip_number' => $tripNumber,
            'fecha' => $fechaPersonal->toDateString(),
            'dia_semana' => $fechaPersonal->locale('es')->isoFormat('dddd'),
            'monto_cobrado' => 15.00,
            'propina' => 2.00,
            'alquiler' => 2.25,
            'porcentaje_cuota' => 15,
        ]);

        YearlyCounter::query()
            ->where('user_id', $user->id)
            ->where('anio', $anio)
            ->update(['current_trip_number' => $tripNumber]);

        $categoriaGasolina = ExpenseCategory::query()->where('name', 'GASOLINA')->first();

        if ($categoriaGasolina) {
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
}
