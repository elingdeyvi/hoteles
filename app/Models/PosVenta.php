<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProperty;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosVenta extends Model
{
    use BelongsToProperty;

    protected $table = 'pos_ventas';

    protected $fillable = [
        'property_id',
        'apertura_caja_id',
        'user_id',
        'numero',
        'payment_method',
        'total',
        'recibido',
        'cambio',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'recibido' => 'decimal:2',
        'cambio' => 'decimal:2',
    ];

    public function lineas(): HasMany
    {
        return $this->hasMany(PosVentaLinea::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function apertura(): BelongsTo
    {
        return $this->belongsTo(AperturaCaja::class, 'apertura_caja_id');
    }
}
