# Desarrollo local (sin MySQL ni Docker)

Guía para trabajar en tu PC con **SQLite**: un solo archivo de base de datos, sin instalar MySQL ni Docker.

## Requisitos

| Componente | Notas |
|------------|--------|
| PHP 8.1+ | Con extensión `pdo_sqlite` |
| Composer | 2.x |
| Node.js | 18+ |

Comprobar SQLite en PHP:

```powershell
php -m | findstr -i sqlite
```

Debe aparecer `pdo_sqlite`.

## Instalación en un comando (Windows)

```powershell
cd c:\proyecto_personal\prueba\hoteles
.\scripts\setup-sqlite.ps1
```

Linux / macOS:

```bash
chmod +x scripts/setup-sqlite.sh scripts/dev.sh
./scripts/setup-sqlite.sh
```

Reinstalar BD desde cero:

```powershell
.\scripts\setup-sqlite.ps1 -Fresh
```

El script:

1. Copia `.env.sqlite.example` → `.env`
2. Crea `database/database.sqlite`
3. `migrate --seed` (2 hoteles demo, usuarios, POS)
4. `npm run build`
5. Ejecuta **28 tests**

## Arrancar la aplicación

**Windows** (abre 2 ventanas: Laravel + Vite):

```powershell
.\scripts\dev.ps1
```

**Manual** (2 terminales):

```bash
php artisan serve --host=127.0.0.1 --port=8000
npm run dev
```

## URLs

| URL | Descripción |
|-----|-------------|
| http://127.0.0.1:8000/auth/login | Panel del hotel |
| http://127.0.0.1:8000/reservar/costa-azul | Reservas web (playa) |
| http://127.0.0.1:8000/reservar/sierra-verde | Reservas web (montaña) |
| http://127.0.0.1:8000/api/health | Health check |

## Usuarios demo

Contraseña (todos): **`12345678`**

| Email | Rol |
|-------|-----|
| admin@gmail.com | Administrador (ambos hoteles) |
| recepcionista@gmail.com | Recepcionista (Costa Azul + Sierra Verde) |
| housekeeping@gmail.com | Housekeeping (Costa Azul) |
| cajero@gmail.com | Cajero POS (Sierra Verde) |

## Pagos demo

En `.env.sqlite.example` los pagos están activados:

```env
HOTEL_BOOKING_PAYMENTS_ENABLED=true
HOTEL_BOOKING_PAYMENT_PROVIDER=demo
```

Tras reservar en `/reservar/*`, use el botón **Pagar anticipo (modo demo)**.

## Flujo de prueba recomendado

1. Login como `admin@gmail.com` → selector con **Costa Azul** y **Sierra Verde**
2. Reserva web en `/reservar/sierra-verde` + pago demo
3. Login `recepcionista@gmail.com` → confirmar / check-in
4. Login `cajero@gmail.com` → solo ve Sierra Verde → POS
5. `php artisan test` antes de commit

## SQLite vs MySQL vs Docker

| Modo | Cuándo usarlo |
|------|----------------|
| **SQLite** (`setup-sqlite.ps1`) | Desarrollo rápido en PC sin MySQL |
| **MySQL** (`setup.ps1`) | Igual que producción |
| **Docker** (`docker-setup.ps1`) | MySQL + Apache containerizados |

SQLite es válido para desarrollo y pruebas del MVP. En **staging/producción** use MySQL.

## Problemas frecuentes

### `pdo_sqlite` missing

Edite `php.ini` y descomente:

```ini
extension=pdo_sqlite
extension=sqlite3
```

Reinicie la terminal.

### Pantalla en blanco / 404 en assets

```bash
npm run build
# o con hot reload:
npm run dev
```

### Error de migración

```powershell
.\scripts\setup-sqlite.ps1 -Fresh
```

### Cambié .env y no aplica

```bash
php artisan config:clear
php artisan optimize:clear
```

## Siguiente paso

- Deploy staging: [`DEPLOY-STAGING.md`](DEPLOY-STAGING.md)
- Go-live: [`GO-LIVE.md`](GO-LIVE.md)
