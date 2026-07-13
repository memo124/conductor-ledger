<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Currency extends Model
{
    public $incrementing = false;

    protected $primaryKey = 'code';

    protected $keyType = 'string';

    protected $fillable = [
        'code',
        'name',
        'name_es',
        'symbol',
        'currency_type',
        'decimal_places',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'decimal_places' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function exchangeRate(): HasOne
    {
        return $this->hasOne(ExchangeRate::class, 'target_currency', 'code')
            ->where('base_currency', config('conductor-ledger.currency.base', 'USD'));
    }

    public function displayName(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();

        if ($locale === 'es' && $this->name_es) {
            return $this->name_es;
        }

        return $this->name;
    }
}
