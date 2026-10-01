<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProperty;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Reservation extends Model
{
    use BelongsToProperty;

    protected $fillable = [
        'property_id',
        'folio',
        'huesped_id',
        'room_type_id',
        'room_id',
        'check_in',
        'check_out',
        'modalidad',
        'hora_entrada',
        'hora_salida',
        'horas',
        'guests_count',
        'personas_extra',
        'tarifa_persona_extra',
        'status',
        'source',
        'online_reference',
        'estimated_total',
        'monto_hospedaje',
        'monto_extra',
        'payment_status',
        'deposit_amount',
        'payment_reference',
        'paid_at',
        'notes',
        'requiere_factura',
        'created_by',
    ];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
        'estimated_total' => 'decimal:2',
        'monto_hospedaje' => 'decimal:2',
        'monto_extra' => 'decimal:2',
        'tarifa_persona_extra' => 'decimal:2',
        'requiere_factura' => 'boolean',
        'personas_extra' => 'integer',
        'horas' => 'integer',
        'deposit_amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function huesped(): BelongsTo
    {
        return $this->belongsTo(Huesped::class, 'huesped_id');
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function stays(): HasMany
    {
        return $this->hasMany(Stay::class);
    }

    public function activeStay(): HasOne
    {
        return $this->hasOne(Stay::class)->where('status', 'activa');
    }
}
