<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Folio extends Model
{
    protected $fillable = [
        'stay_id',
        'folio_number',
        'balance',
        'status',
        'closed_at',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'closed_at' => 'datetime',
    ];

    public function stay(): BelongsTo
    {
        return $this->belongsTo(Stay::class);
    }

    public function charges(): HasMany
    {
        return $this->hasMany(FolioCharge::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(FolioPayment::class);
    }
}
