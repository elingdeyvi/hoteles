<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FolioPayment extends Model
{
    protected $fillable = [
        'folio_id',
        'payment_method',
        'amount',
        'recibido',
        'cambio',
        'reference',
        'received_by',
        'apertura_caja_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'recibido' => 'decimal:2',
        'cambio' => 'decimal:2',
    ];

    public function folio(): BelongsTo
    {
        return $this->belongsTo(Folio::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
