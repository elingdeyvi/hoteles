<?php

namespace App\Services;

use App\Models\Folio;
use App\Models\FolioCharge;
use App\Models\PosProduct;
use App\Models\PosVenta;
use App\Models\PosVentaLinea;
use App\Models\Stay;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PosSaleService
{
    public function __construct(
        private readonly FolioService $folios,
        private readonly CajaService $caja,
        private readonly InventarioService $inventario,
    ) {}

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

        $apertura = $this->caja->exigir('consumo');

        return DB::transaction(function () use ($folio, $lines, $userId, $apertura) {
            $charges = collect();

            foreach ($lines as $line) {
                $product = PosProduct::query()
                    ->with('category.outlet')
                    ->where('is_active', true)
                    ->findOrFail($line['product_id']);

                $qty = max(1, (int) ($line['quantity'] ?? 1));
                $unitPrice = $product->importeCargo();
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
                    'apertura_caja_id' => $apertura->id,
                ]);

                $this->inventario->descontarVenta($product, $qty, $charge, $userId);
                $charges->push($charge);
            }

            $this->folios->recalculateBalance($folio);

            $this->caja->sincronizar($apertura);

            return [
                'folio' => $folio->fresh(['charges', 'payments', 'stay.room', 'stay.reservation.huesped']),
                'charges' => $charges,
            ];
        });
    }

    /**
     * Venta cobrada en el momento, sin folio de habitación.
     *
     * @param  array<int, array{product_id: int, quantity: int}>  $lines
     * @return array{venta: PosVenta, cambio: float}
     */
    public function venderPublico(array $lines, string $metodo, ?float $recibido, int $userId): array
    {
        if (empty($lines)) {
            throw ValidationException::withMessages([
                'lines' => ['Agregue al menos un producto.'],
            ]);
        }

        $apertura = $this->caja->exigir('cobro');

        return DB::transaction(function () use ($lines, $metodo, $recibido, $userId, $apertura) {
            $detalle = [];
            $total = 0.0;

            foreach ($lines as $line) {
                $product = PosProduct::query()
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->findOrFail($line['product_id']);
                $qty = max(1, (int) ($line['quantity'] ?? 1));
                $precio = $product->importeCargo();
                $importe = round($precio * $qty, 2);
                $total += $importe;
                $detalle[] = compact('product', 'qty', 'precio', 'importe');
            }

            $total = round($total, 2);
            $cambio = 0.0;
            $recibidoRedondo = round((float) $recibido, 2);

            if ($metodo === 'efectivo') {
                if ($recibidoRedondo + 0.001 < $total) {
                    throw ValidationException::withMessages([
                        'recibido' => ['El efectivo recibido no cubre el total de '.$total.'.'],
                    ]);
                }
                $cambio = round($recibidoRedondo - $total, 2);
            }

            $venta = PosVenta::query()->create([
                'apertura_caja_id' => $apertura->id,
                'user_id' => $userId,
                'numero' => 'VP-'.now()->format('ymdHis').'-'.strtoupper(Str::random(3)),
                'payment_method' => $metodo,
                'total' => $total,
                'recibido' => $metodo === 'efectivo' ? $recibidoRedondo : $total,
                'cambio' => $cambio,
            ]);

            foreach ($detalle as $item) {
                PosVentaLinea::query()->create([
                    'pos_venta_id' => $venta->id,
                    'pos_product_id' => $item['product']->id,
                    'nombre' => $item['product']->name,
                    'cantidad' => $item['qty'],
                    'precio' => $item['precio'],
                    'costo' => (float) $item['product']->costo,
                    'importe' => $item['importe'],
                ]);
                $this->inventario->registrar(
                    $item['product'],
                    \App\Models\PosMovimiento::SALIDA,
                    $item['qty'],
                    'venta',
                    $userId,
                    'Venta al público '.$venta->numero,
                );
            }

            $this->caja->sincronizar($apertura);

            return [
                'venta' => $venta->load('lineas'),
                'cambio' => $cambio,
            ];
        });
    }
}
