<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class YearlyCounter extends Model
{
    public $incrementing = false;

    protected $primaryKey = null;

    protected $fillable = [
        'user_id',
        'anio',
        'current_trip_number',
        'current_expense_number',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
