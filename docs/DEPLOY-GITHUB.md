# Deploy con GitHub Actions

## CI automático (cada push / PR)

El workflow [`.github/workflows/ci.yml`](../.github/workflows/ci.yml) ejecuta tres jobs:

| Job | Qué valida |
|-----|------------|
| **php-tests** | `composer install` + `php artisan test` (SQLite en memoria) |
| **frontend-build** | `npm ci` + `npm run build` + artefacto `public/build/` |
| **production-build** | Smoke test: composer `--no-dev`, build Vite y cachés Laravel |

## Health check (monitoreo)

Endpoint público para uptime (UptimeRobot, Pingdom, etc.):

```
GET /api/health
```

Respuesta `200` con `status: healthy` si la base de datos responde; `503` si no.

## Deploy manual por SSH — Producción

Workflow: [`.github/workflows/deploy.yml`](../.github/workflows/deploy.yml)

Antes de conectar al servidor, el workflow ejecuta **28 tests PHPUnit** y **build Vite** sobre la rama elegida. Si fallan, no se despliega.

### 0. Clave SSH para Actions

Guía: **[SSH-DEPLOY-KEY.md](SSH-DEPLOY-KEY.md)** — generar par de claves y registrar el secret `DEPLOY_PRIVATE_KEY`.

### 1. Secrets del repositorio

En GitHub → **Settings → Secrets and variables → Actions → New repository secret**:

Checklist imprimible: [`.github/SECRETS-CHECKLIST.md`](../.github/SECRETS-CHECKLIST.md)

| Secret | Ejemplo | Descripción |
|--------|---------|-------------|
| `DEPLOY_HOST` | `203.0.113.10` | IP o dominio del servidor |
| `DEPLOY_USER` | `deploy` | Usuario SSH |
| `DEPLOY_PRIVATE_KEY` | contenido de `id_rsa` | Clave privada PEM (sin passphrase recomendado) |
| `DEPLOY_PATH` | `/var/www/hoteles` | Ruta del proyecto en el servidor |
| `DEPLOY_PORT` | `22` | Opcional; puerto SSH |
| `DEPLOY_HEALTH_URL` | `https://hotel.tudominio.com` | Opcional; verifica `/api/health` tras el deploy |

### 2. Preparar el servidor

Guía paso a paso: **[SERVIDOR-INICIAL.md](SERVIDOR-INICIAL.md)**

Resumen:

- Clonar el repo en `DEPLOY_PATH`
- Primera vez: `bash scripts/server-first-install.sh`
- `.env` de producción (ver `.env.production.example`)
- PHP, Composer, Node y MySQL instalados
- Document root → `public/`

### 3. Ejecutar deploy

GitHub → **Actions → Deploy → Run workflow**

Opciones:

- **branch**: rama a desplegar (por defecto `main`)
- **run_migrations**: aplicar migraciones (`migrate --force`)

En el servidor ejecuta `scripts/github-remote-deploy.sh`:

```bash
git pull
composer install --no-dev
npm ci && npm run build
php artisan migrate --force   # si está marcado
php artisan config:cache && route:cache && view:cache
php artisan hotel:preflight   # falla el deploy si algo no está listo
```

Si configuraste `DEPLOY_HEALTH_URL`, el workflow hace además `GET /api/health` desde GitHub.

### 4. Entorno `production` (opcional)

El job usa `environment: production` para exigir aprobación manual si lo configuras en **Settings → Environments**.

## Deploy a Staging

Workflow: [`.github/workflows/deploy-staging.yml`](../.github/workflows/deploy-staging.yml)

Igual que producción: **tests + build** antes del SSH. Clave SSH: [SSH-DEPLOY-KEY.md](SSH-DEPLOY-KEY.md) (secret `STAGING_DEPLOY_PRIVATE_KEY`).

Instalación inicial en el VPS staging:

```bash
bash scripts/server-first-install.sh --staging
# Editar .env (Stripe sk_test_, APP_URL staging)
```

Guía completa: [DEPLOY-STAGING.md](DEPLOY-STAGING.md)

Secrets con prefijo `STAGING_`:

| Secret | Descripción |
|--------|-------------|
| `STAGING_DEPLOY_HOST` | Servidor staging |
| `STAGING_DEPLOY_USER` | Usuario SSH |
| `STAGING_DEPLOY_PRIVATE_KEY` | Clave privada |
| `STAGING_DEPLOY_PATH` | Ruta del proyecto staging |
| `STAGING_DEPLOY_PORT` | Opcional (22) |
| `STAGING_DEPLOY_HEALTH_URL` | URL base staging para health check post-deploy |

Al ejecutar, elige la **rama** (por defecto `develop`) y si aplicar migraciones.

Entorno GitHub: `staging` (puedes añadir protección/aprobación en Settings → Environments).

## Notificaciones Slack (CI fallido)

Secret opcional: `SLACK_WEBHOOK_URL` (Incoming Webhook de Slack).

Si algún job de CI falla en push a `main`/`master`/`develop`, se envía un mensaje al canal configurado.

## Sin GitHub Actions

Despliegue manual en el servidor:

```bash
./scripts/build-production.sh
php artisan migrate --force
```

Ver [PRODUCCION.md](PRODUCCION.md).
