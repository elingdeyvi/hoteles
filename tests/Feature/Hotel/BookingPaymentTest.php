<?php

namespace Tests\Feature\Hotel;

use App\Mail\WebReservationMail;
use App\Models\Reservation;
use App\Models\RoomType;
use App\Services\BookingPaymentService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\HotelTestCase;

class BookingPaymentTest extends HotelTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedHotelCatalog();
        Mail::fake();

        config([
            'hotel.booking.payments.enabled' => true,
            'hotel.booking.payments.provider' => 'demo',
            'hotel.booking.payments.deposit_percent' => 30,
        ]);
    }

    public function test_web_booking_with_deposit_creates_pending_payment(): void
    {
        $roomType = RoomType::query()->where('code', 'doble')->firstOrFail();

        $response = $this->postJson('/api/booking/costa-azul/reservations', [
            'room_type_id' => $roomType->id,
            'check_in' => now()->addDays(3)->toDateString(),
            'check_out' => now()->addDays(5)->toDateString(),
            'guest' => [
                'nombre' => 'Pago Demo',
                'email' => 'pago.demo@test.com',
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.payment_status', 'pending')
            ->assertJsonPath('payment.required', true);

        $folio = $response->json('data.folio');
        $this->assertNotEmpty($response->json('data.deposit_amount'));
    }

    public function test_demo_payment_confirms_reservation(): void
    {
        $roomType = RoomType::query()->firstOrFail();

        $create = $this->postJson('/api/booking/costa-azul/reservations', [
            'room_type_id' => $roomType->id,
            'check_in' => now()->addDays(2)->toDateString(),
            'check_out' => now()->addDays(4)->toDateString(),
            'guest' => [
                'nombre' => 'Cliente Pago',
                'email' => 'cliente.pago@test.com',
            ],
        ])->assertCreated();

        $folio = $create->json('data.folio');

        $this->postJson('/api/booking/costa-azul/payments/demo-confirm', [
            'folio' => $folio,
            'email' => 'cliente.pago@test.com',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'confirmada')
            ->assertJsonPath('data.payment_status', 'paid');

        Mail::assertSent(WebReservationMail::class, fn ($mail) => $mail->type === 'confirmed');
    }

    public function test_stripe_checkout_returns_session_url(): void
    {
        Http::fake([
            'api.stripe.com/v1/checkout/sessions' => Http::response([
                'id' => 'cs_test_123',
                'url' => 'https://checkout.stripe.com/pay/cs_test_123',
            ], 200),
        ]);

        config([
            'hotel.booking.payments.provider' => 'stripe',
            'hotel.stripe.secret' => 'sk_test_fake_key',
        ]);

        $roomType = RoomType::query()
            ->where('code', 'cabana')
            ->whereHas('property', fn ($q) => $q->where('code', 'sierra-verde'))
            ->firstOrFail();

        $create = $this->postJson('/api/booking/sierra-verde/reservations', [
            'room_type_id' => $roomType->id,
            'check_in' => now()->addDays(2)->toDateString(),
            'check_out' => now()->addDays(4)->toDateString(),
            'guest' => [
                'nombre' => 'Stripe Test',
                'email' => 'stripe.test@test.com',
            ],
        ])->assertCreated();

        $folio = $create->json('data.folio');

        $this->postJson('/api/booking/sierra-verde/payments/checkout', [
            'folio' => $folio,
            'email' => 'stripe.test@test.com',
        ])
            ->assertOk()
            ->assertJsonPath('data.provider', 'stripe')
            ->assertJsonPath('data.checkout_url', 'https://checkout.stripe.com/pay/cs_test_123');

        Http::assertSent(function ($request) use ($folio) {
            return str_contains($request->url(), 'checkout/sessions')
                && str_contains($request['success_url'], '/reservar/sierra-verde')
                && str_contains($request['success_url'], urlencode($folio));
        });
    }

    public function test_stripe_webhook_marks_reservation_paid(): void
    {
        config([
            'hotel.stripe.webhook_secret' => 'whsec_test_secret',
        ]);

        $roomType = RoomType::query()
            ->where('code', 'doble')
            ->whereHas('property', fn ($q) => $q->where('code', 'costa-azul'))
            ->firstOrFail();
        $create = $this->postJson('/api/booking/costa-azul/reservations', [
            'room_type_id' => $roomType->id,
            'check_in' => now()->addDays(2)->toDateString(),
            'check_out' => now()->addDays(4)->toDateString(),
            'guest' => [
                'nombre' => 'Webhook Test',
                'email' => 'webhook.test@test.com',
            ],
        ])->assertCreated();

        $reservation = Reservation::query()->where('folio', $create->json('data.folio'))->firstOrFail();

        $payload = json_encode([
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_paid',
                    'metadata' => [
                        'reservation_id' => (string) $reservation->id,
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $signature = app(BookingPaymentService::class)
            ->signStripeWebhookPayload($payload, 'whsec_test_secret');

        $this->call('POST', '/api/booking/webhooks/stripe', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $payload)->assertOk();

        $reservation->refresh();
        $this->assertSame('paid', $reservation->payment_status);
        $this->assertSame('confirmada', $reservation->status);

        Mail::assertSent(WebReservationMail::class, fn ($mail) => $mail->type === 'confirmed');
    }
}
