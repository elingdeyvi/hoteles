<?php

namespace App\Http\Controllers;

use App\Services\BookingPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingPaymentController extends Controller
{
    public function __construct(private readonly BookingPaymentService $payments) {}

    public function stripeWebhook(Request $request): JsonResponse
    {
        $this->payments->handleStripeWebhook(
            $request->getContent(),
            $request->header('Stripe-Signature')
        );

        return response()->json(['received' => true]);
    }
}
