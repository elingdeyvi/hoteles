# Instalación — Sistema de gestión hotelera

## Requisitos

- PHP 8.1+
- Composer
- Node.js 18+
- MySQL 8 (o Docker)

## Docker (MySQL incluido)

Un solo comando levanta Apache + MySQL + Vite e inicializa la BD:

```powershell
.\scripts\docker-setup.ps1
```

```bash
chmod +x scripts/docker-setup.sh
./scripts/docker-setup.sh
```

Usa `.env.docker.example` (`DB_HOST=mysql`). App: http://localhost:8000

Detalle: [docker/README.md](../docker/README.md).

## SQLite (sin MySQL ni Docker)

Ideal para desarrollo en PC cuando MySQL no está instalado:

```powershell
.\scripts\setup-sqlite.ps1
.\scripts\dev.ps1
```

```bash
./scripts/setup-sqlite.sh
./scripts/dev.sh
```

Usa `.env.sqlite.example` y el archivo `database/database.sqlite`.

Guía completa: [DESARROLLO-LOCAL.md](DESARROLLO-LOCAL.md)

## Instalación automática (MySQL nativo)

**Windows (PowerShell):**

```powershell
.\scripts\setup.ps1
```

**Linux / macOS:**

```bash
chmod +x scripts/setup.sh
./scripts/setup.sh
```

Recrear base de datos: añade `-Fresh` (PowerShell) o `--fresh` (bash).

El script ejecuta: `composer install`, `key:generate`, `migrate --seed`, `storage:link`, `npm install`, `npm run build`, tests y `hotel:preflight`.

## Instalación manual

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Configura MySQL en `.env` (plantilla en `.env.example`):

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hoteles
DB_USERNAME=root
DB_PASSWORD=tu_clave
```

```bash
php artisan migrate --seed
npm install
npm run dev
```

Abre la app (Vite + Laravel según tu setup) y entra en `/auth/login`.

## Usuarios de demostración

Tras `migrate --seed`, la contraseña de todos los usuarios demo es: **`12345678`**

| Email | Rol | Uso principal |
|-------|-----|----------------|
| `admin@gmail.com` | Administrador | Configuración, todos los módulos |
| `recepcionista@gmail.com` | Recepcionista | Reservas, check-in/out, folios, POS, reportes |
| `housekeeping@gmail.com` | Housekeeping | Tablero de limpieza |
| `cajero@gmail.com` | Cajero | Solo POS consumos (`/hotel/pos`) |

Huésped de prueba (datos): `huesped@demo.com` (solo registro en BD, no login).

## Reservas públicas

- URL: `/reservar/costa-azul` (slug del hotel; `/reservar` redirige al predeterminado)
- API: `/api/booking/{code}/*` (ej. `costa-azul`)

### Anticipo en línea (opcional)

En `.env`:

```env
HOTEL_BOOKING_PAYMENTS_ENABLED=true
HOTEL_BOOKING_PAYMENT_PROVIDER=demo    # demo en desarrollo, stripe en producción
HOTEL_BOOKING_DEPOSIT_PERCENT=30
HOTEL_BOOKING_CURRENCY=mxn
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

- **demo**: botón “Pagar anticipo (modo demo)” en `/reservar` — solo fuera de `production`
- **stripe**: redirección a Stripe Checkout; webhook `POST /api/booking/webhooks/stripe`

Con Docker (`.env.docker.example`) los pagos demo vienen activados por defecto.

Producción con Stripe: [`docs/PAGOS-STRIPE.md`](docs/PAGOS-STRIPE.md)

## Docker

Ver [docker/README.md](../docker/README.md).

```bash
cd docker
docker-compose up -d --build
docker-compose exec apache php artisan migrate --seed
```

App: http://localhost:8000 — Vite: http://localhost:5173

## Tests

```bash
php artisan test
```

CI en GitHub Actions (`.github/workflows/ci.yml`).

## Producción

Ver [PRODUCCION.md](PRODUCCION.md) y `.env.production.example`.

## Variables útiles (.env)

```env
HOTEL_BOOKING_ENABLED=true
HOTEL_BOOKING_NOTIFY_EMAIL=true
MAIL_MAILER=log

VITE_API_URL=/api
```
