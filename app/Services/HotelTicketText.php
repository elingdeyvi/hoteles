<?php

namespace App\Services;

use App\Models\ConfiguracionEmpresa;
use App\Models\CorteCaja;
use App\Models\Folio;
use App\Models\PosVenta;
use App\Models\Reservation;

class HotelTicketText
{
    public function reserva(Reservation $reservation, ?ConfiguracionEmpresa $empresa, int $columnas = 42): string
    {
        $reservation->loadMissing(['huesped', 'roomType', 'room']);
        $nombre = $empresa?->nombre_corto ?: $empresa?->nombre_empresa ?: 'Hotel';

        $lineas = [
            $this->centrar($nombre, $columnas),
            $this->centrar('RESERVACION', $columnas),
            str_repeat('-', $columnas),
            'Folio: '.$reservation->folio,
            'Huesped: '.$this->cortar((string) $reservation->huesped?->nombre, $columnas - 9),
            'Entrada: '.$this->fecha($reservation->check_in),
            'Salida:  '.$this->fecha($reservation->check_out),
            'Tipo: '.$this->cortar((string) $reservation->roomType?->name, $columnas - 6),
            'Hab: '.($reservation->room?->number ?: 'Por asignar'),
            'Estado: '.($reservation->status ?: ''),
            str_repeat('-', $columnas),
            'Total: '.$this->dinero($reservation->estimated_total),
            '',
            $this->centrar('Gracias por su preferencia', $columnas),
        ];

        if ($empresa?->telefono) {
            $lineas[] = $this->centrar('Tel '.$empresa->telefono, $columnas);
        }

        return implode("\n", $lineas);
    }

    public function folio(Folio $folio, ?ConfiguracionEmpresa $empresa, int $columnas = 42): string
    {
        $folio->loadMissing(['stay.reservation.huesped', 'stay.room', 'charges', 'payments']);
        $nombre = $empresa?->nombre_corto ?: $empresa?->nombre_empresa ?: 'Hotel';
        $lineas = [
            $this->centrar($nombre, $columnas),
            $this->centrar('CUENTA', $columnas),
            str_repeat('-', $columnas),
            'Folio: '.$folio->folio_number,
            'Huesped: '.$this->cortar((string) $folio->stay?->reservation?->huesped?->nombre, $columnas - 9),
            'Hab: '.($folio->stay?->room?->number ?: '—'),
            'Reserva: '.($folio->stay?->reservation?->folio ?: '—'),
            str_repeat('-', $columnas),
            'CARGOS',
        ];

        foreach ($folio->charges as $cargo) {
            $importe = $this->dinero((float) $cargo->amount * (int) ($cargo->quantity ?: 1));
            $lineas[] = $this->fila((string) $cargo->concept, $importe, $columnas);
        }

        $lineas[] = str_repeat('-', $columnas);
        $lineas[] = 'PAGOS';
        if ($folio->payments->isEmpty()) {
            $lineas[] = 'Sin pagos';
        }
        foreach ($folio->payments as $pago) {
            $lineas[] = $this->fila((string) $pago->payment_method, $this->dinero($pago->amount), $columnas);
        }

        $lineas[] = str_repeat('=', $columnas);
        $lineas[] = $this->fila('SALDO', $this->dinero($folio->balance), $columnas);
        $cambio = (float) $folio->payments->sum('cambio');
        if ($cambio > 0) {
            $lineas[] = $this->fila('CAMBIO', $this->dinero($cambio), $columnas);
        }
        $lineas[] = '';
        $lineas[] = $this->centrar('Gracias por su preferencia', $columnas);

        return implode("\n", $lineas);
    }

