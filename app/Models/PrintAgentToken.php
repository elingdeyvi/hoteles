<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProperty;
use Illuminate\Database\Eloquent\Model;

class PrintAgentToken extends Model
{
    use BelongsToProperty;

    protected $fillable = [
        'property_id',
        'nombre',
        'token_hash',
        'activo',
        'last_seen_at',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }
}
