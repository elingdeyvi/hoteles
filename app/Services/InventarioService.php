<?php

namespace App\Services;

use App\Models\FolioCharge;
use App\Models\PosMovimiento;
use App\Models\PosProduct;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventarioService
{
    public function registrar(
        PosProduct $product,
        string $tipo,
        float $cantidad,
        string $motivo,
        ?int $userId,
        ?string $observaciones = null,
        ?int $folioChargeId = null,
        string $destino = 'bodega',
    ): PosMovimiento {
        if ($cantidad <= 0) {
            throw ValidationException::withMessages([
                'cantidad' => ['La cantidad debe ser mayor a cero.'],
            ]);
        }

        return DB::transaction(function () use ($product, $tipo, $cantidad, $motivo, $userId, $observaciones, $folioChargeId, $destino) {
            $locked = PosProduct::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();

            if (! $locked->controla_inventario && $motivo !== 'venta') {
                $locked->controla_inventario = true;
            }

            if ($motivo === 'venta' && ! $locked->controla_inventario) {
                return new PosMovimiento();
            }

            $anterior = (float) $locked->stock_actual;
            $nuevo = $tipo === PosMovimiento::ENTRADA
                ? $anterior + $cantidad
                : $anterior - $cantidad;

            if ($nuevo < -0.0001) {
                throw ValidationException::withMessages([
                    'stock' => ["No hay existencia suficiente de {$locked->name}. Disponible: ".number_format($anterior, 2, '.', '').'.'],
                ]);
            }

            $locked->stock_actual = round(max(0, $nuevo), 2);
            $this->ajustarUbicacion($locked, $tipo, $cantidad, $destino);
            $locked->save();

            return PosMovimiento::query()->create([
                'pos_product_id' => $locked->id,
                'tipo' => $tipo,
                'motivo' => $motivo,
                'cantidad' => $cantidad,
                'stock_anterior' => $anterior,
                'stock_nuevo' => $locked->stock_actual,
                'folio_charge_id' => $folioChargeId,
                'user_id' => $userId,
                'observaciones' => $observaciones,
                'fecha_movimiento' => now(),
            ]);
        });
    }

    public function descontarVenta(PosProduct $product, float $cantidad, FolioCharge $charge, int $userId): void
    {
        $product->refresh();
        if (! $product->controla_inventario) {
            return;
        }

        $this->registrar($product, PosMovimiento::SALIDA, $cantidad, 'venta', $userId, 'Consumo en folio', $charge->id);
    }

    public function devolverVenta(PosProduct $product, float $cantidad, ?int $userId, int $chargeId): void
    {
        $product->refresh();
        if (! $product->controla_inventario) {
            return;
        }

        $this->registrar($product, PosMovimiento::ENTRADA, $cantidad, 'devolucion', $userId, 'Cargo quitado del folio', $chargeId, 'exhibicion');
    }

    private function ajustarUbicacion(PosProduct $locked, string $tipo, float $cantidad, string $destino): void
    {
        $bodega = (float) $locked->bodega;
        $exhibicion = (float) $locked->exhibicion;

        if ($tipo === PosMovimiento::ENTRADA) {
            if ($destino === 'exhibicion') {
                $exhibicion += $cantidad;
            } else {
                $bodega += $cantidad;
            }
        } elseif (($bodega + $exhibicion) > 0) {
            $restante = $cantidad;
            $deExhibicion = min($exhibicion, $restante);
            $exhibicion -= $deExhibicion;
            $restante -= $deExhibicion;
            $bodega = max(0, $bodega - $restante);
        }

        $locked->bodega = round($bodega, 2);
        $locked->exhibicion = round($exhibicion, 2);
    }
}
