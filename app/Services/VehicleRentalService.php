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

    /**
     * @return array{
     *     amount: float,
     *     base_ingreso: float,
     *     percentage_applied: float,
     *     raw_amount: float,
     *     period_cap: float,
     *     reserve_cap: float,
     *     trip_cap_applied: bool,
     *     period_cap_applied: bool,
     *     reserve_cap_applied: bool,
     *     quota_configured: bool
     * }
     */
    public function buildTripRentalSuggestion(
        Vehicle $vehicle,
        Carbon $fecha,
        float $baseIngreso,
        string $registrationMode,
        ?float $porcentajeCuota = null,
    ): array {
        $baseIngreso = max(0, round($baseIngreso, 2));
        $pct = round((float) ($porcentajeCuota ?? $vehicle->quota_percentage), 2);
        $periodCap = match ($registrationMode) {
            'monthly' => $this->suggestMonthlyRental($vehicle),
            default => $this->suggestDailyRental($vehicle, $fecha),
        };

        $empty = [
            'amount' => 0.0,
            'base_ingreso' => $baseIngreso,
            'percentage_applied' => $pct,
            'raw_amount' => 0.0,
            'period_cap' => $periodCap,
            'reserve_cap' => (float) $vehicle->quota_reserve_amount,
            'trip_cap_applied' => false,
            'period_cap_applied' => false,
            'reserve_cap_applied' => false,
            'quota_configured' => $pct > 0,
        ];

        if (! $this->isRentedVehicle($vehicle) || $pct <= 0 || $baseIngreso <= 0) {
            return $empty;
        }

        $rawAmount = round($baseIngreso * ($pct / 100), 2);
        $amount = $rawAmount;
        $reserveCapApplied = false;
        $periodCapApplied = false;
        $tripCapApplied = false;

        $reserveCap = (float) $vehicle->quota_reserve_amount;
        if ($reserveCap > 0 && $amount > $reserveCap) {
            $amount = $reserveCap;
            $reserveCapApplied = true;
        }

        if ($periodCap > 0 && $amount > $periodCap) {
            $amount = $periodCap;
            $periodCapApplied = true;
        }

        if ($amount > $baseIngreso) {
            $amount = $baseIngreso;
            $tripCapApplied = true;
        }

        return [
            'amount' => round($amount, 2),
            'base_ingreso' => $baseIngreso,
            'percentage_applied' => $pct,
            'raw_amount' => $rawAmount,
            'period_cap' => $periodCap,
            'reserve_cap' => $reserveCap,
            'trip_cap_applied' => $tripCapApplied,
            'period_cap_applied' => $periodCapApplied,
            'reserve_cap_applied' => $reserveCapApplied,
            'quota_configured' => true,
        ];
    }

    public function suggestTripRental(
        Vehicle $vehicle,
        Carbon $fecha,
        float $baseIngreso,
        string $registrationMode,
        ?float $porcentajeCuota = null,
    ): float {
        return $this->buildTripRentalSuggestion(
            $vehicle,
            $fecha,
            $baseIngreso,
            $registrationMode,
            $porcentajeCuota,
        )['amount'];
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
    public function validateTripRental(Vehicle $vehicle, float $alquiler, float $baseIngreso = 0): void
    {
        if (! $this->isRentedVehicle($vehicle) && $alquiler > 0) {
            throw ValidationException::withMessages([
                'alquiler' => 'Solo los vehículos ALQUILADO o FINANCIADO pueden registrar costo periódico en el viaje.',
            ]);
        }

        if ($baseIngreso > 0 && $alquiler > $baseIngreso) {
            throw ValidationException::withMessages([
                'alquiler' => 'La cuota del vehículo no puede superar lo ganado en el viaje.',
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
        $suggestion = $isRented
            ? $this->buildTripRentalSuggestion($vehicle, $fecha, $base, $registrationMode, $porcentajeCuota)
            : [
                'amount' => 0.0,
                'base_ingreso' => $base,
                'percentage_applied' => (float) $vehicle->quota_percentage,
                'raw_amount' => 0.0,
                'period_cap' => 0.0,
                'reserve_cap' => (float) $vehicle->quota_reserve_amount,
                'trip_cap_applied' => false,
                'period_cap_applied' => false,
                'reserve_cap_applied' => false,
                'quota_configured' => (float) $vehicle->quota_percentage > 0,
            ];

        return [
            'is_rented' => $isRented,
            'ownership_type' => $vehicle->ownershipType?->name,
            'rental_period' => $vehicle->rental_period ?? 'daily',
            'rental_period_label' => $this->rentalPeriodLabel($vehicle),
            'rental_fee' => (float) $vehicle->rental_fee_daily,
            'quota_percentage' => (float) $vehicle->quota_percentage,
            'quota_reserve_amount' => (float) $vehicle->quota_reserve_amount,
            'suggested_alquiler' => $suggestion['amount'],
            'alquiler_editable' => $isRented,
            'base_ingreso' => $suggestion['base_ingreso'],
            'percentage_applied' => $suggestion['percentage_applied'],
            'raw_amount' => $suggestion['raw_amount'],
            'period_cap' => $suggestion['period_cap'],
            'reserve_cap' => $suggestion['reserve_cap'],
            'trip_cap_applied' => $suggestion['trip_cap_applied'],
            'period_cap_applied' => $suggestion['period_cap_applied'],
            'reserve_cap_applied' => $suggestion['reserve_cap_applied'],
            'quota_configured' => $suggestion['quota_configured'],
        ];
    }
}
