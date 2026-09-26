<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProperty;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrintJob extends Model
{
    use BelongsToProperty;

    public const ESTADO_PENDING = 'pending';

    public const ESTADO_PROCESSING = 'processing';

    public const ESTADO_COMPLETED = 'completed';

    public const ESTADO_FAILED = 'failed';

    public const TIPO_RESERVA = 'reserva';

    public const TIPO_FOLIO = 'folio';

    public const TIPO_PRUEBA = 'prueba';

    protected $fillable = [
        'property_id',
        'impresora_id',
        'user_id',
        'reservation_id',
        'folio_id',
        'tipo',
        'estado',
        'contenido',
        'columnas',
        'impresora_snapshot',
        'intentos',
        'max_intentos',
        'error_mensaje',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'impresora_snapshot' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function impresora(): BelongsTo
    {
        return $this->belongsTo(Impresora::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
