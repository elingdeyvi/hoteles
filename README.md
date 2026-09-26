# Hotel v2

Sistema hotelero con la misma base que POSNegocio-v2: Laravel 12, Inertia 2, Vue 3, AdminLTE 4 y Spatie Permission.

## Arranque

```bash
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
npm install
npm run dev
```

Node 20.19 o superior. Base MySQL `hotel_v2`.

## Usuarios de demostración

Contraseña de todos: `12345678`

| Correo | Rol |
| --- | --- |
| admin@gmail.com | Administrador |
| recepcionista@gmail.com | Recepcionista |
| housekeeping@gmail.com | Housekeeping |
| cajero@gmail.com | Cajero |

Hoteles sembrados: Costa Azul (`costa-azul`) y Sierra Verde (`sierra-verde`). Reserva pública: `/reservar/costa-azul`.
