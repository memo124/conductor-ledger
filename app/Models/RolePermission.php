<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RolePermission extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $primaryKey = 'role_id';

    protected $fillable = [
        'role_id',
        'app_option_id',
        'can_view',
        'can_create',
        'can_update',
        'can_delete',
    ];

    protected function casts(): array
    {
        return [
            'can_view' => 'boolean',
            'can_create' => 'boolean',
            'can_update' => 'boolean',
            'can_delete' => 'boolean',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function appOption(): BelongsTo
    {
        return $this->belongsTo(AppOption::class);
    }
}
