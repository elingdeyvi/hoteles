# Changelog

## [1.0.0] — MVP Gestión hotelera

Transformación del proyecto desde Control de Lavado (TIBU) a sistema hotelero modular.

### Módulos

- Configuración: tipos de habitación, habitaciones, tarifas, empresa
- Recepción: huéspedes, reservas, planning, check-in/out
- Facturación: folios, cargos, pagos, factura PDF
- POS: consumos a folio (restaurante, bar, tienda)
- Housekeeping: estados de habitación
- Reportes: ingresos y ventas POS (export CSV)
- Booking web: `/reservar/{slug}` + API `/api/booking/{code}/*` + anticipo en línea
- Multi-propiedad: tabla `properties`, CRUD en panel, selector en header
- Dashboard: KPIs, reservas web pendientes, POS del día

### Infraestructura

- Tests PHPUnit (flujo completo hotel)
- GitHub Actions CI (tests + build Vite)
- Deploy SSH producción y staging
- Health check `/api/health`
- Scripts: `setup.ps1/sh`, `build-production.ps1/sh`
- Documentación: instalación, producción, deploy

### Limpieza

- Eliminado dominio lavandería (controladores, modelos, rutas, factories)
- Menú y marca actualizados a hotel
- Plantillas PDF/email legacy de lavandería removidas
- Locales i18n reducidos a claves activas (hotel + configuración empresa)
- Comando `php artisan hotel:preflight` para validar servidor antes de producción
- Deploy: `server-first-install.sh`, health check post-deploy (`DEPLOY_HEALTH_URL`), preflight en `github-remote-deploy.sh`
- Docker: `docker-setup.ps1/sh`, `.env.docker.example`, healthcheck MySQL en compose

### Producción

- Comando `php artisan hotel:secure-demo-users` para desactivar cuentas demo
- `hotel:preflight` valida usuarios demo, proveedor de pago y hoteles activos en producción
- Guía go-live: `docs/GO-LIVE.md`
- Stripe staging (modo test): `docs/STRIPE-STAGING.md`, `.env.staging.example`
- Desarrollo local SQLite: `setup-sqlite.ps1/sh`, `dev.ps1/sh`, `docs/DESARROLLO-LOCAL.md`
- `server-first-install.sh --staging`, guía clave SSH `docs/SSH-DEPLOY-KEY.md`

- Segundo hotel **Sierra Verde** (`sierra-verde`) con inventario y POS en seeders
- Asignación demo: recepcionista → ambos hoteles; housekeeping → Costa Azul; cajero → Sierra Verde
- Guía deploy staging: `docs/DEPLOY-STAGING.md`
