<?php

namespace App\Http\Controllers;

use App\Models\Huesped;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Services\HotelPricingService;
use App\Services\OnlineBookingService;
use App\Services\ReservationService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ReservationController extends Controller
{
    public function __construct(
        private readonly ReservationService $reservations,
        private readonly HotelPricingService $pricing,
        private readonly OnlineBookingService $onlineBooking
    ) {}

    public function index(Request $request): Response
    {
        $query = Reservation::query()
            ->with(['huesped', 'roomType', 'room'])
            ->orderByDesc('check_in');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('source')) {
            $query->where('source', $request->string('source'));
        }

        if ($request->filled('from')) {
            $query->whereDate('check_in', '>=', $request->date('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('check_out', '<=', $request->date('to'));
        }

        $availability = null;
        if ($request->filled('avail_in') && $request->filled('avail_out')) {
            $checkIn = Carbon::parse($request->get('avail_in'))->startOfDay();
            $checkOut = Carbon::parse($request->get('avail_out'))->startOfDay();
            $modalidad = $request->get('modalidad') === 'horas' ? 'horas' : 'noche';
            $horas = max(1, (int) $request->get('horas', 1));
            $personasExtra = max(0, (int) $request->get('personas_extra', 0));
            $exclude = $request->filled('exclude') ? (int) $request->get('exclude') : null;
            if ($checkOut->gt($checkIn)) {
                $availability = RoomType::query()->where('is_active', true)->orderBy('name')->get()->map(function (RoomType $type) use ($checkIn, $checkOut, $modalidad, $horas, $personasExtra, $exclude) {
                    $free = $this->reservations->availableRooms($type, $checkIn, $checkOut, $exclude);
                    $cotizacion = $this->pricing->cotizar($type, $checkIn, $checkOut, $modalidad, $horas, $personasExtra);

                    return [
                        'room_type' => $type->only(['id', 'name', 'base_price', 'capacity', 'hourly_price', 'extra_person_price']),
                        'available_rooms' => $free->count(),
                        'room_ids' => $free->pluck('id')->values(),
                        'estimated_total' => $cotizacion['total'],
                        'nightly_rate' => $cotizacion['tarifa'],
                        'monto_extra' => $cotizacion['extra'],
                        'modalidad' => $modalidad,
                    ];
                });
            }
        }

        return Inertia::render('Hotel/Reservations/Index', [
            'reservations' => $query->paginate(20)->withQueryString(),
            'huespedes' => Huesped::query()->orderBy('nombre')->get(['id', 'nombre', 'email']),
            'roomTypes' => RoomType::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'base_price', 'hourly_price', 'extra_person_price']),
            'rooms' => Room::query()->where('is_active', true)->orderBy('number')->get(['id', 'number', 'room_type_id', 'status']),
            'filters' => $request->only(['status', 'source', 'from', 'to', 'avail_in', 'avail_out', 'create', 'check_in', 'check_out']),
            'availability' => $availability,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->datosReserva($request);
        $this->asegurarDisponibilidad($data);

        Reservation::create([
            ...$data,
            'folio' => $this->reservations->generateFolio(),
            'source' => 'recepcion',
            'status' => 'confirmada',
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Reserva confirmada.');
    }

    public function update(Request $request, Reservation $reservation): RedirectResponse
    {
        if (in_array($reservation->status, ['check_in', 'check_out', 'cancelada'], true)) {
            return back()->with('error', 'Esta reserva ya no se puede editar.');
        }

        $data = $this->datosReserva($request, false);
        if (empty($data['huesped_id'])) {
            unset($data['huesped_id']);
        }
        $this->asegurarDisponibilidad($data, $reservation->id);
        $reservation->update($data);

        return back()->with('success', 'Reserva actualizada.');
    }

    public function cancel(Reservation $reservation): RedirectResponse
    {
        if ($reservation->status === 'check_in') {
            return back()->with('error', 'No se puede cancelar una reserva con check-in activo.');
        }

        $reservation->update(['status' => 'cancelada']);

        return back()->with('success', 'Reserva cancelada.');
    }

    public function confirm(Reservation $reservation): RedirectResponse
    {
        try {
            $this->onlineBooking->confirmWebReservation($reservation);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        return back()->with('success', 'Reserva confirmada.');
    }

    /**
     * @return array<string, mixed>
     */
    private function datosReserva(Request $request, bool $requiereHuesped = true): array
    {
        $rules = [
            'room_type_id' => ['required', 'exists:room_types,id'],
            'room_id' => ['nullable', 'exists:rooms,id'],
            'check_in' => ['required', 'date'],
            'check_out' => ['required', 'date'],
            'modalidad' => ['required', 'in:noche,horas'],
            'hora_entrada' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'hora_salida' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'horas' => ['nullable', 'integer', 'min:1', 'max:24'],
            'guests_count' => ['nullable', 'integer', 'min:1', 'max:20'],
            'personas_extra' => ['nullable', 'integer', 'min:0', 'max:10'],
            'notes' => ['nullable', 'string'],
            'requiere_factura' => ['nullable', 'boolean'],
        ];

        if ($requiereHuesped) {
            $rules['huesped_id'] = ['required', 'exists:huespedes,id'];
        } else {
            $rules['huesped_id'] = ['nullable', 'exists:huespedes,id'];
        }

        $data = $request->validate($rules);
        $modalidad = $data['modalidad'];
        $checkIn = Carbon::parse($data['check_in'])->startOfDay();
        $checkOut = Carbon::parse($data['check_out'])->startOfDay();

        if ($modalidad === 'horas') {
            if (empty($data['horas'])) {
                throw ValidationException::withMessages([
                    'horas' => 'Indica cuántas horas dura la estancia.',
                ]);
            }
            $checkOut = $checkIn->copy();
        } elseif (! $checkOut->gt($checkIn)) {
            throw ValidationException::withMessages([
                'check_out' => 'La salida debe ser posterior a la entrada.',
            ]);
        }

        $roomType = RoomType::findOrFail($data['room_type_id']);
        $personasExtra = (int) ($data['personas_extra'] ?? 0);
        $horas = (int) ($data['horas'] ?? 1);
        $finCotizacion = $modalidad === 'horas' ? $checkIn->copy()->addDay() : $checkOut;
        $cotizacion = $this->pricing->cotizar($roomType, $checkIn, $finCotizacion, $modalidad, $horas, $personasExtra);

        return [
            'huesped_id' => $data['huesped_id'] ?? null,
            'room_type_id' => $data['room_type_id'],
            'room_id' => $data['room_id'] ?: null,
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'modalidad' => $modalidad,
            'hora_entrada' => $this->hora($data['hora_entrada'] ?? null),
            'hora_salida' => $this->hora($data['hora_salida'] ?? null),
            'horas' => $modalidad === 'horas' ? $horas : null,
            'guests_count' => $data['guests_count'] ?? 1,
            'personas_extra' => $personasExtra,
            'tarifa_persona_extra' => $cotizacion['tarifa_persona_extra'],
            'estimated_total' => $cotizacion['total'],
            'monto_hospedaje' => $cotizacion['hospedaje'],
            'monto_extra' => $cotizacion['extra'],
            'notes' => $data['notes'] ?? null,
            'requiere_factura' => $request->boolean('requiere_factura'),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function asegurarDisponibilidad(array $data, ?int $excludeId = null): void
    {
        $checkIn = Carbon::parse($data['check_in']);
        $checkOut = $data['modalidad'] === 'horas'
            ? $checkIn->copy()->addDay()
            : Carbon::parse($data['check_out']);
        $roomType = RoomType::findOrFail($data['room_type_id']);

        if (! empty($data['room_id'])) {
            $room = Room::findOrFail($data['room_id']);
            if (! $this->reservations->isRoomAvailable($room, $checkIn, $checkOut, $excludeId)) {
                throw ValidationException::withMessages([
                    'room_id' => 'La habitación no está disponible en esas fechas.',
                ]);
            }
        } elseif ($this->reservations->availableRooms($roomType, $checkIn, $checkOut, $excludeId)->count() < 1) {
            throw ValidationException::withMessages([
                'room_type_id' => 'No hay habitaciones disponibles de ese tipo en esas fechas.',
            ]);
        }
    }

    private function hora(?string $valor): ?string
    {
        if (! $valor) {
            return null;
        }

        return substr($valor, 0, 5);
    }
}
