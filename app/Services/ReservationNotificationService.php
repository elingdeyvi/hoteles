<?php

namespace App\Services;

use App\Mail\WebReservationMail;
use App\Models\Reservation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ReservationNotificationService
{
    public function sendReceived(Reservation $reservation): void
    {
        $this->send($reservation, 'received');
    }

    public function sendConfirmed(Reservation $reservation): void
    {
        $this->send($reservation, 'confirmed');
    }

    private function send(Reservation $reservation, string $type): void
    {
        if (! config('hotel.booking.notify_guest_email', true)) {
            return;
        }

        $reservation->loadMissing(['huesped', 'roomType']);
        $email = $reservation->huesped?->email;

        if (! $email) {
            return;
        }

        try {
            Mail::to($email)->send(new WebReservationMail($reservation, $type));
        } catch (\Throwable $e) {
            Log::warning('No se pudo enviar correo de reserva web.', [
                'reservation_id' => $reservation->id,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
