<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProperty;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosOutlet extends Model
{
    use BelongsToProperty;

    protected $fillable = ['property_id', 'name', 'code', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function categories(): HasMany
    {
        return $this->hasMany(PosCategory::class)->orderBy('sort_order');
    }
}
