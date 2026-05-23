<?php

namespace App\Services;

use App\Models\ConfiguracionEmpresa;
use App\Models\Folio;

class FolioInvoiceService
{
    public function buildViewData(Folio $folio): array
    {
        $folio->load([
            'charges',
            'payments.receiver',
            'stay.room.roomType',
            'stay.reservation.huesped',
        ]);

        $config = ConfiguracionEmpresa::activa()->first();
        $reservation = $folio->stay?->reservation;
        $huesped = $reservation?->huesped;

        $subtotal = $folio->charges->sum(fn ($c) => (float) $c->amount * (int) $c->quantity);
        $paid = $folio->payments->sum(fn ($p) => (float) $p->amount);

        $paymentLabels = config('hotel.payment_methods', []);

        return [
            'folio' => $folio,
            'config' => $config,
            'huesped' => $huesped,
            'reservation' => $reservation,
            'room' => $folio->stay?->room,
            'roomType' => $folio->stay?->room?->roomType,
            'subtotal' => round($subtotal, 2),
            'paid' => round($paid, 2),
            'balance' => round((float) $folio->balance, 2),
            'payment_labels' => $paymentLabels,
            'issued_at' => $folio->closed_at ?? now(),
        ];
    }
}
