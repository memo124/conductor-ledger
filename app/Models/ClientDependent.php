<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClientDependent extends Model
{
    protected $fillable = [
        'client_id',
        'name',
        'relationship_label',
        'phone',
        'birth_date',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function microbusPassengers(): HasMany
    {
        return $this->hasMany(MicrobusPassenger::class);
    }
}
