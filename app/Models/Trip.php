<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    public $incrementing = false;

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    protected $fillable = [
        'uuid',
        'user_id',
        'vehicle_id',
        'anio',
        'trip_number',
        'fecha',
        'dia_semana',
        'indrive',
        'otros_viajes',
        'propina',
        'alquiler',
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
        ];
    }
}
