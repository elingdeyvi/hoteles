<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AperturaCaja extends Model
{
    protected $table = 'aperturas_caja';

    protected $fillable = [
        'caja_id',
        'user_id',
        'fondo_inicial',
        'fecha_apertura',
        'fecha_cierre',
        'total_consumos',
        'total_efectivo',
        'total_tarjeta',
        'total_transferencia',
        'total_otros',
        'total_cobros',
        'total_esperado',
        'total_real',
        'diferencia',
        'observaciones',
        'cerrada',
    ];

    protected $casts = [
        'fondo_inicial' => 'decimal:2',
        'total_consumos' => 'decimal:2',
        'total_efectivo' => 'decimal:2',
        'total_tarjeta' => 'decimal:2',
        'total_transferencia' => 'decimal:2',
        'total_otros' => 'decimal:2',
        'total_cobros' => 'decimal:2',
        'total_esperado' => 'decimal:2',
        'total_real' => 'decimal:2',
        'diferencia' => 'decimal:2',
        'cerrada' => 'boolean',
        'fecha_apertura' => 'datetime',
        'fecha_cierre' => 'datetime',
    ];

    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cortes(): HasMany
    {
        return $this->hasMany(CorteCaja::class);
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(FolioPayment::class);
    }

    public function cargos(): HasMany
    {
        return $this->hasMany(FolioCharge::class);
    }
}
