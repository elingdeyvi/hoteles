#!/usr/bin/env bash
# Ejecutar en el servidor vía SSH (GitHub Actions deploy).
# Variables: RUN_MIGRATIONS (true|false)
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

echo "==> Deploy hotel — $(date -Iseconds)"
echo "    Ruta: $ROOT"
echo "    Rama: $(git rev-parse --abbrev-ref HEAD 2>/dev/null || echo '?')"
echo "    Commit: $(git rev-parse --short HEAD 2>/dev/null || echo '?')"
echo "    Entorno: $(grep -E '^APP_ENV=' .env 2>/dev/null | cut -d= -f2- || echo '?')"

echo "==> Composer (producción)"
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

echo "==> Frontend (Vite)"
npm ci
npm run build

echo "==> Laravel"
php artisan storage:link 2>/dev/null || true

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
  echo "==> Migraciones"
  php artisan migrate --force
fi

php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

if grep -qE '^HOTEL_BOOKING_PAYMENT_PROVIDER=stripe' .env 2>/dev/null \
  && grep -qE '^HOTEL_BOOKING_PAYMENTS_ENABLED=true' .env 2>/dev/null; then
  echo "==> Stripe check"
  php artisan hotel:stripe-check
fi

echo "==> Preflight"
if ! php artisan hotel:preflight; then
  echo "ERROR: hotel:preflight falló. Revisa .env, MySQL y public/build." >&2
  exit 1
fi

echo "==> Permisos (storage y cache)"
if command -v chgrp >/dev/null 2>&1; then
  for dir in storage bootstrap/cache; do
    if [ -d "$dir" ]; then
      chmod -R ug+rwx "$dir" 2>/dev/null || true
    fi
  done
fi

echo "==> Resumen"
php artisan hotel:about 2>/dev/null || true

echo "==> Deploy OK: $ROOT"
