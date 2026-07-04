<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlySummary extends Model
{
    protected $fillable = [
        'user_id',
        'anio',
        'mes',
        'total_indrive',
        'total_otros_viajes',
        'total_propinas',
        'total_alquiler',
        'total_gastos',
        'ganancia_neta',
    ];

    protected function casts(): array
    {
        return [
            'total_indrive' => 'decimal:2',
            'total_otros_viajes' => 'decimal:2',
            'total_propinas' => 'decimal:2',
            'total_alquiler' => 'decimal:2',
            'total_gastos' => 'decimal:2',
            'ganancia_neta' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
