#!/usr/bin/env bash
# Arranque completo con Docker (Apache + MySQL + Vite).
# Uso: ./scripts/docker-setup.sh
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

require_cmd() {
  if ! command -v "$1" >/dev/null 2>&1; then
    echo "ERROR: no se encontró '$1'. Instala Docker Desktop." >&2
    exit 1
  fi
}

compose() {
  if docker compose version >/dev/null 2>&1; then
    docker compose "$@"
  else
    docker-compose "$@"
  fi
}

require_cmd docker

if [[ ! -f .env ]]; then
  cp .env.docker.example .env
  echo "Creado .env desde .env.docker.example"
else
  echo ".env existente — asegúrate de DB_HOST=mysql para Docker"
fi

echo ""
echo "=== Docker setup — hotel ==="
echo ""

cd docker
compose up -d --build

echo ""
echo "Esperando MySQL..."
for i in $(seq 1 60); do
  if compose exec -T mysql mysqladmin ping -h localhost --silent 2>/dev/null; then
  echo "MySQL listo."
    break
  fi
  if [[ "$i" -eq 60 ]]; then
    echo "ERROR: MySQL no respondió a tiempo." >&2
    exit 1
  fi
  sleep 2
done

echo ""
echo "Instalando dependencias PHP..."
compose exec -T apache composer install --no-interaction --prefer-dist

echo ""
echo "APP_KEY y base de datos..."
compose exec -T apache php artisan key:generate --force
compose exec -T apache php artisan migrate --seed --force
compose exec -T apache php artisan storage:link 2>/dev/null || true

echo ""
echo "Tests (SQLite en PHPUnit, no afecta MySQL)..."
compose exec -T apache php artisan test || true

echo ""
echo "=== Docker listo ==="
echo ""
echo "  App:     http://localhost:8000"
echo "  Vite:    http://localhost:5173"
echo "  Login:   http://localhost:8000/auth/login"
echo "  Reservar: http://localhost:8000/reservar"
echo ""
echo "Usuarios demo (password): admin@gmail.com, recepcionista@gmail.com"
echo ""
echo "Comandos:"
echo "  cd docker && compose logs -f apache"
echo "  cd docker && compose exec apache bash"
echo "  cd docker && compose down"
