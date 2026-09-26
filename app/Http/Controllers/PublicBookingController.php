<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracionEmpresa;
use App\Models\Property;
use App\Services\BookingPaymentService;
use App\Services\OnlineBookingService;
use App\Support\BrandAssets;
use App\Support\CurrentProperty;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicBookingController extends Controller
{
    public function __construct(
        private readonly OnlineBookingService $booking,
        private readonly BookingPaymentService $payments
    ) {}

    public function show(Request $request, Property $property): Response
    {
        $this->ensureEnabled($property);

        $empresa = ConfiguracionEmpresa::obtenerConfiguracion($property->id);
        $availability = null;

        if ($request->filled('check_in') && $request->filled('check_out')) {
            $checkIn = Carbon::parse($request->get('check_in'));
            $checkOut = Carbon::parse($request->get('check_out'));
            if ($checkOut->gt($checkIn)) {
                $availability = $this->booking->availability($checkIn, $checkOut, $request->integer('room_type_id') ?: null);
            }
        }

        $lookup = null;
        if ($request->filled('folio') && $request->filled('email')) {
            $found = $this->booking->lookup($request->string('folio'), $request->string('email'));
            $lookup = $found ? [
                'folio' => $found->folio,
                'status' => $found->status,
                'check_in' => $found->check_in?->format('Y-m-d'),
                'check_out' => $found->check_out?->format('Y-m-d'),
                'estimated_total' => $found->estimated_total,
                'payment_status' => $found->payment_status,
                'deposit_amount' => $found->deposit_amount,
                'room_type' => $found->roomType?->name,
                'guest_name' => $found->huesped?->nombre,
                'telefono' => $found->huesped?->telefono,
                'guests_count' => $found->guests_count,
                'room' => ['number' => $found->room?->number],
            ] : ['missing' => true];
        }

        return Inertia::render('Booking/Index', [
            'property' => $property->only(['id', 'code', 'name', 'phone', 'email', 'address']),
            'hotel' => [
                'nombre' => $empresa?->nombre_empresa ?? $property->name,
                'telefono' => $empresa?->telefono ?? $property->phone,
                'email' => $empresa?->email ?? $property->email,
                'direccion' => $empresa?->direccion ?? $property->address,
                'logo_url' => $empresa?->logo_url ?? BrandAssets::logoUrl($property->code),
                'color_primario' => $empresa?->color_primario ?? BrandAssets::primaryColor($property->code),
            ],
            'roomTypes' => $this->booking->activeRoomTypes(),
            'availability' => $availability,
            'lookup' => $lookup,
            'filters' => $request->only(['check_in', 'check_out', 'room_type_id', 'folio', 'email']),
            'booking' => [
                'max_guests' => (int) config('hotel.booking.max_guests', 8),
                'confirmation_note' => config('hotel.booking.confirmation_note'),
                'payments' => [
                    'enabled' => $this->payments->paymentsEnabled(),
                    'deposit_percent' => (int) config('hotel.booking.payments.deposit_percent', 30),
                    'provider' => config('hotel.booking.payments.provider', 'demo'),
                ],
            ],
        ]);
    }

    public function store(Request $request, Property $property): RedirectResponse
    {
        $this->ensureEnabled($property);

        $data = $request->validate([
            'room_type_id' => ['required', 'exists:room_types,id'],
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'guests_count' => ['nullable', 'integer', 'min:1', 'max:'.(int) config('hotel.booking.max_guests', 8)],
            'notes' => ['nullable', 'string', 'max:500'],
            'guest_nombre' => ['required', 'string', 'max:120'],
            'guest_email' => ['required', 'email', 'max:120'],
            'guest_telefono' => ['nullable', 'string', 'max:40'],
            'guest_documento' => ['nullable', 'string', 'max:60'],
        ]);

        $reservation = $this->booking->createBooking([
            'room_type_id' => $data['room_type_id'],
            'check_in' => $data['check_in'],
            'check_out' => $data['check_out'],
            'guests_count' => $data['guests_count'] ?? 1,
            'notes' => $data['notes'] ?? null,
            'guest' => [
                'nombre' => $data['guest_nombre'],
                'email' => $data['guest_email'],
                'telefono' => $data['guest_telefono'] ?? null,
                'documento' => $data['guest_documento'] ?? null,
            ],
        ]);

        $message = config('hotel.booking.confirmation_note');
        if ($this->payments->paymentsEnabled() && $reservation->payment_status === 'pending') {
            $message = 'Reserva registrada. Complete el anticipo para confirmar su estadía. Folio '.$reservation->folio;
        } else {
            $message .= ' Folio '.$reservation->folio;
        }

        return redirect()
            ->route('booking.show', ['property' => $property->code, 'folio' => $reservation->folio, 'email' => $data['guest_email']])
            ->with('success', $message);
    }

    public function demoPay(Request $request, Property $property): RedirectResponse
    {
        $this->ensureEnabled($property);

        if (! $this->payments->paymentsEnabled() || config('hotel.booking.payments.provider') !== 'demo') {
            abort(404);
        }

        if (app()->environment('production')) {
            abort(403, 'Confirmación demo no disponible en producción.');
        }

        $data = $request->validate([
            'folio' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email'],
        ]);

        $reservation = $this->booking->lookup($data['folio'], $data['email']);
        if (! $reservation) {
            return back()->with('error', 'Reserva no encontrada.');
        }

        if ($reservation->payment_status !== 'pending') {
            return back()->with('error', 'La reserva no tiene un anticipo pendiente.');
        }

        $this->payments->markPaid($reservation, 'DEMO-'.now()->format('YmdHis'));

        return back()->with('success', 'Anticipo registrado. Su reserva quedó confirmada.');
    }

    private function ensureEnabled(Property $property): void
    {
        if (! config('hotel.booking.enabled', true) || ! $property->booking_enabled) {
            abort(503, 'Las reservas en línea no están disponibles.');
        }

        if (! CurrentProperty::id()) {
            CurrentProperty::set($property);
        }
    }
}
