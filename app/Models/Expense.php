<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    public $incrementing = false;

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    protected $fillable = [
        'uuid',
        'user_id',
        'vehicle_id',
        'category_id',
        'anio',
        'expense_number',
        'fecha',
        'monto',
        'descripcion',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'monto' => 'decimal:2',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }
}
