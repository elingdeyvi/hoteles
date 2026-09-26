<?php

namespace App\Http\Controllers;

use App\Models\RoomRate;
use App\Models\RoomType;
use App\Models\SeasonRate;
use App\Support\CatalogDelete;
use App\Support\CurrentProperty;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoomRateController extends Controller
{
    public function index(Request $request): Response
    {
        $query = RoomRate::query()->with(['roomType', 'seasonRates']);

        if ($request->filled('room_type_id')) {
            $query->where('room_type_id', $request->integer('room_type_id'));
        }

        return Inertia::render('Hotel/Rates/Index', [
            'rates' => $query->orderBy('name')->get(),
            'roomTypes' => RoomType::query()->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['room_type_id']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'room_type_id' => ['required', 'exists:room_types,id'],
            'name' => ['required', 'string', 'max:120'],
            'price' => ['required', 'numeric', 'min:0'],
            'is_weekend' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        $data['is_weekend'] = $request->boolean('is_weekend');
        $data['is_active'] = $request->boolean('is_active', true);
        RoomRate::create($data);

        return back()->with('success', 'Tarifa creada.');
    }

    public function update(Request $request, RoomRate $roomRate): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'price' => ['required', 'numeric', 'min:0'],
            'is_weekend' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        $data['is_weekend'] = $request->boolean('is_weekend');
        $data['is_active'] = $request->boolean('is_active');
        $roomRate->update($data);

        return back()->with('success', 'Tarifa actualizada.');
    }

    public function destroy(RoomRate $roomRate): RedirectResponse
    {
        $this->tarifaDelHotel($roomRate);

        return CatalogDelete::run($roomRate, [], 'Tarifa eliminada.');
    }

    public function destroySeason(SeasonRate $seasonRate): RedirectResponse
    {
        $seasonRate->load('roomRate');
        $this->tarifaDelHotel($seasonRate->roomRate);

        return CatalogDelete::run($seasonRate, [], 'Temporada eliminada.');
    }

    private function tarifaDelHotel(?RoomRate $roomRate): void
    {
        $roomRate?->loadMissing('roomType');
        abort_unless(
            $roomRate?->roomType && (int) $roomRate->roomType->property_id === (int) CurrentProperty::id(),
            404
        );
    }

    public function storeSeason(Request $request, RoomRate $roomRate): RedirectResponse
    {
        $data = $request->validate([
            'season_name' => ['required', 'string', 'max:120'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'price_override' => ['nullable', 'numeric', 'min:0'],
            'multiplier' => ['nullable', 'numeric', 'min:0.1'],
            'is_active' => ['boolean'],
        ]);

        $data['room_rate_id'] = $roomRate->id;
        $data['multiplier'] = $data['multiplier'] ?? 1;
        $data['is_active'] = $request->boolean('is_active', true);
        SeasonRate::create($data);

        return back()->with('success', 'Temporada agregada.');
    }
}
