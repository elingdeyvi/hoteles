#!/usr/bin/env bash
# Instalación inicial — Sistema de gestión hotelera (Linux / macOS)
# Uso: ./scripts/setup.sh
#      ./scripts/setup.sh --fresh

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
echo "=== Setup hotel — $ROOT ==="
echo ""

require_cmd php
require_cmd composer
require_cmd npm

if [[ ! -f .env ]]; then
  cp .env.example .env
  echo "Creado .env desde .env.example"
else
  echo ".env ya existe (no se sobrescribe)"
fi

echo ""
echo "[1/6] Composer install..."
composer install --no-interaction --prefer-dist

echo ""
echo "[2/6] APP_KEY..."
php artisan key:generate --force

echo ""
echo "[3/6] Base de datos..."
echo "Asegúrate de que MySQL esté activo y DB_* en .env sea correcto."
if [[ "$FRESH" -eq 1 ]]; then
  php artisan migrate:fresh --seed --force
else
  php artisan migrate --seed --force
fi

echo ""
echo "[4/6] Storage link..."
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
echo "=== Listo ==="
echo ""
echo "Usuarios demo (contraseña: password):"
echo "  admin@gmail.com          — Administrador"
echo "  recepcionista@gmail.com  — Recepcionista"
echo "  housekeeping@gmail.com   — Housekeeping"
echo "  cajero@gmail.com         — Cajero (POS)"
echo ""
echo "Siguiente paso:"
echo "  npm run dev"
echo "  php artisan serve   (si no usas Docker)"
echo ""
echo "Login: /auth/login  |  Reservas públicas: /reservar"
echo ""
