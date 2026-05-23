#!/usr/bin/env bash
# Instalación local con SQLite — sin MySQL ni Docker.
# Uso: ./scripts/setup-sqlite.sh
#      ./scripts/setup-sqlite.sh --fresh

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

FRESH=0
if [[ "${1:-}" == "--fresh" ]]; then
  FRESH=1
fi

require_cmd() {
  if ! command -v "$1" >/dev/null 2>&1; then
    echo "ERROR: no se encontró '$1' en el PATH." >&2
    exit 1
  fi
}

echo ""
echo "=== Setup hotel (SQLite) — $ROOT ==="
echo ""

require_cmd php
require_cmd composer
require_cmd npm

if ! php -m 2>/dev/null | grep -q pdo_sqlite; then
  echo "ERROR: extensión PHP pdo_sqlite no disponible." >&2
  exit 1
fi

cp .env.sqlite.example .env
echo "Configurado .env desde .env.sqlite.example"

touch database/database.sqlite

echo ""
echo "[1/7] Composer install..."
composer install --no-interaction --prefer-dist

echo ""
echo "[2/7] APP_KEY..."
php artisan key:generate --force

echo ""
echo "[3/7] Base de datos (SQLite)..."
if [[ "$FRESH" -eq 1 ]]; then
  php artisan migrate:fresh --seed --force
else
  php artisan migrate --seed --force
fi

echo ""
echo "[4/7] Storage link..."
php artisan storage:link 2>/dev/null || true

echo ""
echo "[5/7] npm install + build..."
npm install
npm run build

echo ""
echo "[6/7] Tests..."
php artisan test

echo ""
echo "[7/7] Resumen..."
php artisan hotel:about
php artisan hotel:preflight

echo ""
echo "=== Listo (SQLite) ==="
echo ""
echo "Arrancar: ./scripts/dev.sh"
echo "Login: http://127.0.0.1:8000/auth/login (admin@gmail.com / password)"
echo ""
