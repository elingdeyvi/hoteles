<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Property extends Model
{
    protected $fillable = [
        'code',
        'name',
        'timezone',
        'currency',
        'phone',
        'email',
        'address',
        'booking_enabled',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'booking_enabled' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function roomTypes(): HasMany
    {
        return $this->hasMany(RoomType::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function posOutlets(): HasMany
    {
        return $this->hasMany(PosOutlet::class);
    }

    public function configuracion(): HasOne
    {
        return $this->hasOne(ConfiguracionEmpresa::class);
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }
}
