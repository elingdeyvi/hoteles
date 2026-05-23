# Deploy en staging

Guía para desplegar el MVP en un **servidor de pruebas** antes de producción.

## 1. Servidor

Requisitos: PHP 8.1+, MySQL 8, Composer, Node 18+ (solo para build en CI o local), Nginx/Apache.

Primera instalación en el VPS:

```bash
git clone <repo-url> /var/www/hoteles-staging
cd /var/www/hoteles-staging
bash scripts/server-first-install.sh
```

Copie [`.env.staging.example`](../.env.staging.example) → `.env` y ajuste:

```env
APP_ENV=staging
APP_DEBUG=false
APP_URL=https://staging.tudominio.com

DB_DATABASE=hoteles_staging
DB_USERNAME=...
DB_PASSWORD=...

ALLOW_DEV_SETUP_ROUTES=false

# Pagos demo en staging (rápido, sin tarjeta)
# HOTEL_BOOKING_PAYMENTS_ENABLED=true
# HOTEL_BOOKING_PAYMENT_PROVIDER=demo

# Pagos Stripe TEST (recomendado antes de producción)
HOTEL_BOOKING_PAYMENTS_ENABLED=true
HOTEL_BOOKING_PAYMENT_PROVIDER=stripe
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

Guía Stripe test: [`STRIPE-STAGING.md`](STRIPE-STAGING.md)

```bash
php artisan key:generate
php artisan migrate --seed
npm ci && npm run build
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan hotel:preflight
```

## 2. Secrets en GitHub

Configure los secrets de staging (ver [.github/SECRETS-CHECKLIST.md](../.github/SECRETS-CHECKLIST.md)):

- `STAGING_DEPLOY_HOST`
- `STAGING_DEPLOY_USER`
- `STAGING_DEPLOY_PRIVATE_KEY`
- `STAGING_DEPLOY_PATH` (ej. `/var/www/hoteles-staging`)
- `STAGING_DEPLOY_HEALTH_URL` (ej. `https://staging.tudominio.com`)

Cree el entorno **staging** en GitHub → Settings → Environments (opcional: aprobación manual).

## 3. Disparar deploy

Push a la rama configurada en `.github/workflows/deploy-staging.yml` (normalmente `develop`) o ejecute el workflow manualmente desde Actions.

El workflow:

1. Ejecuta tests + build Vite
2. Conecta por SSH al servidor
3. `git pull`, `composer install`, migraciones, cache
4. Verifica `/api/health` si `STAGING_DEPLOY_HEALTH_URL` está definido

## 4. Verificación post-deploy

```bash
bash scripts/verify-health.sh https://staging.tudominio.com
php artisan hotel:about
php artisan hotel:preflight
```

Checklist manual:

- [ ] Login `admin@gmail.com` / `password`
- [ ] Selector de hotel muestra **Costa Azul** y **Sierra Verde**
- [ ] Reserva web en `/reservar/sierra-verde`
- [ ] Flujo recepción en un hotel asignado (`recepcionista@gmail.com`)
- [ ] Cajero solo ve Sierra Verde (`cajero@gmail.com`)
- [ ] Pago Stripe test en `/reservar/costa-azul` (tarjeta `4242…`) — ver [`STRIPE-STAGING.md`](STRIPE-STAGING.md)

## 5. Pasar a producción

1. Clonar la misma configuración con `APP_ENV=production`
2. Cambiar contraseñas demo o desactivar usuarios demo
3. Configurar SMTP real para correos de reserva
4. Si usa Stripe: [`PAGOS-STRIPE.md`](PAGOS-STRIPE.md) + `php artisan hotel:stripe-check`
5. Secrets del workflow `Deploy` (producción) en lugar de `STAGING_*`
