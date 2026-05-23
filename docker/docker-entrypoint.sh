#!/bin/bash
set -e

echo "Iniciando contenedor Apache..."

echo "Esperando MySQL..."
until nc -z mysql 3306; do
    sleep 1
done
echo "MySQL disponible."

if [ ! -d "vendor" ]; then
    echo "Instalando dependencias Composer..."
    composer install --optimize-autoloader --no-interaction
fi

chmod -R 775 storage bootstrap/cache 2>/dev/null || true
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

echo "Contenedor Apache listo."

exec "$@"
