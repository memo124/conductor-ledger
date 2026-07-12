<?php

namespace App\Services;

use App\Models\TripType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TripRegistrationService
{
    public function baseIngreso(string $registrationMode, array $amounts): float
    {
        if (in_array($registrationMode, ['daily', 'monthly'], true)) {
            return max(0, (float) ($amounts['monto_bruto'] ?? 0) - (float) ($amounts['comision_app'] ?? 0));
        }

        return (float) ($amounts['monto_cobrado'] ?? 0);
    }

    /**
     * @throws ValidationException
     */
    public function validateUniqueness(
        int $userId,
        int $vehicleId,
        TripType $tripType,
        string $registrationMode,
        ?int $platformId,
        Carbon $fecha,
        ?int $periodYear,
        ?int $periodMonth,
    ): void {
        if ($registrationMode === 'daily' && $tripType->code === 'PLATAFORMA') {
            $exists = DB::table('trips')
                ->where('user_id', $userId)
                ->where('vehicle_id', $vehicleId)
                ->where('fecha', $fecha->toDateString())
                ->where('platform_id', $platformId)
                ->where('registration_mode', 'daily')
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'platform_id' => 'Ya existe un resumen diario para esta plataforma, vehículo y fecha.',
                ]);
            }

            return;
        }

        if ($registrationMode === 'daily' && $tripType->code !== 'PLATAFORMA') {
            $exists = DB::table('trips')
                ->where('user_id', $userId)
                ->where('vehicle_id', $vehicleId)
                ->where('fecha', $fecha->toDateString())
                ->where('trip_type_id', $tripType->id)
                ->where('registration_mode', 'daily')
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'fecha' => 'Ya existe un resumen diario para este tipo de viaje y vehículo.',
                ]);
            }

            return;
        }

        if ($registrationMode === 'monthly') {
            $exists = DB::table('trips')
                ->where('user_id', $userId)
                ->where('vehicle_id', $vehicleId)
                ->where('trip_type_id', $tripType->id)
                ->where('registration_mode', 'monthly')
                ->where('period_year', $periodYear)
                ->where('period_month', $periodMonth)
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'period_month' => 'Ya existe un resumen mensual para este tipo, vehículo y período.',
                ]);
            }
        }
    }

    public function resolveFecha(string $registrationMode, Carbon $fecha, ?int $periodYear, ?int $periodMonth): Carbon
    {
        if ($registrationMode === 'monthly' && $periodYear && $periodMonth) {
            return Carbon::create($periodYear, $periodMonth, 1)->endOfMonth();
        }

        return $fecha;
    }

    public function registrationModeLabel(string $mode): string
    {
        return match ($mode) {
            'daily' => 'Resumen del día',
            'monthly' => 'Resumen del mes',
            default => 'Viaje individual',
        };
    }
}
