#!/usr/bin/env bash
# Instalación inicial en servidor Linux (ejecutar una sola vez en DEPLOY_PATH).
# Uso:
#   bash scripts/server-first-install.sh              # producción (.env.production.example)
#   bash scripts/server-first-install.sh --staging    # staging (.env.staging.example)
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

MODE="production"
ENV_TEMPLATE=".env.production.example"
if [[ "${1:-}" == "--staging" ]]; then
  MODE="staging"
  ENV_TEMPLATE=".env.staging.example"
fi

echo "=== Instalación inicial — gestión hotelera ($MODE) ==="

if [[ ! -f .env ]]; then
  if [[ -f "$ENV_TEMPLATE" ]]; then
    cp "$ENV_TEMPLATE" .env
    echo "Creado .env desde $ENV_TEMPLATE — edítalo (DB_*, APP_URL, MAIL_*, Stripe) antes de migrar."
  else
    echo "ERROR: no hay .env ni $ENV_TEMPLATE" >&2
    exit 1
  fi
fi

if ! grep -qE '^APP_KEY=.{10,}' .env 2>/dev/null; then
  php artisan key:generate --force
fi

echo ""
echo ">>> Revise .env ahora (Ctrl+C para cancelar, Enter para continuar)"
read -r _

echo "[1/6] Composer..."
composer install --no-interaction --prefer-dist --optimize-autoloader

echo "[2/6] Frontend..."
npm ci
npm run build

echo "[3/6] Storage..."
php artisan storage:link 2>/dev/null || true

if [[ "$MODE" == "staging" ]]; then
  read -r -p "¿Ejecutar migrate --seed (datos demo + 2 hoteles)? [Y/n] " SEED
  SEED=${SEED:-Y}
else
  read -r -p "¿Ejecutar migrate --seed (datos demo)? [y/N] " SEED
fi

if [[ "${SEED,,}" == "y" ]]; then
  php artisan migrate --seed --force
  if [[ "$MODE" == "production" ]]; then
    echo ""
    echo "AVISO: En producción real ejecute después:"
    echo "  php artisan hotel:secure-demo-users --force"
  fi
else
  php artisan migrate --force
fi

echo "[4/6] Cachés..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "[5/6] Preflight..."
php artisan hotel:preflight

echo "[6/6] Permisos"
chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true

echo ""
echo "=== Instalación completada ($MODE) ==="
echo "Document root del vhost → $ROOT/public"
echo "Health: bash scripts/verify-health.sh \${APP_URL}"
if [[ "$MODE" == "staging" ]]; then
  echo "Stripe test: docs/STRIPE-STAGING.md"
  echo "Deploy CI: GitHub Actions → Deploy Staging"
else
  echo "Go-live: docs/GO-LIVE.md"
fi
