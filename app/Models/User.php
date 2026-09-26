<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    public const ADMIN_ROL = 'Administrador';

    public const RECEPCIONISTA_ROL = 'Recepcionista';

    public const HOUSEKEEPING_ROL = 'Housekeeping';

    public const CAJERO_ROL = 'Cajero';

    public const ROLES = [
        self::ADMIN_ROL,
        self::RECEPCIONISTA_ROL,
        self::HOUSEKEEPING_ROL,
        self::CAJERO_ROL,
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'estatus',
        'uuid',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function properties(): BelongsToMany
    {
        return $this->belongsToMany(Property::class)
            ->withPivot('is_default')
            ->withTimestamps();
    }

    public function isHotelAdmin(): bool
    {
        return $this->hasRole(self::ADMIN_ROL);
    }

    /** @return list<int> */
    public function accessiblePropertyIds(): array
    {
        if ($this->isHotelAdmin()) {
            return Property::query()->where('is_active', true)->pluck('id')->all();
        }

        $ids = $this->properties()
            ->where('properties.is_active', true)
            ->pluck('properties.id')
            ->all();

        if ($ids === []) {
            return Property::query()->where('is_active', true)->pluck('id')->all();
        }

        return $ids;
    }

    public function canAccessProperty(?int $propertyId): bool
    {
        if (! $propertyId) {
            return true;
        }

        return in_array($propertyId, $this->accessiblePropertyIds(), true);
    }

    public function scopeActivos($query)
    {
        return $query->where('estatus', 'activo');
    }
}
