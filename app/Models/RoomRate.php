<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoomRate extends Model
{
    protected $fillable = [
        'room_type_id',
        'name',
        'price',
        'is_weekend',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_weekend' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function seasonRates(): HasMany
    {
        return $this->hasMany(SeasonRate::class);
    }
}
