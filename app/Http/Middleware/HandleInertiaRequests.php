<?php

namespace App\Http\Middleware;

use App\Models\ConfiguracionEmpresa;
use App\Services\CajaService;
use App\Models\Property;
use App\Support\BrandAssets;
use App\Support\CurrentProperty;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                'permissions' => $user
                    ? $user->getAllPermissions()->pluck('name')->values()->all()
                    : [],
                'roles' => $user
                    ? $user->getRoleNames()->values()->all()
                    : [],
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'appName' => config('app.name', 'Hotel'),
            'empresa' => fn () => $this->empresaProps(),
            'currentProperty' => fn () => CurrentProperty::get()?->only(['id', 'code', 'name', 'currency']),
            'cajaAbierta' => fn () => ($user && CurrentProperty::id())
                ? app(CajaService::class)->paraVista()
                : null,
            'properties' => fn () => $this->accessibleProperties($request),
            'hotelCatalogs' => fn () => [
                'room_statuses' => config('hotel.room_statuses'),
                'reservation_statuses' => config('hotel.reservation_statuses'),
                'payment_methods' => config('hotel.payment_methods'),
                'charge_types' => config('hotel.charge_types'),
                'reservation_sources' => config('hotel.reservation_sources'),
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function accessibleProperties(Request $request): array
    {
        $user = $request->user();
        if (! $user) {
            return [];
        }

        $query = Property::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name');

        if (! $user->isHotelAdmin()) {
            $ids = $user->accessiblePropertyIds();
            if ($ids !== []) {
                $query->whereIn('id', $ids);
            }
        }

        return $query->get(['id', 'code', 'name', 'currency'])->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    private function empresaProps(): array
    {
        $property = CurrentProperty::get();
        $config = ConfiguracionEmpresa::obtenerConfiguracion($property?->id);
        $logo = $config?->logo_url;
        $mark = ($config && $config->logo_path)
            ? $logo
            : (BrandAssets::markUrl($property?->code) ?: $logo);

        return [
            'nombre' => $config?->nombre_empresa ?: config('app.name', 'Hotel'),
            'nombre_corto' => $config?->nombre_corto ?: 'Hotel',
            'logo_url' => $logo,
            'logo_mark_url' => $mark,
            'color_primario' => $config?->color_primario ?: '#1a365d',
            'color_secundario' => $config?->color_secundario ?: '#1e293b',
            'tema_modo' => $config?->tema_modo ?: 'claro',
            'telefono' => $config?->telefono,
            'email' => $config?->email,
        ];
    }
}
