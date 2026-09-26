<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracionEmpresa;
use App\Models\Property;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PropertyController extends Controller
{
    public function index(): Response
    {
        $properties = Property::query()
            ->withCount(['roomTypes', 'reservations'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return Inertia::render('Hotel/Properties/Index', [
            'properties' => $properties,
        ]);
    }

    public function switch(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'property_id' => ['required', 'integer', 'exists:properties,id'],
        ]);

        if (! $request->user()->canAccessProperty((int) $data['property_id'])) {
            abort(403, 'No tiene acceso a este hotel.');
        }

        $request->session()->put('current_property_id', (int) $data['property_id']);

        return back()->with('success', 'Hotel activo actualizado.');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $property = Property::create($data);

        ConfiguracionEmpresa::query()->create([
            'property_id' => $property->id,
            'nombre_empresa' => $property->name,
            'nombre_corto' => Str::limit($property->name, 20, ''),
            'nombre_largo' => $property->name,
            'email' => $data['email'] ?? null,
            'telefono' => $data['phone'] ?? null,
            'direccion' => $data['address'] ?? null,
            'activo' => true,
        ]);

        return back()->with('success', 'Hotel creado.');
    }

    public function update(Request $request, Property $property): RedirectResponse
    {
        $property->update($this->validated($request, $property));

        return back()->with('success', 'Hotel actualizado.');
    }

    public function destroy(Property $property): RedirectResponse
    {
        if (Property::query()->where('is_active', true)->count() <= 1 && $property->is_active) {
            throw ValidationException::withMessages([
                'property' => ['Debe existir al menos un hotel activo.'],
            ]);
        }

        if ($property->roomTypes()->withoutGlobalScopes()->exists() || $property->reservations()->withoutGlobalScopes()->exists()) {
            $property->update(['is_active' => false, 'booking_enabled' => false]);

            return back()->with('success', 'El hotel fue desactivado porque tiene datos operativos.');
        }

        ConfiguracionEmpresa::query()->where('property_id', $property->id)->delete();
        $property->delete();

        return back()->with('success', 'Hotel eliminado.');
    }

    private function validated(Request $request, ?Property $property = null): array
    {
        $data = $request->validate([
            'code' => [
                'nullable',
                'string',
                'max:40',
                'regex:/^[a-z0-9\-]+$/',
                Rule::unique('properties', 'code')->ignore($property?->id),
            ],
            'name' => ['required', 'string', 'max:120'],
            'timezone' => ['nullable', 'string', 'max:50'],
            'currency' => ['nullable', 'string', 'size:3'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:120'],
            'address' => ['nullable', 'string'],
            'booking_enabled' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        if (! $property && empty($data['code'])) {
            $data['code'] = Str::slug($data['name']);
        } elseif (! empty($data['code'])) {
            $data['code'] = Str::slug($data['code']);
        }

        if (! empty($data['currency'])) {
            $data['currency'] = strtoupper($data['currency']);
        }

        $data['timezone'] = $data['timezone'] ?? 'America/Mexico_City';
        $data['currency'] = $data['currency'] ?? 'MXN';
        $data['booking_enabled'] = $request->boolean('booking_enabled', true);
        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
