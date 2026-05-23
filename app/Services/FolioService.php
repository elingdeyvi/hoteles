<?php

namespace App\Services;

use App\Models\Folio;
use App\Models\FolioCharge;
use App\Models\FolioPayment;
use App\Models\Stay;
use Illuminate\Support\Str;

class FolioService
{
    public function createForStay(Stay $stay, float $roomCharge): Folio
    {
        $folio = Folio::create([
            'stay_id' => $stay->id,
            'folio_number' => $this->generateNumber(),
            'balance' => $roomCharge,
            'status' => 'abierto',
        ]);

        FolioCharge::create([
            'folio_id' => $folio->id,
            'concept' => 'Estancia habitación',
            'charge_type' => 'habitacion',
            'amount' => $roomCharge,
            'quantity' => 1,
        ]);

        return $folio->load(['charges', 'payments']);
    }

    public function addCharge(Folio $folio, string $concept, float $amount, string $type = 'extra'): FolioCharge
    {
        $charge = FolioCharge::create([
            'folio_id' => $folio->id,
            'concept' => $concept,
            'charge_type' => $type,
            'amount' => $amount,
            'quantity' => 1,
        ]);

        $this->recalculateBalance($folio);

        return $charge;
    }

    public function registerPayment(Folio $folio, string $method, float $amount, ?int $userId, ?string $reference = null): FolioPayment
    {
        $payment = FolioPayment::create([
            'folio_id' => $folio->id,
            'payment_method' => $method,
            'amount' => $amount,
            'reference' => $reference,
            'received_by' => $userId,
        ]);

        $this->recalculateBalance($folio);

        return $payment;
    }

    public function recalculateBalance(Folio $folio): void
    {
        $folio->load(['charges', 'payments']);

        $charges = $folio->charges->sum(fn (FolioCharge $c) => (float) $c->amount * (int) $c->quantity);
        $payments = $folio->payments->sum(fn (FolioPayment $p) => (float) $p->amount);

        $folio->update(['balance' => round($charges - $payments, 2)]);
    }

    public function close(Folio $folio): Folio
    {
        $this->recalculateBalance($folio);

        $folio->update([
            'status' => 'cerrado',
            'closed_at' => now(),
        ]);

        return $folio->fresh(['charges', 'payments']);
    }

    private function generateNumber(): string
    {
        $prefix = config('hotel.folio_stay_prefix', 'FOL');

        return $prefix.'-'.now()->format('ymdHis').'-'.strtoupper(Str::random(3));
    }
}
