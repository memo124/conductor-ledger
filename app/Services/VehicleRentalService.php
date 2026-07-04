<?php

namespace App\Services;

use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class VehicleRentalService
{
    public function isRentedVehicle(Vehicle $vehicle): bool
    {
        return strtoupper($vehicle->ownershipType?->name ?? '') === 'ALQUILADO';
    }

    public function suggestDailyRental(Vehicle $vehicle, Carbon $fecha): float
    {
        $fee = (float) $vehicle->rental_fee_daily;

        if ($fee <= 0) {
            return 0.0;
        }

        return round(match ($vehicle->rental_period ?? 'daily') {
            'weekly' => $fee / 7,
            'monthly' => $fee / max(1, $fecha->daysInMonth),
            default => $fee,
        }, 2);
    }

    public function rentalPeriodLabel(Vehicle $vehicle): string
    {
        return match ($vehicle->rental_period ?? 'daily') {
            'weekly' => 'semanal',
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
                'alquiler' => 'Solo los vehículos ALQUILADO pueden registrar costo de alquiler en el viaje.',
            ]);
        }
    }

    public function vehicleMeta(Vehicle $vehicle, ?Carbon $fecha = null): array
    {
        $fecha ??= Carbon::today();
        $isRented = $this->isRentedVehicle($vehicle);
        $suggested = $isRented ? $this->suggestDailyRental($vehicle, $fecha) : 0.0;

        return [
            'is_rented' => $isRented,
            'ownership_type' => $vehicle->ownershipType?->name,
            'rental_period' => $vehicle->rental_period ?? 'daily',
            'rental_period_label' => $this->rentalPeriodLabel($vehicle),
            'rental_fee' => (float) $vehicle->rental_fee_daily,
            'suggested_alquiler' => $suggested,
            'alquiler_editable' => $isRented,
        ];
    }
}
