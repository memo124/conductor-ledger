<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'email',
        'address',
        'latitude',
        'longitude',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dependents(): HasMany
    {
        return $this->hasMany(ClientDependent::class);
    }

    public function microbusPassengers(): HasMany
    {
        return $this->hasMany(MicrobusPassenger::class);
    }
}
