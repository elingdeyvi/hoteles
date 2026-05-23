<?php

namespace App\Mail;

use App\Models\ConfiguracionEmpresa;
use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WebReservationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Reservation $reservation,
        public string $type
    ) {}

    public function build(): self
    {
        $hotel = ConfiguracionEmpresa::obtenerConfiguracion();
        $hotelName = $hotel?->nombre_empresa ?? 'Hotel';

        $subjects = [
            'received' => "Solicitud de reserva recibida — {$hotelName}",
            'confirmed' => "Reserva confirmada — {$hotelName}",
        ];

        return $this
            ->subject($subjects[$this->type] ?? "Reserva — {$hotelName}")
            ->view('mails.web_reservation', [
                'reservation' => $this->reservation,
                'type' => $this->type,
                'hotelName' => $hotelName,
                'hotelPhone' => $hotel?->telefono,
                'hotelEmail' => $hotel?->email,
            ]);
    }
}
