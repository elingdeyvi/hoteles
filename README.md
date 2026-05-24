# Sistema de gestión hotelera

MVP **Laravel 10** + **Vue 3** (Vite): recepción, reservas en línea, POS a folio, facturación, housekeeping, reportes y dashboard operativo.

## Inicio rápido

**Opción A — SQLite (sin MySQL ni Docker, recomendado en Windows):**

```powershell
.\scripts\setup-sqlite.ps1
.\scripts\dev.ps1
```

App: http://127.0.0.1:8000 — Guía: [docs/DESARROLLO-LOCAL.md](docs/DESARROLLO-LOCAL.md)

**Opción B — Docker (MySQL incluido):**

```powershell
# Windows
.\scripts\docker-setup.ps1

# Linux / macOS
chmod +x scripts/docker-setup.sh && ./scripts/docker-setup.sh
```

App: http://localhost:8000 — Login: `/auth/login`

**Opción C — Instalación nativa (MySQL local):**

```powershell
# Windows
.\scripts\setup.ps1

# Linux / macOS
chmod +x scripts/setup.sh && ./scripts/setup.sh
```

Reinstalar BD desde cero: `.\scripts\setup.ps1 -Fresh` o `./scripts/setup.sh --fresh`

**Manual:**

```bash
composer install && cp .env.example .env && php artisan key:generate
# Editar DB_* en .env (ver .env.example)
php artisan migrate --seed
npm install && npm run dev
```

Documentación: [docs/INSTALACION.md](docs/INSTALACION.md)

## Usuarios demo

Contraseña (todos): **`12345678`**

| Usuario | Rol |
|---------|-----|
| `admin@gmail.com` | Administrador |
| `recepcionista@gmail.com` | Recepcionista |
| `housekeeping@gmail.com` | Housekeeping |
| `cajero@gmail.com` | Cajero (POS) — solo **Sierra Verde** |

Login: `/auth/login`

**Reservas públicas (demo):**

| Hotel | URL |
|-------|-----|
| Costa Azul (playa) | `/reservar/costa-azul` |
| Sierra Verde (montaña) | `/reservar/sierra-verde` |

`/reservar` redirige a Costa Azul. Admin ve ambos hoteles en el selector del header.

## Módulos principales

| Ruta | Módulo |
|------|--------|
| `/dashboard` | KPIs operación + web pendientes + POS |
| `/hotel/recepcion/*` | Huéspedes, reservas, planning, check-in |
| `/hotel/pos` | Consumos a habitación |
| `/hotel/facturacion/folios` | Folios y pagos |
| `/hotel/housekeeping` | Estados de habitación |
| `/hotel/reportes` | Ingresos y ventas POS (export CSV) |
| `/hotel/config/*` | Tipos, habitaciones, tarifas |

## Tests y CI

```bash
php artisan test
```

GitHub Actions (push/PR):

- **CI** — tests PHP + build Vite + smoke producción (`.github/workflows/ci.yml`)
- **Deploy** — producción / staging por SSH — [docs/DEPLOY-GITHUB.md](docs/DEPLOY-GITHUB.md)
- **Health** — `GET /api/health` (monitoreo uptime)

## Docker

```bash
cd docker && docker-compose up -d --build
docker-compose exec apache php artisan migrate --seed
```

Ver [docker/README.md](docker/README.md).

## Producción

```bash
./scripts/build-production.sh   # o .\scripts\build-production.ps1
```

Guía completa: [docs/PRODUCCION.md](docs/PRODUCCION.md) — checklist HTTPS, `.env`, Apache/Nginx y mantenimiento.

## Documentación del proyecto

| Documento | Contenido |
|-----------|-----------|
| [docs/MODULOS.md](docs/MODULOS.md) | Rutas, API, servicios y roles |
| [docs/REVISION-FINAL.md](docs/REVISION-FINAL.md) | Checklist pre-producción |
| [docs/SERVIDOR-INICIAL.md](docs/SERVIDOR-INICIAL.md) | Primera instalación en VPS |
| [docs/MULTI-PROPIEDAD.md](docs/MULTI-PROPIEDAD.md) | Varios hoteles en una instalación |
| [docs/STRIPE-STAGING.md](docs/STRIPE-STAGING.md) | Pagos Stripe en modo test (staging) |
| [docs/GO-LIVE.md](docs/GO-LIVE.md) | Checklist puesta en producción |
| [docs/DEPLOY-STAGING.md](docs/DEPLOY-STAGING.md) | Deploy en servidor de pruebas |
| [docs/DESARROLLO-LOCAL.md](docs/DESARROLLO-LOCAL.md) | Desarrollo con SQLite (sin MySQL) |
| [CHANGELOG.md](CHANGELOG.md) | Historial del MVP |

```bash
php artisan hotel:about      # resumen en consola
php artisan hotel:preflight  # verificación pre-deploy
php artisan hotel:secure-demo-users --force  # desactivar cuentas demo (producción)
php artisan hotel:stripe-check  # validar Stripe (producción)
```

Pagos Stripe: [`docs/PAGOS-STRIPE.md`](docs/PAGOS-STRIPE.md)

## Convenciones

Reglas para desarrollo con Cursor: [.cursorrules](.cursorrules)
