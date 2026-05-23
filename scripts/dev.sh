#!/usr/bin/env bash
# Arranca Laravel + Vite (requiere tmux o 2 terminales).
# Uso: ./scripts/dev.sh

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

if [[ ! -f .env ]]; then
  echo "ERROR: no hay .env. Ejecute ./scripts/setup-sqlite.sh" >&2
  exit 1
fi

if command -v tmux >/dev/null 2>&1; then
  tmux new-session -d -s hotel-dev "cd '$ROOT' && php artisan serve --host=127.0.0.1 --port=8000"
  tmux split-window -h "cd '$ROOT' && npm run dev"
  tmux attach-session -t hotel-dev
else
  echo "=== Dev hotel ==="
  echo "Abra otra terminal y ejecute: npm run dev"
  echo "App: http://127.0.0.1:8000"
  echo ""
  php artisan serve --host=127.0.0.1 --port=8000
fi
