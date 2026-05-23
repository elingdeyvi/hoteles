<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reserva</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1a2f42; line-height: 1.5;">
    <h2 style="color: #1e5f8a;">{{ $hotelName }}</h2>

    @if($type === 'received')
        <p>Hola {{ $reservation->huesped?->nombre }},</p>
        <p>Hemos recibido su solicitud de reserva en línea. Nuestro equipo la revisará y le confirmará a la brevedad.</p>
    @else
        <p>Hola {{ $reservation->huesped?->nombre }},</p>
        <p>¡Buenas noticias! Su reserva ha sido <strong>confirmada</strong>.</p>
    @endif

    <table cellpadding="6" cellspacing="0" border="0" style="border-collapse: collapse; margin: 16px 0;">
        <tr><td><strong>Folio</strong></td><td>{{ $reservation->folio }}</td></tr>
        @if($reservation->online_reference)
        <tr><td><strong>Referencia web</strong></td><td>{{ $reservation->online_reference }}</td></tr>
        @endif
        <tr><td><strong>Habitación</strong></td><td>{{ $reservation->roomType?->name }}</td></tr>
        <tr><td><strong>Entrada</strong></td><td>{{ $reservation->check_in?->format('d/m/Y') }}</td></tr>
        <tr><td><strong>Salida</strong></td><td>{{ $reservation->check_out?->format('d/m/Y') }}</td></tr>
        <tr><td><strong>Huéspedes</strong></td><td>{{ $reservation->guests_count }}</td></tr>
        <tr><td><strong>Total estimado</strong></td><td>${{ number_format((float) $reservation->estimated_total, 2) }}</td></tr>
        <tr><td><strong>Estado</strong></td><td>{{ $reservation->status }}</td></tr>
    </table>

    @if($hotelPhone || $hotelEmail)
        <p style="color: #64748b; font-size: 14px;">
            Contacto:
            @if($hotelPhone) {{ $hotelPhone }} @endif
            @if($hotelEmail) — {{ $hotelEmail }} @endif
        </p>
    @endif

    <p style="color: #64748b; font-size: 12px;">Mensaje automático del sistema de reservas.</p>
</body>
</html>
