<?php

namespace App\Support;

class HomeRedirect
{
    public static function url(): string
    {
        $user = auth()->user();

        if ($user && ! $user->can('dashboard.ver') && $user->can('pos.vender')) {
            return route('pos.index', absolute: false);
        }

        if ($user && ! $user->can('dashboard.ver') && $user->can('housekeeping.gestionar')) {
            return route('housekeeping.index', absolute: false);
        }

        return route('dashboard', absolute: false);
    }
}
