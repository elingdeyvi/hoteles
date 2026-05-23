<?php

namespace App\Services;

use App\Models\Reservation;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class BookingPaymentService
{
    public function __construct(
        private readonly ReservationNotificationService $notifications
    ) {}

    public function paymentsEnabled(): bool
    {
        return (bool) config('hotel.booking.payments.enabled', false);
    }

    public function calculateDeposit(float $estimatedTotal): float
    {
        $percent = max(1, min(100, (int) config('hotel.booking.payments.deposit_percent', 30)));

        return max(1.0, round($estimatedTotal * $percent / 100, 2));
    }

    public function applyPendingPayment(Reservation $reservation): Reservation
    {
        if (! $this->paymentsEnabled()) {
            return $reservation;
        }

        $deposit = $this->calculateDeposit((float) $reservation->estimated_total);

        $reservation->update([
            'payment_status' => 'pending',
            'deposit_amount' => $deposit,
        ]);

        return $reservation->fresh();
    }

    public function createCheckout(Reservation $reservation, string $guestEmail): array
    {
        if ($reservation->payment_status !== 'pending' || ! $reservation->deposit_amount) {
            throw ValidationException::withMessages([
                'payment' => ['Esta reserva no requiere pago en línea o ya fue pagada.'],
            ]);
        }

        if (strtolower($reservation->huesped?->email ?? '') !== strtolower(trim($guestEmail))) {
            throw ValidationException::withMessages([
                'email' => ['El correo no coincide con la reserva.'],
            ]);
        }

        $provider = config('hotel.booking.payments.provider', 'demo');

        return match ($provider) {
            'stripe' => $this->createStripeCheckout($reservation),
            default => $this->createDemoCheckout($reservation),
        };
    }

    public function markPaid(Reservation $reservation, string $reference): Reservation
    {
        if ($reservation->payment_status === 'paid') {
            return $reservation;
        }

        $reservation->update([
            'payment_status' => 'paid',
            'payment_reference' => $reference,
            'paid_at' => now(),
            'status' => $reservation->status === 'pendiente' ? 'confirmada' : $reservation->status,
        ]);

        $reservation = $reservation->fresh(['huesped', 'roomType', 'room']);

        if ($reservation->status === 'confirmada') {
            $this->notifications->sendConfirmed($reservation);
        }

        return $reservation;
    }

    public function handleStripeWebhook(string $payload, ?string $signature): void
    {
        $secret = config('hotel.stripe.webhook_secret');
        if (! $secret) {
            abort(503, 'Webhook Stripe no configurado.');
        }

        if (! $this->verifyStripeSignature($payload, $signature, $secret)) {
            abort(400, 'Firma Stripe inválida.');
        }

        $event = json_decode($payload, true);
        if (! is_array($event) || ($event['type'] ?? '') !== 'checkout.session.completed') {
            return;
        }

        $session = $event['data']['object'] ?? [];
        $reservationId = (int) ($session['metadata']['reservation_id'] ?? 0);
        if (! $reservationId) {
            return;
        }

        $reservation = Reservation::query()->find($reservationId);
        if ($reservation) {
            $this->markPaid($reservation, (string) ($session['id'] ?? 'stripe'));
        }
    }

    /** Genera firma Stripe-Signature para pruebas (CLI/tests). */
    public function signStripeWebhookPayload(string $payload, string $secret, ?int $timestamp = null): string
    {
        $timestamp ??= time();
        $signed = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        return "t={$timestamp},v1={$signed}";
    }

    private function createDemoCheckout(Reservation $reservation): array
    {
        return [
            'provider' => 'demo',
            'amount' => (float) $reservation->deposit_amount,
            'currency' => config('hotel.booking.payments.currency', 'mxn'),
            'checkout_url' => null,
            'demo' => true,
            'folio' => $reservation->folio,
        ];
    }

    private function createStripeCheckout(Reservation $reservation): array
    {
        $secret = config('hotel.stripe.secret');
        if (! $secret) {
            throw ValidationException::withMessages([
                'payment' => ['Pagos Stripe no están configurados en el servidor.'],
            ]);
        }

        $currency = strtolower(config('hotel.booking.payments.currency', 'mxn'));
        $amount = (int) round((float) $reservation->deposit_amount * 100);
        $appUrl = rtrim(config('app.url'), '/');
        $reservation->loadMissing('property');
        $propertyCode = $reservation->property?->code ?? 'costa-azul';
        $returnBase = $appUrl.'/reservar/'.$propertyCode;

        $response = Http::withBasicAuth($secret, '')
            ->asForm()
            ->post('https://api.stripe.com/v1/checkout/sessions', [
                'mode' => 'payment',
                'success_url' => $returnBase.'?payment=success&folio='.urlencode($reservation->folio),
                'cancel_url' => $returnBase.'?payment=cancel&folio='.urlencode($reservation->folio),
                'client_reference_id' => $reservation->folio,
                'customer_email' => $reservation->huesped?->email,
                'line_items[0][price_data][currency]' => $currency,
                'line_items[0][price_data][product_data][name]' => 'Anticipo reserva '.$reservation->folio,
                'line_items[0][price_data][unit_amount]' => $amount,
                'line_items[0][quantity]' => 1,
                'metadata[reservation_id]' => (string) $reservation->id,
                'metadata[folio]' => $reservation->folio,
            ]);

        if (! $response->successful()) {
            throw ValidationException::withMessages([
                'payment' => ['No se pudo iniciar el pago con Stripe.'],
            ]);
        }

        $session = $response->json();
        $reservation->update(['payment_reference' => $session['id'] ?? null]);

        return [
            'provider' => 'stripe',
            'amount' => (float) $reservation->deposit_amount,
            'currency' => $currency,
            'checkout_url' => $session['url'] ?? null,
            'session_id' => $session['id'] ?? null,
            'demo' => false,
            'folio' => $reservation->folio,
        ];
    }

    private function verifyStripeSignature(string $payload, ?string $signature, string $secret): bool
    {
        if (! $signature) {
            return false;
        }

        $parts = [];
        foreach (explode(',', $signature) as $element) {
            [$key, $value] = array_pad(explode('=', $element, 2), 2, null);
            if ($key && $value) {
                $parts[$key] = $value;
            }
        }

        $timestamp = $parts['t'] ?? null;
        $v1 = $parts['v1'] ?? null;
        if (! $timestamp || ! $v1) {
            return false;
        }

        $signed = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        return hash_equals($signed, $v1);
    }
}
