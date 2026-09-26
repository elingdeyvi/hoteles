<?php

namespace App\Support;

class ImpresoraDestino
{
    public function __construct(
        public readonly ?string $nombreSistema,
        public readonly ?string $ip,
        public readonly int $puerto = 9100,
        public readonly int $anchoPapel = 80,
        public readonly ?string $nombre = null,
    ) {}

    public function columnas(): int
    {
        return $this->anchoPapel <= 58 ? 32 : 42;
    }

    public function tieneDestino(): bool
    {
        return ($this->ip !== null && $this->ip !== '') || ($this->nombreSistema !== null && $this->nombreSistema !== '');
    }
}
