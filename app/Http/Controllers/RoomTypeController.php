<?php

namespace App\Http\Controllers;

use App\Models\RoomType;
use App\Support\CatalogDelete;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class RoomTypeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Hotel/RoomTypes/Index', [
            'roomTypes' => RoomType::query()->withCount('rooms')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['nullable', 'string', 'max:40'],
            'description' => ['nullable', 'string'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:20'],
            'amenities' => ['nullable', 'string'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'hourly_price' => ['nullable', 'numeric', 'min:0'],
            'extra_person_price' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $data['code'] = $data['code'] ?: Str::slug($data['name']);
        $data['capacity'] = $data['capacity'] ?? 2;
        $data['is_active'] = $request->boolean('is_active', true);
        $data['amenities'] = $this->amenities($data['amenities'] ?? null);

        RoomType::create($data);

        return back()->with('success', 'Tipo de habitación creado.');
    }

    public function update(Request $request, RoomType $roomType): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:40'],
            'description' => ['nullable', 'string'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:20'],
            'amenities' => ['nullable', 'string'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'hourly_price' => ['nullable', 'numeric', 'min:0'],
            'extra_person_price' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['amenities'] = $this->amenities($data['amenities'] ?? null);
        $roomType->update($data);

        return back()->with('success', 'Tipo de habitación actualizado.');
    }

    public function destroy(RoomType $roomType): RedirectResponse
    {
        return CatalogDelete::run($roomType, [
            'habitaciones' => $roomType->rooms()->exists(),
            'tarifas' => $roomType->rates()->exists(),
            'reservaciones' => $roomType->reservations()->exists(),
        ], 'Tipo de habitación eliminado.');
    }

    private function amenities(?string $raw): ?array
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }
}
