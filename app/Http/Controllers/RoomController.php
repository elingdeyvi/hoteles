<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\RoomType;
use App\Support\CatalogDelete;
use App\Support\CurrentProperty;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class RoomController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Room::query()->with('roomType');

        if ($request->filled('room_type_id')) {
            $query->where('room_type_id', $request->integer('room_type_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return Inertia::render('Hotel/Rooms/Index', [
            'rooms' => $query->orderBy('number')->get(),
            'roomTypes' => RoomType::query()->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['room_type_id', 'status']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'room_type_id' => ['required', 'exists:room_types,id'],
            'number' => [
                'required',
                'string',
                'max:20',
                Rule::unique('rooms', 'number')->where(fn ($q) => $q->where('property_id', CurrentProperty::id())),
            ],
            'floor' => ['nullable', 'integer', 'min:0', 'max:99'],
            'status' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $data['floor'] = $data['floor'] ?? 1;
        $data['status'] = $data['status'] ?? 'disponible';
        $data['is_active'] = $request->boolean('is_active', true);
        Room::create($data);

        return back()->with('success', 'Habitación creada.');
    }

    public function update(Request $request, Room $room): RedirectResponse
    {
        $data = $request->validate([
            'room_type_id' => ['required', 'exists:room_types,id'],
            'number' => [
                'required',
                'string',
                'max:20',
                Rule::unique('rooms', 'number')->ignore($room->id)->where(fn ($q) => $q->where('property_id', $room->property_id)),
            ],
            'floor' => ['nullable', 'integer', 'min:0', 'max:99'],
            'status' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $room->update($data);

        return back()->with('success', 'Habitación actualizada.');
    }

    public function destroy(Room $room): RedirectResponse
    {
        return CatalogDelete::run($room, [
            'reservaciones' => $room->reservations()->exists(),
            'estancias' => $room->stays()->exists(),
        ], 'Habitación eliminada.');
    }
}
