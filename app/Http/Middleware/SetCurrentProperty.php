<?php

namespace App\Http\Middleware;

use App\Models\Property;
use App\Support\CurrentProperty;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentProperty
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $user = $request->user();

            if ($request->route('property')) {
                $property = $request->route('property');
                if (is_string($property)) {
                    $property = Property::query()
                        ->where('code', $property)
                        ->where('is_active', true)
                        ->firstOrFail();
                }
                CurrentProperty::set($property);
            } elseif ($header = $request->header('X-Property-Id')) {
                $property = Property::query()->where('id', (int) $header)->where('is_active', true)->first();
                if ($property) {
                    CurrentProperty::set($property);
                }
            } elseif (! CurrentProperty::id()) {
                $defaultQuery = Property::query()->where('is_active', true)->orderBy('sort_order');
                if ($user && ! $user->isHotelAdmin()) {
                    $accessibleIds = $user->accessiblePropertyIds();
                    if ($accessibleIds !== []) {
                        $defaultQuery->whereIn('id', $accessibleIds);
                    }
                }
                $default = $defaultQuery->first();
                if ($default) {
                    CurrentProperty::set($default);
                }
            }

            if ($user && CurrentProperty::id() && ! $user->canAccessProperty(CurrentProperty::id())) {
                abort(403, 'No tiene acceso a este hotel.');
            }

            return $next($request);
        } finally {
            CurrentProperty::clear();
        }
    }
}