    public function ventaPublica(PosVenta $venta, ?ConfiguracionEmpresa $empresa, int $columnas = 42): string
    {
        $venta->loadMissing('lineas');
        $nombre = $empresa?->nombre_corto ?: $empresa?->nombre_empresa ?: 'Hotel';
        $lineas = [
            $this->centrar($nombre, $columnas),
            $this->centrar('VENTA AL PUBLICO', $columnas),
            str_repeat('-', $columnas),
            'Ticket: '.$venta->numero,
            'Pago: '.$venta->payment_method,
            str_repeat('-', $columnas),
        ];

        foreach ($venta->lineas as $linea) {
            $lineas[] = $this->fila(
                $linea->cantidad.' '.$linea->nombre,
                $this->dinero($linea->importe),
                $columnas
            );
        }

        $lineas[] = str_repeat('=', $columnas);
        $lineas[] = $this->fila('TOTAL', $this->dinero($venta->total), $columnas);
        if ((float) $venta->cambio > 0) {
            $lineas[] = $this->fila('RECIBIDO', $this->dinero($venta->recibido), $columnas);
            $lineas[] = $this->fila('CAMBIO', $this->dinero($venta->cambio), $columnas);
        }
        $lineas[] = '';
        $lineas[] = $this->centrar('Gracias por su preferencia', $columnas);

        return implode("\n", $lineas);
    }

    public function corte(CorteCaja $corte, ?ConfiguracionEmpresa $empresa, int $columnas = 42): string
    {
        $nombre = $empresa?->nombre_corto ?: $empresa?->nombre_empresa ?: 'Hotel';
        $lineas = [
            $this->centrar($nombre, $columnas),
            $this->centrar('CORTE '.$corte->tipo, $columnas),
            str_repeat('-', $columnas),
            'Fecha: '.($corte->fecha_corte?->format('Y-m-d H:i') ?: ''),
            'Usuario: '.$this->cortar((string) $corte->usuario?->name, $columnas - 9),
            str_repeat('-', $columnas),
            $this->fila('Fondo', $this->dinero($corte->fondo_inicial), $columnas),
            $this->fila('Efectivo', $this->dinero($corte->total_efectivo), $columnas),
            $this->fila('Tarjeta', $this->dinero($corte->total_tarjeta), $columnas),
            $this->fila('Transfer.', $this->dinero($corte->total_transferencia), $columnas),
            $this->fila('(+) Ingresos', $this->dinero($corte->total_ingresos), $columnas),
            $this->fila('(-) Egresos', $this->dinero($corte->total_egresos), $columnas),
            str_repeat('=', $columnas),
            $this->fila('ESPERADO', $this->dinero($corte->total_esperado), $columnas),
        ];

        if ($corte->total_real !== null) {
            $lineas[] = $this->fila('CONTADO', $this->dinero($corte->total_real), $columnas);
            $lineas[] = $this->fila('DIFERENCIA', $this->dinero($corte->diferencia), $columnas);
        }

        return implode("\n", $lineas);
    }

    public function prueba(?ConfiguracionEmpresa $empresa, string $impresora, int $columnas = 42): string
    {
        $nombre = $empresa?->nombre_corto ?: $empresa?->nombre_empresa ?: 'Hotel';

        return implode("\n", [
            $this->centrar($nombre, $columnas),
            $this->centrar('PRUEBA DE IMPRESION', $columnas),
            str_repeat('-', $columnas),
            'Impresora: '.$this->cortar($impresora, $columnas - 12),
            'Fecha: '.now()->format('Y-m-d H:i'),
            str_repeat('-', $columnas),
            $this->centrar('Ticket de prueba', $columnas),
        ]);
    }

    private function dinero(mixed $monto): string
    {
        return '$'.number_format((float) $monto, 2);
    }

    private function fecha(mixed $fecha): string
    {
        if ($fecha instanceof \DateTimeInterface) {
            return $fecha->format('Y-m-d');
        }

        return substr((string) $fecha, 0, 10);
    }

    private function centrar(string $texto, int $columnas): string
    {
        $texto = $this->cortar($texto, $columnas);
        $espacio = max(0, intdiv($columnas - mb_strlen($texto), 2));

        return str_repeat(' ', $espacio).$texto;
    }

    private function fila(string $izq, string $der, int $columnas): string
    {
        $der = $this->cortar($der, 12);
        $izq = $this->cortar($izq, max(1, $columnas - mb_strlen($der) - 1));
        $relleno = max(1, $columnas - mb_strlen($izq) - mb_strlen($der));

        return $izq.str_repeat(' ', $relleno).$der;
    }

    private function cortar(string $texto, int $max): string
    {
        $texto = trim($texto);
        if ($max < 1) {
            return '';
        }
        if (mb_strlen($texto) <= $max) {
            return $texto;
        }

        return mb_substr($texto, 0, max(0, $max - 3)).'...';
    }
}
