<?php

namespace App\Http\Controllers;

use App\Models\RoomRate;
use App\Models\SeasonRate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomRateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = RoomRate::query()->with(['roomType', 'seasonRates']);

        if ($request->filled('room_type_id')) {
            $query->where('room_type_id', $request->integer('room_type_id'));
        }

        return response()->json(['data' => $query->orderBy('name')->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'room_type_id' => ['required', 'exists:room_types,id'],
            'name' => ['required', 'string', 'max:120'],
            'price' => ['required', 'numeric', 'min:0'],
            'is_weekend' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        return response()->json(['data' => RoomRate::create($data)->load('roomType')], 201);
    }

    public function update(Request $request, RoomRate $roomRate): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'is_weekend' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        $roomRate->update($data);

        return response()->json(['data' => $roomRate->fresh(['roomType', 'seasonRates'])]);
    }

    public function storeSeason(Request $request, RoomRate $roomRate): JsonResponse
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
        $season = SeasonRate::create($data);

        return response()->json(['data' => $season], 201);
    }
}
