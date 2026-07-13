<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeRateSync extends Model
{
    protected $fillable = [
        'status',
        'currencies_count',
        'api_calls_used',
        'source_date',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'currencies_count' => 'integer',
            'api_calls_used' => 'integer',
            'source_date' => 'datetime',
        ];
    }
}
