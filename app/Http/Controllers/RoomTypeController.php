<?php

namespace App\Http\Controllers;

use App\Models\RoomType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoomTypeController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => RoomType::query()->withCount('rooms')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['nullable', 'string', 'max:40'],
            'description' => ['nullable', 'string'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:20'],
            'amenities' => ['nullable', 'array'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        if (empty($data['code'])) {
            $data['code'] = Str::slug($data['name']);
        }

        $roomType = RoomType::create($data);

        return response()->json(['data' => $roomType], 201);
    }

    public function update(Request $request, RoomType $roomType): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'code' => ['sometimes', 'string', 'max:40'],
            'description' => ['nullable', 'string'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:20'],
            'amenities' => ['nullable', 'array'],
            'base_price' => ['sometimes', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $roomType->update($data);

        return response()->json(['data' => $roomType->fresh()]);
    }

    public function destroy(RoomType $roomType): JsonResponse
    {
        $roomType->delete();

        return response()->json(['message' => 'Tipo de habitación eliminado.']);
    }
}
