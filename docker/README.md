# Docker — Gestión hotelera

Stack: **PHP 8.1 Apache** + **MySQL 8** + **Node 18** (Vite).

## 1. Variables en `.env` (raíz del proyecto)

```env
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=hoteles
DB_USERNAME=laravel
DB_PASSWORD=root

VITE_API_URL=http://localhost:8000/api
```

`DB_HOST=mysql` es el nombre del servicio en `docker-compose.yml`.

## 2. Levantar servicios

**Automático (desde la raíz del proyecto):**

```powershell
.\scripts\docker-setup.ps1
```

```bash
./scripts/docker-setup.sh
```

**Manual** — desde esta carpeta:

```bash
docker compose up -d --build
docker compose exec apache php artisan key:generate --force
docker compose exec apache php artisan migrate --seed
```

El seeder carga hotel de demo (**Hotel Costa Azul**), habitaciones, catálogo POS y usuarios con contraseña **`password`**.

## Servicios

| Servicio | Puerto | URL |
|----------|--------|-----|
| apache | 8000 | http://localhost:8000 |
| node (Vite) | 5173 | http://localhost:5173 |
| mysql | 3306 | localhost:3306 |

## Comandos útiles

```bash
# Logs
docker-compose logs -f apache

# Shell en la app
docker-compose exec apache bash

# Tests
docker-compose exec apache php artisan test

# Composer / npm
docker-compose exec apache composer install
docker-compose exec node npm install
```

## Detener

```bash
docker-compose down
# Con volúmenes: docker-compose down -v
```
