<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use LaravelAndVueJS\Traits\LaravelPermissionToVueJS;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasRoles;
    use LaravelPermissionToVueJS;

    public const ADMIN_ROL = 'Administrador';

    public const RECEPCIONISTA_ROL = 'Recepcionista';

    public const HOUSEKEEPING_ROL = 'Housekeeping';

    public const ROLES = [
        self::ADMIN_ROL,
        self::RECEPCIONISTA_ROL,
        self::HOUSEKEEPING_ROL,
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'estatus',
        'autorizado',
        'nota',
        'uuid',
    ];

    public function properties()
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

    protected $hidden = [
        'password',
        'remember_token',
    ];
}
