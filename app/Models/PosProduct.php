<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosProduct extends Model
{
    protected $fillable = [
        'pos_category_id',
        'name',
        'sku',
        'price',
        'iva_porcentaje',
        'aplicar_iva',
        'costo',
        'piezas_caja',
        'controla_inventario',
        'stock_actual',
        'stock_minimo',
        'bodega',
        'exhibicion',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'iva_porcentaje' => 'decimal:2',
        'aplicar_iva' => 'boolean',
        'costo' => 'decimal:2',
        'bodega' => 'decimal:2',
        'exhibicion' => 'decimal:2',
        'controla_inventario' => 'boolean',
        'stock_actual' => 'decimal:2',
        'stock_minimo' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(PosCategory::class, 'pos_category_id');
    }

    public function importeCargo(): float
    {
        $base = (float) $this->price;
        if (! $this->aplicar_iva) {
            return round($base, 2);
        }

        return round($base * (1 + ((float) $this->iva_porcentaje / 100)), 2);
    }
}
