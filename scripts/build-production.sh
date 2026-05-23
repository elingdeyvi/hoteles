#!/usr/bin/env bash
# Build optimizado para producción (assets Vite + cachés Laravel)
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

echo "=== Build producción — hotel ==="

if [[ ! -f .env ]]; then
  echo "AVISO: no existe .env. Copia .env.production.example antes de desplegar." >&2
fi

echo "[1/5] Composer (sin dev)..."
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

echo "[2/5] Frontend (Vite)..."
if [[ -f package-lock.json ]]; then
  npm ci
else
  npm install
fi
npm run build

echo "[3/5] Storage link..."
php artisan storage:link 2>/dev/null || true

echo "[4/5] Cachés Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "[5/5] Verificación build..."
if [[ ! -d public/build ]]; then
  echo "ERROR: no se generó public/build. Revisa npm run build." >&2
  exit 1
fi

echo ""
echo "=== Build completado ==="
echo "Siguiente: php artisan migrate --force"
echo "Permisos: chown/chmod en storage y bootstrap/cache"
echo "Guía: docs/PRODUCCION.md"
echo ""
