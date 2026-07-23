<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MicrobusPassenger extends Model
{
    protected $fillable = [
        'microbus_route_id',
        'client_id',
        'client_dependent_id',
        'display_name',
        'monthly_fee',
        'pickup_notes',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'monthly_fee' => 'decimal:2',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(MicrobusRoute::class, 'microbus_route_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function dependent(): BelongsTo
    {
        return $this->belongsTo(ClientDependent::class, 'client_dependent_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(MicrobusPassengerPayment::class);
    }

    public function resolvedName(): string
    {
        if ($this->dependent) {
            return $this->dependent->name;
        }

        if ($this->client) {
            return $this->client->name;
        }

        return $this->display_name ?? '—';
    }
}
