<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CorteCaja extends Model
{
    public const TIPO_X = 'X';

    public const TIPO_Z = 'Z';

    protected $table = 'cortes_caja';

    protected $fillable = [
        'apertura_caja_id',
        'caja_id',
        'user_id',
        'tipo',
        'fecha_corte',
        'fondo_inicial',
        'total_consumos',
        'total_efectivo',
        'total_tarjeta',
        'total_transferencia',
        'total_otros',
        'total_cobros',
        'total_ingresos',
        'total_egresos',
        'total_esperado',
        'total_real',
        'diferencia',
        'observaciones',
        'conteo',
    ];

    protected $casts = [
        'fondo_inicial' => 'decimal:2',
        'total_consumos' => 'decimal:2',
        'total_efectivo' => 'decimal:2',
        'total_tarjeta' => 'decimal:2',
        'total_transferencia' => 'decimal:2',
        'total_otros' => 'decimal:2',
        'total_cobros' => 'decimal:2',
        'total_ingresos' => 'decimal:2',
        'total_egresos' => 'decimal:2',
        'total_esperado' => 'decimal:2',
        'total_real' => 'decimal:2',
        'diferencia' => 'decimal:2',
        'fecha_corte' => 'datetime',
        'conteo' => 'array',
    ];

    public function apertura(): BelongsTo
    {
        return $this->belongsTo(AperturaCaja::class, 'apertura_caja_id');
    }

    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
