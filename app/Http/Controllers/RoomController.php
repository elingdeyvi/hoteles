<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Room::query()->with('roomType');

        if ($request->filled('room_type_id')) {
            $query->where('room_type_id', $request->integer('room_type_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return response()->json(['data' => $query->orderBy('number')->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'room_type_id' => ['required', 'exists:room_types,id'],
            'number' => ['required', 'string', 'max:20', 'unique:rooms,number'],
            'floor' => ['nullable', 'integer', 'min:0', 'max:99'],
            'status' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $room = Room::create($data);

        return response()->json(['data' => $room->load('roomType')], 201);
    }

    public function update(Request $request, Room $room): JsonResponse
    {
        $data = $request->validate([
            'room_type_id' => ['sometimes', 'exists:room_types,id'],
            'number' => ['sometimes', 'string', 'max:20', 'unique:rooms,number,'.$room->id],
            'floor' => ['nullable', 'integer', 'min:0', 'max:99'],
            'status' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $room->update($data);

        return response()->json(['data' => $room->fresh('roomType')]);
    }

    public function destroy(Room $room): JsonResponse
    {
        $room->delete();

        return response()->json(['message' => 'Habitación eliminada.']);
    }
}
