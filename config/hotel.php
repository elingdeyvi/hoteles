<?php

return [
    'health' => [
        'version' => env('HOTEL_APP_VERSION', '1.0.0'),
    ],

    'folio_prefix' => env('HOTEL_FOLIO_PREFIX', 'RES'),
    'folio_stay_prefix' => env('HOTEL_FOLIO_STAY_PREFIX', 'FOL'),

    'room_statuses' => [
        'disponible' => 'Disponible',
        'ocupada' => 'Ocupada',
        'sucia' => 'Sucia',
        'limpia' => 'Limpia',
        'mantenimiento' => 'En mantenimiento',
    ],

    'reservation_statuses' => [
        'pendiente' => 'Pendiente',
        'confirmada' => 'Confirmada',
        'check_in' => 'Check-in',
        'check_out' => 'Check-out',
        'cancelada' => 'Cancelada',
    ],

    'payment_methods' => [
        'efectivo' => 'Efectivo',
        'tarjeta' => 'Tarjeta',
        'transferencia' => 'Transferencia',
    ],

    'charge_types' => [
        'habitacion' => 'Habitación',
        'pos' => 'Consumo POS',
        'extra' => 'Cargo extra',
    ],

    'reservation_sources' => [
        'recepcion' => 'Recepción',
        'web' => 'Reserva en línea',
    ],

    'booking' => [
        'enabled' => env('HOTEL_BOOKING_ENABLED', true),
        'min_advance_days' => (int) env('HOTEL_BOOKING_MIN_ADVANCE_DAYS', 0),
        'max_guests' => (int) env('HOTEL_BOOKING_MAX_GUESTS', 8),
        'confirmation_note' => 'Su solicitud fue registrada. El hotel confirmará la reserva por correo o teléfono.',
        'notify_guest_email' => env('HOTEL_BOOKING_NOTIFY_EMAIL', true),
        'payments' => [
            'enabled' => env('HOTEL_BOOKING_PAYMENTS_ENABLED', false),
            'provider' => env('HOTEL_BOOKING_PAYMENT_PROVIDER', 'demo'),
            'deposit_percent' => (int) env('HOTEL_BOOKING_DEPOSIT_PERCENT', 30),
            'currency' => env('HOTEL_BOOKING_CURRENCY', 'mxn'),
        ],
    ],

    'stripe' => [
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    /** Cuentas creadas por migrate/seed — deben desactivarse antes de producción. */
    'demo_user_emails' => [
        'admin@gmail.com',
        'recepcionista@gmail.com',
        'housekeeping@gmail.com',
        'cajero@gmail.com',
    ],
];
