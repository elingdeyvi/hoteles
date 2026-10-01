<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosVentaLinea extends Model
{
    protected $table = 'pos_venta_lineas';

    protected $fillable = [
        'pos_venta_id',
        'pos_product_id',
        'nombre',
        'cantidad',
        'precio',
        'costo',
        'importe',
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'precio' => 'decimal:2',
        'costo' => 'decimal:2',
        'importe' => 'decimal:2',
    ];

    public function venta(): BelongsTo
    {
        return $this->belongsTo(PosVenta::class, 'pos_venta_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(PosProduct::class, 'pos_product_id');
    }
}
