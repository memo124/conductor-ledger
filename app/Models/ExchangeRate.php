<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExchangeRate extends Model
{
    protected $fillable = [
        'base_currency',
        'target_currency',
        'rate',
        'source_date',
        'fetched_at',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:10',
            'source_date' => 'datetime',
            'fetched_at' => 'datetime',
        ];
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'target_currency', 'code');
    }
}
