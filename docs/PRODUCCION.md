# Despliegue a producción

Guía para publicar el sistema hotelero en un servidor Linux con **Apache o Nginx**, **PHP 8.1+**, **MySQL 8** y assets compilados con Vite.

## Checklist previo

En el servidor, tras configurar `.env` y compilar assets:

```bash
php artisan hotel:preflight
```

Debe terminar con **Listo para producción.** Si falla MySQL o `manifest.json`, corrige antes de abrir el sitio al público.

- [ ] Dominio y certificado HTTPS (Let's Encrypt)
- [ ] MySQL creado (usuario con permisos solo sobre su BD)
- [ ] PHP 8.1+ con extensiones: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `gd`
- [ ] Composer 2.x y Node 18+ (solo en máquina de build o CI)
- [ ] Document root del vhost apuntando a `public/`

## 1. Variables de entorno

```bash
cp .env.production.example .env
nano .env
```

Imprescindible en producción:

| Variable | Valor |
|----------|--------|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | URL pública con `https://` |
| `APP_KEY` | Generar en servidor: `php artisan key:generate --force` |
| `ALLOW_DEV_SETUP_ROUTES` | `false` |
| `DB_*` | Credenciales reales |
| `MAIL_*` | SMTP real si usas reservas web con correo |
| `SESSION_SECURE_COOKIE` | `true` (con HTTPS) |

Plantilla completa: [.env.production.example](../.env.production.example)

## 2. Build de artefactos

En el servidor o en CI, desde la raíz del proyecto:

**Linux / macOS:**

```bash
chmod +x scripts/build-production.sh
./scripts/build-production.sh
```

**Windows:**

```powershell
.\scripts\build-production.ps1
```

El script ejecuta:

- `composer install --no-dev --optimize-autoloader`
- `npm ci` + `npm run build` (genera `public/build/`)
- `php artisan storage:link`
- Cachés: `config`, `routes`, `views`

## 3. Base de datos

```bash
php artisan migrate --force
# Solo primera vez o entorno nuevo con datos demo:
# php artisan migrate --seed --force
```

En producción **no** uses seeders de demo salvo entorno de staging.

## 4. Permisos (Linux)

```bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

Ajusta `www-data` al usuario del servidor web (puede ser `apache`, `nginx`).

## 5. Servidor web

### Apache

`DocumentRoot` → `/ruta/al/proyecto/public`

```apache
<VirtualHost *:443>
    ServerName hotel.tudominio.com
    DocumentRoot /var/www/hoteles/public

    <Directory /var/www/hoteles/public>
        AllowOverride All
        Require all granted
    </Directory>

    SSLEngine on
    # ... certificados SSL
</VirtualHost>
```

`public/.htaccess` de Laravel debe estar activo (`mod_rewrite`).

### Nginx

```nginx
server {
    listen 443 ssl http2;
    server_name hotel.tudominio.com;
    root /var/www/hoteles/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## 6. Verificación post-deploy

```bash
php artisan hotel:about
php artisan hotel:secure-demo-users --force   # si corrió seed en el servidor
php artisan hotel:preflight
bash scripts/verify-health.sh https://hotel.tudominio.com
```

Desde el navegador:

1. `https://hotel.tudominio.com/auth/login` — carga Vue (revisa consola sin errores 404 en `/build/`)
2. Login con usuario real (no demo)
3. `https://hotel.tudominio.com/reservar` — motor de reservas público

## 7. Mantenimiento

```bash
# Actualizar código
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Limpiar cachés (si cambias .env)

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Copias de seguridad

- Base de datos MySQL: dump diario automatizado
- `storage/app/` (logos, uploads): incluir en backup de archivos

## 8. Seguridad recomendada

- Firewall: solo 80/443 públicos; MySQL no expuesto a internet
- Usuarios demo: `php artisan hotel:secure-demo-users --force` (ver [`GO-LIVE.md`](GO-LIVE.md))
- `php artisan config:cache` evita leer `.env` en cada request (no subas `.env` a repositorios)
- Rate limiting API ya configurado (`public-booking`, `login`)

## 9. Docker en producción

Para entornos containerizados, compila assets **antes** de la imagen o en etapa multi-stage:

```dockerfile
# Ejemplo: etapa node para build
FROM node:18-alpine AS frontend
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

FROM php:8.1-apache
# ... copiar vendor, código, y public/build desde etapa frontend
```

Ver [docker/README.md](../docker/README.md) para desarrollo local.

## 10. CI/CD (GitHub Actions)

**CI** (automático en push/PR): `.github/workflows/ci.yml`

- Tests PHP (`php artisan test`)
- Build Vite (`npm run build`)
- Smoke test de build de producción

**Deploy** (manual): `.github/workflows/deploy.yml` — SSH al servidor. Configuración: [DEPLOY-GITHUB.md](DEPLOY-GITHUB.md).

Alternativa sin Actions: ejecutar `scripts/build-production.sh` directamente en el servidor.
