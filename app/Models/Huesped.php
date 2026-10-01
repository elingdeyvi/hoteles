<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Huesped extends Model
{
    protected $table = 'huespedes';

    protected $appends = ['identificacion_url'];

    protected $fillable = [
        'nombre',
        'email',
        'telefono',
        'documento',
        'identificacion_path',
        'nacionalidad',
        'direccion',
        'notas',
    ];

    public function getIdentificacionUrlAttribute(): ?string
    {
        if (! $this->identificacion_path) {
            return null;
        }

        return asset('storage/'.$this->identificacion_path);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'huesped_id');
    }
}
