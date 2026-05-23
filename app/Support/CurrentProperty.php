<?php

namespace App\Support;

use App\Models\Property;

class CurrentProperty
{
    protected static ?int $id = null;

    protected static ?Property $model = null;

    public static function set(Property|int $property): void
    {
        if ($property instanceof Property) {
            static::$model = $property;
            static::$id = $property->id;
        } else {
            static::$id = $property;
            static::$model = null;
        }
    }

    public static function setId(?int $id): void
    {
        static::$id = $id;
        static::$model = null;
    }

    public static function id(): ?int
    {
        return static::$id;
    }

    public static function get(): ?Property
    {
        if (static::$model) {
            return static::$model;
        }

        if (static::$id) {
            static::$model = Property::query()->find(static::$id);

            return static::$model;
        }

        return null;
    }

    public static function clear(): void
    {
        static::$id = null;
        static::$model = null;
    }
}
