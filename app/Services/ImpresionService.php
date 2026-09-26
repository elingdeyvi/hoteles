<?php

namespace App\Services;

use App\Support\ImpresoraDestino;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\Printer;

class ImpresionService
{
    /**
     * @return array{success: bool, message?: string, error?: string}
     */
    public function imprimir(ImpresoraDestino $destino, string $contenido): array
    {
        if (! $destino->tieneDestino()) {
            return [
                'success' => false,
                'error' => 'La impresora no tiene nombre de Windows ni IP.',
            ];
        }

        try {
            $connector = $destino->ip !== null && $destino->ip !== ''
                ? new NetworkPrintConnector($destino->ip, $destino->puerto)
                : new WindowsPrintConnector((string) $destino->nombreSistema);

            $printer = new Printer($connector);
            $printer->initialize();
            $printer->setJustification(Printer::JUSTIFY_LEFT);

            foreach (preg_split("/\r\n|\n|\r/", $contenido) ?: [] as $linea) {
                $printer->text($linea."\n");
            }

            $printer->feed(4);
            $printer->cut();
            $printer->close();

            $nombre = $destino->nombre ?: $destino->nombreSistema ?: $destino->ip;

            return [
                'success' => true,
                'message' => "Ticket enviado a {$nombre}.",
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => 'Error al imprimir: '.$e->getMessage(),
            ];
        }
    }
}
