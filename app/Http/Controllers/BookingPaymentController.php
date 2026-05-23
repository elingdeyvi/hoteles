<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Services\BookingPaymentService;
use App\Services\OnlineBookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BookingPaymentController extends Controller
{
    public function __construct(
        private readonly BookingPaymentService $payments,
        private readonly OnlineBookingService $booking
    ) {}

    public function checkout(Request $request): JsonResponse
    {
        $this->ensurePaymentsEnabled();

        $data = $request->validate([
            'folio' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email'],
        ]);

        $reservation = $this->booking->lookup($data['folio'], $data['email']);
        if (! $reservation) {
            return response()->json(['message' => 'Reserva no encontrada.'], 404);
        }

        return response()->json([
            'data' => $this->payments->createCheckout($reservation, $data['email']),
        ]);
    }

    public function demoConfirm(Request $request): JsonResponse
    {
        $this->ensurePaymentsEnabled();

        if (config('hotel.booking.payments.provider') !== 'demo') {
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
            return response()->json(['message' => 'Reserva no encontrada.'], 404);
        }

        if ($reservation->payment_status !== 'pending') {
            throw ValidationException::withMessages([
                'payment' => ['La reserva no tiene un anticipo pendiente.'],
            ]);
        }

        $reservation = $this->payments->markPaid($reservation, 'DEMO-'.now()->format('YmdHis'));

        return response()->json([
            'data' => $this->reservationPayload($reservation),
            'message' => 'Anticipo registrado. Su reserva quedó confirmada.',
        ]);
    }

    public function stripeWebhook(Request $request): JsonResponse
    {
        $this->payments->handleStripeWebhook(
            $request->getContent(),
            $request->header('Stripe-Signature')
        );

        return response()->json(['received' => true]);
    }

    private function ensurePaymentsEnabled(): void
    {
        if (! config('hotel.booking.enabled', true)) {
            abort(503, 'Las reservas en línea no están disponibles.');
        }

        if (! $this->payments->paymentsEnabled()) {
            abort(404, 'Pagos en línea no habilitados.');
        }
    }

    private function reservationPayload(Reservation $reservation): array
    {
        return [
            'folio' => $reservation->folio,
            'status' => $reservation->status,
            'payment_status' => $reservation->payment_status,
            'deposit_amount' => $reservation->deposit_amount,
            'paid_at' => $reservation->paid_at?->toIso8601String(),
        ];
    }
}
