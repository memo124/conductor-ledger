<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleOwnershipType extends Model
{
    public $timestamps = false;

    protected $fillable = ['name'];

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'ownership_type_id');
    }
}
