<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura {{ $folio->folio_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a2f42; margin: 24px; }
        .header { border-bottom: 3px solid #1e5f8a; padding-bottom: 12px; margin-bottom: 16px; }
        .header h1 { font-size: 20px; color: #1e5f8a; margin: 0 0 4px 0; }
        .header .sub { color: #64748b; font-size: 10px; }
        .meta { width: 100%; margin-bottom: 16px; }
        .meta td { vertical-align: top; padding: 4px 8px 4px 0; }
        .box { background: #f4f6f8; border: 1px solid #e8ecf1; padding: 10px; margin-bottom: 14px; }
        table.items { width: 100%; border-collapse: collapse; margin: 12px 0; }
        table.items th { background: #1e5f8a; color: #fff; padding: 8px; text-align: left; font-size: 10px; }
        table.items td { border-bottom: 1px solid #e8ecf1; padding: 7px 8px; }
        .text-right { text-align: right; }
        .totals { width: 45%; margin-left: auto; margin-top: 12px; }
        .totals td { padding: 5px 8px; }
        .totals .grand { font-size: 13px; font-weight: bold; color: #1e5f8a; border-top: 2px solid #1e5f8a; }
        .footer { margin-top: 24px; font-size: 9px; color: #64748b; border-top: 1px solid #e8ecf1; padding-top: 10px; }
        .badge { display: inline-block; padding: 2px 8px; background: #c4a35a; color: #fff; font-size: 9px; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $config?->ticket_encabezado_nombre ?? $config?->nombre_empresa ?? 'Hotel' }}</h1>
        <div class="sub">
            @if($config?->ticket_encabezado_rfc ?? $config?->rfc) RFC: {{ $config->ticket_encabezado_rfc ?? $config->rfc }} · @endif
            {{ $config?->ticket_encabezado_domicilio ?? $config?->direccion }}
            @if($config?->ticket_encabezado_cp_ciudad) · {{ $config->ticket_encabezado_cp_ciudad }} @endif
            @if($config?->ticket_encabezado_telefono ?? $config?->telefono) · Tel: {{ $config->ticket_encabezado_telefono ?? $config->telefono }} @endif
        </div>
    </div>

    <table class="meta">
        <tr>
            <td width="50%">
                <strong>Factura / Folio:</strong> {{ $folio->folio_number }}<br>
                <strong>Reserva:</strong> {{ $reservation?->folio ?? '—' }}<br>
                <span class="badge">{{ strtoupper($folio->status) }}</span>
            </td>
            <td width="50%" class="text-right">
                <strong>Fecha emisión:</strong> {{ $issued_at->format('d/m/Y H:i') }}<br>
                @if($folio->closed_at)
                    <strong>Cierre:</strong> {{ $folio->closed_at->format('d/m/Y H:i') }}<br>
                @endif
            </td>
        </tr>
    </table>

    <div class="box">
        <strong>Huésped:</strong> {{ $huesped?->nombre ?? '—' }}<br>
        @if($huesped?->documento) <strong>Documento:</strong> {{ $huesped->documento }} · @endif
        @if($huesped?->email) {{ $huesped->email }} · @endif
        @if($huesped?->telefono) {{ $huesped->telefono }} @endif
        <br>
        <strong>Habitación:</strong> {{ $room?->number ?? '—' }} ({{ $roomType?->name ?? '—' }}) ·
        <strong>Estancia:</strong>
        {{ $reservation?->check_in?->format('d/m/Y') }} — {{ $reservation?->check_out?->format('d/m/Y') }}
    </div>

    <table class="items">
        <thead>
            <tr>
                <th>Concepto</th>
                <th class="text-right" width="80">Cant.</th>
                <th class="text-right" width="100">Importe</th>
            </tr>
        </thead>
        <tbody>
            @foreach($folio->charges as $charge)
            <tr>
                <td>{{ $charge->concept }}</td>
                <td class="text-right">{{ $charge->quantity }}</td>
                <td class="text-right">$ {{ number_format((float) $charge->amount * (int) $charge->quantity, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Subtotal cargos</td><td class="text-right">$ {{ number_format($subtotal, 2) }}</td></tr>
        <tr><td>Total pagado</td><td class="text-right">$ {{ number_format($paid, 2) }}</td></tr>
        <tr class="grand"><td>Saldo</td><td class="text-right">$ {{ number_format($balance, 2) }}</td></tr>
    </table>

    @if($folio->payments->isNotEmpty())
    <h3 style="font-size: 12px; color: #1e5f8a; margin-top: 20px;">Pagos registrados</h3>
    <table class="items">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Método</th>
                <th>Referencia</th>
                <th class="text-right">Monto</th>
            </tr>
        </thead>
        <tbody>
            @foreach($folio->payments as $payment)
            <tr>
                <td>{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                <td>{{ $payment_labels[$payment->payment_method] ?? $payment->payment_method }}</td>
                <td>{{ $payment->reference ?? '—' }}</td>
                <td class="text-right">$ {{ number_format((float) $payment->amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="footer">
        @if($config?->ticket_footer_text)
            {!! nl2br(e($config->ticket_footer_text)) !!}
        @else
            Documento generado por el sistema de gestión hotelera. Gracias por su preferencia.
        @endif
    </div>
</body>
</html>
