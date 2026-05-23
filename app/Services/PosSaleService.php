<?php

namespace App\Services;

use App\Models\Folio;
use App\Models\FolioCharge;
use App\Models\PosProduct;
use App\Models\Stay;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PosSaleService
{
    public function __construct(private readonly FolioService $folios) {}

    /**
     * Habitaciones con estancia activa y folio abierto (cargos a cuenta).
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public function roomsInHouse(): \Illuminate\Support\Collection
    {
        return Stay::query()
            ->where('status', 'activa')
            ->with([
                'room.roomType',
                'folio',
                'reservation.huesped',
            ])
            ->get()
            ->filter(fn (Stay $stay) => $stay->folio && $stay->folio->status === 'abierto')
            ->map(fn (Stay $stay) => [
                'stay_id' => $stay->id,
                'folio_id' => $stay->folio->id,
                'folio_number' => $stay->folio->folio_number,
                'room_number' => $stay->room?->number,
                'room_type' => $stay->room?->roomType?->name,
                'huesped' => $stay->reservation?->huesped?->nombre,
                'balance' => (float) $stay->folio->balance,
            ])
            ->values();
    }

    /**
     * @param  array<int, array{product_id: int, quantity: int}>  $lines
     * @return array{folio: Folio, charges: \Illuminate\Support\Collection<int, FolioCharge>}
     */
    public function chargeToFolio(Folio $folio, array $lines, int $userId): array
    {
        if ($folio->status !== 'abierto') {
            throw ValidationException::withMessages([
                'folio' => ['El folio está cerrado. No se pueden agregar consumos.'],
            ]);
        }

        if (empty($lines)) {
            throw ValidationException::withMessages([
                'lines' => ['Agregue al menos un producto.'],
            ]);
        }

        return DB::transaction(function () use ($folio, $lines, $userId) {
            $charges = collect();

            foreach ($lines as $line) {
                $product = PosProduct::query()
                    ->with('category.outlet')
                    ->where('is_active', true)
                    ->findOrFail($line['product_id']);

                $qty = max(1, (int) ($line['quantity'] ?? 1));
                $unitPrice = (float) $product->price;
                $outletName = $product->category?->outlet?->name ?? 'POS';
                $concept = "[{$outletName}] {$product->name}";

                $charge = FolioCharge::create([
                    'folio_id' => $folio->id,
                    'concept' => $concept,
                    'charge_type' => 'pos',
                    'amount' => $unitPrice,
                    'quantity' => $qty,
                    'pos_product_id' => $product->id,
                    'charged_by' => $userId,
                ]);

                $charges->push($charge);
            }

            $this->folios->recalculateBalance($folio);

            return [
                'folio' => $folio->fresh(['charges', 'payments', 'stay.room', 'stay.reservation.huesped']),
                'charges' => $charges,
            ];
        });
    }
}
