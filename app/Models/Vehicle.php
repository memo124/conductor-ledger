<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vehicle extends Model
{
    protected $fillable = [
        'user_id',
        'ownership_type_id',
        'plate_number',
        'rental_fee_daily',
        'rental_period',
        'quota_percentage',
        'quota_reserve_amount',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
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
}
