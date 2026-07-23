<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    protected $fillable = [
        'user_id',
        'ownership_type_id',
        'alias',
        'vehicle_kind',
        'brand',
        'model',
        'model_year',
        'color',
        'notes',
        'rental_fee_daily',
        'rental_period',
        'quota_percentage',
        'quota_reserve_amount',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'model_year' => 'integer',
            'rental_fee_daily' => 'decimal:2',
            'quota_percentage' => 'decimal:2',
            'quota_reserve_amount' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ownershipType(): BelongsTo
    {
        return $this->belongsTo(VehicleOwnershipType::class, 'ownership_type_id');
    }

    public function microbusRoutes(): HasMany
    {
        return $this->hasMany(MicrobusRoute::class);
    }

    public function displayLabel(): string
    {
        $parts = array_filter([
            $this->alias,
            $this->brand,
            $this->model,
        ]);

        return implode(' · ', $parts) ?: ($this->alias ?? '—');
    }

    public static function kinds(): array
    {
        return [
            'sedan' => 'sedan',
            'suv' => 'suv',
            'pickup' => 'pickup',
            'van' => 'van',
            'microbus' => 'microbus',
            'motorcycle' => 'motorcycle',
            'other' => 'other',
        ];
    }
}
