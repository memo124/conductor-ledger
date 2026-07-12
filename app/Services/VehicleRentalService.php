<?php

namespace App\Services;

use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class VehicleRentalService
{
    /** Tipos que exigen periodo y cuota al registrar el vehículo. */
    private const FEE_OWNERSHIP_TYPES = ['ALQUILADO', 'FINANCIADO'];

    public function ownershipRequiresFee(?string $ownershipTypeName): bool
    {
        return in_array(strtoupper($ownershipTypeName ?? ''), self::FEE_OWNERSHIP_TYPES, true);
    }

    public function isRentedVehicle(Vehicle $vehicle): bool
    {
        return $this->ownershipRequiresFee($vehicle->ownershipType?->name);
    }

    public function suggestDailyRental(Vehicle $vehicle, Carbon $fecha): float
    {
        $fee = (float) $vehicle->rental_fee_daily;

        if ($fee <= 0) {
            return 0.0;
        }

        return round(match ($vehicle->rental_period ?? 'daily') {
            'weekly' => $fee / 7,
            'biweekly' => $fee / 14,
            'monthly' => $fee / max(1, $fecha->daysInMonth),
            default => $fee,
        }, 2);
    }

    public function suggestMonthlyRental(Vehicle $vehicle): float
    {
        $fee = (float) $vehicle->rental_fee_daily;

        if ($fee <= 0) {
            return 0.0;
        }

        return round(match ($vehicle->rental_period ?? 'daily') {
            'weekly' => $fee * 4,
            'biweekly' => $fee * 2,
            'monthly' => $fee,
            default => $fee * 30,
        }, 2);
    }

    public function suggestTripRental(
        Vehicle $vehicle,
        Carbon $fecha,
        float $baseIngreso,
        string $registrationMode,
        ?float $porcentajeCuota = null,
    ): float {
        if ((float) $vehicle->quota_reserve_amount <= 0) {
            return 0.0;
        }

        $pct = $porcentajeCuota ?? (float) $vehicle->quota_percentage;

        if ($pct <= 0 || $baseIngreso <= 0) {
            return 0.0;
        }

        if (! $this->isRentedVehicle($vehicle)) {
            return 0.0;
        }

        $cap = match ($registrationMode) {
            'monthly' => $this->suggestMonthlyRental($vehicle),
            default => $this->suggestDailyRental($vehicle, $fecha),
        };

        return round(min($baseIngreso * ($pct / 100), $cap), 2);
    }

    public function rentalPeriodLabel(Vehicle $vehicle): string
    {
        return match ($vehicle->rental_period ?? 'daily') {
            'weekly' => 'semanal',
            'biweekly' => 'quincenal',
            'monthly' => 'mensual',
            default => 'diario',
        };
    }

    /**
     * @throws ValidationException
     */
    public function validateTripRental(Vehicle $vehicle, float $alquiler): void
    {
        if (! $this->isRentedVehicle($vehicle) && $alquiler > 0) {
            throw ValidationException::withMessages([
                'alquiler' => 'Solo los vehículos ALQUILADO o FINANCIADO pueden registrar costo periódico en el viaje.',
            ]);
        }
    }

    public function vehicleMeta(
        Vehicle $vehicle,
        ?Carbon $fecha = null,
        ?float $baseIngreso = null,
        string $registrationMode = 'per_trip',
        ?float $porcentajeCuota = null,
    ): array {
        $fecha ??= Carbon::today();
        $isRented = $this->isRentedVehicle($vehicle);
        $base = $baseIngreso ?? 0.0;
        $suggested = $isRented
            ? $this->suggestTripRental($vehicle, $fecha, $base, $registrationMode, $porcentajeCuota)
            : 0.0;

        return [
            'is_rented' => $isRented,
            'ownership_type' => $vehicle->ownershipType?->name,
            'rental_period' => $vehicle->rental_period ?? 'daily',
            'rental_period_label' => $this->rentalPeriodLabel($vehicle),
            'rental_fee' => (float) $vehicle->rental_fee_daily,
            'quota_percentage' => (float) $vehicle->quota_percentage,
            'quota_reserve_amount' => (float) $vehicle->quota_reserve_amount,
            'suggested_alquiler' => $suggested,
            'alquiler_editable' => $isRented,
        ];
    }
}
