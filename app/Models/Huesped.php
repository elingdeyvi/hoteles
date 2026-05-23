<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Huesped extends Model
{
    protected $table = 'huespedes';

    protected $fillable = [
        'nombre',
        'email',
        'telefono',
        'documento',
        'nacionalidad',
        'direccion',
        'notas',
    ];

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'huesped_id');
    }
}
