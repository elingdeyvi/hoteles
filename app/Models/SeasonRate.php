<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeasonRate extends Model
{
    protected $fillable = [
        'room_rate_id',
        'season_name',
        'starts_on',
        'ends_on',
        'price_override',
        'multiplier',
        'is_active',
    ];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
        'price_override' => 'decimal:2',
        'multiplier' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function roomRate(): BelongsTo
    {
        return $this->belongsTo(RoomRate::class);
    }
}
