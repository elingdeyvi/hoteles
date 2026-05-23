<?php

namespace App\Models\Concerns;

use App\Support\CurrentProperty;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToProperty
{
    public static function bootBelongsToProperty(): void
    {
        static::addGlobalScope('property', function (Builder $builder): void {
            $propertyId = CurrentProperty::id();
            if ($propertyId) {
                $builder->where($builder->getModel()->getTable().'.property_id', $propertyId);
            }
        });

        static::creating(function ($model): void {
            if (! $model->property_id && CurrentProperty::id()) {
                $model->property_id = CurrentProperty::id();
            }
        });
    }

    public function property()
    {
        return $this->belongsTo(\App\Models\Property::class);
    }
}
