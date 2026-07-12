<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Trip extends Model
{
    public $incrementing = false;

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    protected $fillable = [
        'uuid',
        'user_id',
        'vehicle_id',
        'trip_type_id',
        'platform_id',
        'registration_mode',
        'period_year',
        'period_month',
        'anio',
        'trip_number',
        'fecha',
        'dia_semana',
        'indrive',
        'otros_viajes',
        'propina',
        'alquiler',
        'monto_bruto',
        'comision_app',
        'monto_cobrado',
        'porcentaje_cuota',
        'encrypted_payload',
        'encryption_version',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'indrive' => 'decimal:2',
            'otros_viajes' => 'decimal:2',
            'propina' => 'decimal:2',
            'alquiler' => 'decimal:2',
            'monto_bruto' => 'decimal:2',
            'comision_app' => 'decimal:2',
            'monto_cobrado' => 'decimal:2',
            'porcentaje_cuota' => 'decimal:2',
        ];
    }

    public function tripType(): BelongsTo
    {
        return $this->belongsTo(TripType::class);
    }

    public function platform(): BelongsTo
    {
        return $this->belongsTo(Platform::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
