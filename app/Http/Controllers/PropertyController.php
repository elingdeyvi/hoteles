<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracionEmpresa;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PropertyController extends Controller
{
    /** Listado activo (selector de hotel en header). */
    public function index(): JsonResponse
    {
        $query = Property::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name');

        $user = auth()->user();
        if ($user && ! $user->isHotelAdmin()) {
            $query->whereIn('id', $user->accessiblePropertyIds());
        }

        return response()->json([
            'data' => $query->get(['id', 'code', 'name', 'currency', 'booking_enabled']),
        ]);
    }

    /** Administración: todos los hoteles. */
    public function manage(): JsonResponse
    {
        $properties = Property::query()
            ->withCount(['roomTypes', 'reservations'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $properties]);
    }

    public function store(Request $request): JsonResponse
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

        return response()->json(['data' => $property->fresh()], 201);
    }

    public function update(Request $request, Property $property): JsonResponse
    {
        $data = $this->validated($request, $property);

        $property->update($data);

        return response()->json(['data' => $property->fresh()]);
    }

    public function destroy(Property $property): JsonResponse
    {
        if (Property::query()->where('is_active', true)->count() <= 1 && $property->is_active) {
            throw ValidationException::withMessages([
                'property' => ['Debe existir al menos un hotel activo.'],
            ]);
        }

        if ($property->roomTypes()->exists() || $property->reservations()->exists()) {
            $property->update(['is_active' => false, 'booking_enabled' => false]);

            return response()->json([
                'message' => 'El hotel fue desactivado (tiene datos operativos).',
                'data' => $property->fresh(),
            ]);
        }

        ConfiguracionEmpresa::query()->where('property_id', $property->id)->delete();
        $property->delete();

        return response()->json(['message' => 'Hotel eliminado.']);
    }

    private function validated(Request $request, ?Property $property = null): array
    {
        $propertyId = $property?->id;

        $data = $request->validate([
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:40',
                'regex:/^[a-z0-9\-]+$/',
                Rule::unique('properties', 'code')->ignore($propertyId),
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
        }

        if (! empty($data['code'])) {
            $data['code'] = Str::slug($data['code']);
        }

        if (! empty($data['currency'])) {
            $data['currency'] = strtolower($data['currency']);
        }

        return $data;
    }
}
