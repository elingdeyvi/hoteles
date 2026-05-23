<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FolioCharge extends Model
{
    protected $fillable = [
        'folio_id',
        'concept',
        'charge_type',
        'amount',
        'quantity',
        'pos_product_id',
        'charged_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function folio(): BelongsTo
    {
        return $this->belongsTo(Folio::class);
    }

    public function posProduct(): BelongsTo
    {
        return $this->belongsTo(PosProduct::class);
    }

    public function chargedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'charged_by');
    }
}
