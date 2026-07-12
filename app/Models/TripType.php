<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TripType extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'code',
        'name',
        'allowed_modes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function allowedModesList(): array
    {
        return array_filter(explode(',', $this->allowed_modes ?? ''));
    }

    public function allowsMode(string $mode): bool
    {
        return in_array($mode, $this->allowedModesList(), true);
    }
}
