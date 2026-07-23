<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MicrobusPassengerPayment extends Model
{
    protected $fillable = [
        'microbus_passenger_id',
        'period_year',
        'period_month',
        'amount_due',
        'is_paid',
        'paid_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount_due' => 'decimal:2',
            'is_paid' => 'boolean',
            'paid_at' => 'datetime',
        ];
    }

    public function passenger(): BelongsTo
    {
        return $this->belongsTo(MicrobusPassenger::class, 'microbus_passenger_id');
    }
}
