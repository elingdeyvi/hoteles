<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProperty;
use App\Support\ImpresoraDestino;
use Illuminate\Database\Eloquent\Model;

class Impresora extends Model
{
    use BelongsToProperty;

    protected $table = 'impresoras';

    protected $fillable = [
        'property_id',
        'nombre',
        'nombre_sistema',
        'ip',
        'puerto',
        'ancho_papel',
        'activo',
        'es_default',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'es_default' => 'boolean',
        'puerto' => 'integer',
        'ancho_papel' => 'integer',
    ];

    public function destino(): ImpresoraDestino
    {
        return new ImpresoraDestino(
            $this->nombre_sistema,
            $this->ip,
            (int) ($this->puerto ?: 9100),
            (int) ($this->ancho_papel ?: 80),
            $this->nombre,
        );
    }
}
