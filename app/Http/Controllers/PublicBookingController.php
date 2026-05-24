<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracionEmpresa;
use App\Support\BrandAssets;
use App\Services\BookingPaymentService;
use App\Services\OnlineBookingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicBookingController extends Controller
{
    public function __construct(
        private readonly OnlineBookingService $booking,
        private readonly BookingPaymentService $payments
    ) {}

    public function config(): JsonResponse
    {
        if (! config('hotel.booking.enabled', true)) {
            return response()->json(['message' => 'Las reservas en línea no están disponibles.'], 503);
        }

        $empresa = ConfiguracionEmpresa::obtenerConfiguracion();

        return response()->json([
            'data' => [
                'hotel' => [
                    'nombre' => $empresa?->nombre_empresa ?? 'Hotel',
                    'telefono' => $empresa?->telefono,
                    'email' => $empresa?->email,
                    'direccion' => $empresa?->direccion,
                    'logo_url' => $empresa?->logo_url ?? BrandAssets::logoUrl($empresa?->property?->code),
                    'color_primario' => $empresa?->color_primario ?? BrandAssets::primaryColor($empresa?->property?->code),
                    'color_secundario' => $empresa?->color_secundario ?? '#64748b',
                ],
                'booking' => [
                    'min_advance_days' => (int) config('hotel.booking.min_advance_days', 0),
                    'max_guests' => (int) config('hotel.booking.max_guests', 8),
                    'confirmation_note' => config('hotel.booking.confirmation_note'),
                    'payments' => [
                        'enabled' => $this->payments->paymentsEnabled(),
                        'deposit_percent' => (int) config('hotel.booking.payments.deposit_percent', 30),
                        'currency' => config('hotel.booking.payments.currency', 'mxn'),
                        'provider' => config('hotel.booking.payments.provider', 'demo'),
                    ],
                ],
            ],
        ]);
    }

    public function roomTypes(): JsonResponse
    {
        $this->ensureEnabled();

        return response()->json(['data' => $this->booking->activeRoomTypes()]);
    }

    public function availability(Request $request): JsonResponse
    {
        $this->ensureEnabled();

        $data = $request->validate([
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'room_type_id' => ['nullable', 'integer', 'exists:room_types,id'],
        ]);

        $checkIn = Carbon::parse($data['check_in']);
        $checkOut = Carbon::parse($data['check_out']);

        return response()->json([
            'data' => $this->booking->availability($checkIn, $checkOut, $data['room_type_id'] ?? null),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureEnabled();

        $data = $request->validate([
            'room_type_id' => ['required', 'exists:room_types,id'],
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'guests_count' => ['nullable', 'integer', 'min:1', 'max:'.(int) config('hotel.booking.max_guests', 8)],
            'notes' => ['nullable', 'string', 'max:500'],
            'guest.nombre' => ['required', 'string', 'max:120'],
            'guest.email' => ['required', 'email', 'max:120'],
            'guest.telefono' => ['nullable', 'string', 'max:40'],
            'guest.documento' => ['nullable', 'string', 'max:60'],
        ]);

        $reservation = $this->booking->createBooking($data);

        $payload = [
            'data' => $reservation->load(['huesped', 'roomType']),
            'message' => config('hotel.booking.confirmation_note'),
        ];

        if ($this->payments->paymentsEnabled() && $reservation->payment_status === 'pending') {
            $payload['payment'] = [
                'required' => true,
                'amount' => (float) $reservation->deposit_amount,
                'currency' => config('hotel.booking.payments.currency', 'mxn'),
                'status' => $reservation->payment_status,
            ];
            $payload['message'] = 'Reserva registrada. Complete el anticipo en línea para confirmar su estadía.';
        }

        return response()->json($payload, 201);
    }

    public function lookup(Request $request): JsonResponse
    {
        $this->ensureEnabled();

        $data = $request->validate([
            'folio' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email'],
        ]);

        $reservation = $this->booking->lookup($data['folio'], $data['email']);

        if (! $reservation) {
            return response()->json(['message' => 'No se encontró una reserva con esos datos.'], 404);
        }

        return response()->json([
            'data' => [
                'folio' => $reservation->folio,
                'online_reference' => $reservation->online_reference,
                'status' => $reservation->status,
                'check_in' => $reservation->check_in?->format('Y-m-d'),
                'check_out' => $reservation->check_out?->format('Y-m-d'),
                'estimated_total' => $reservation->estimated_total,
                'payment_status' => $reservation->payment_status,
                'deposit_amount' => $reservation->deposit_amount,
                'paid_at' => $reservation->paid_at?->format('Y-m-d H:i'),
                'room_type' => $reservation->roomType?->only(['id', 'name']),
                'guest_name' => $reservation->huesped?->nombre,
            ],
        ]);
    }

    private function ensureEnabled(): void
    {
        if (! config('hotel.booking.enabled', true)) {
            abort(503, 'Las reservas en línea no están disponibles.');
        }

        $property = \App\Support\CurrentProperty::get();
        if ($property && ! $property->booking_enabled) {
            abort(503, 'Las reservas en línea no están disponibles para este hotel.');
        }
    }
}
