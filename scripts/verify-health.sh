#!/usr/bin/env bash
# Verifica GET /api/health (uso post-deploy).
# Uso: bash scripts/verify-health.sh https://hotel.tudominio.com
set -euo pipefail

BASE="${1:?Indica la URL base, ej. https://hotel.tudominio.com}"
URL="${BASE%/}/api/health"

echo "GET $URL"
HTTP=$(curl -fsS -o /tmp/hotel-health.json -w "%{http_code}" "$URL")
BODY=$(cat /tmp/hotel-health.json)
rm -f /tmp/hotel-health.json

echo "HTTP $HTTP"
echo "$BODY"

if [ "$HTTP" != "200" ]; then
  echo "ERROR: health check no devolvió 200" >&2
  exit 1
fi

if ! echo "$BODY" | grep -q '"status"'; then
  echo "ERROR: respuesta inesperada" >&2
  exit 1
fi

echo "OK — servicio saludable"
