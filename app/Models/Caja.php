<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProperty;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Caja extends Model
{
    use BelongsToProperty;

    protected $fillable = ['property_id', 'codigo', 'nombre', 'activo'];

    protected $casts = ['activo' => 'boolean'];

    public function aperturas(): HasMany
    {
        return $this->hasMany(AperturaCaja::class);
    }

    public function aperturaActual(): HasOne
    {
        return $this->hasOne(AperturaCaja::class)->where('cerrada', false)->latestOfMany();
    }
}
