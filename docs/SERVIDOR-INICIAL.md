# Instalación inicial en servidor

Guía para el **primer despliegue** en un VPS o servidor dedicado (Linux). Los deploys siguientes usan GitHub Actions o `git pull` + `scripts/github-remote-deploy.sh`.

## Requisitos

| Componente | Versión |
|------------|---------|
| PHP | 8.1+ (extensiones: pdo_mysql, mbstring, openssl, tokenizer, xml, ctype, json, bcmath, gd) |
| Composer | 2.x |
| Node.js | 18+ |
| MySQL | 8.x |
| Git | 2.x |

## 1. Base de datos

```sql
CREATE DATABASE hoteles_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'hoteles_user'@'localhost' IDENTIFIED BY 'contraseña_segura';
GRANT ALL ON hoteles_prod.* TO 'hoteles_user'@'localhost';
FLUSH PRIVILEGES;
```

## 2. Clonar el proyecto

```bash
sudo mkdir -p /var/www/hoteles
sudo chown "$USER:$USER" /var/www/hoteles
cd /var/www/hoteles
git clone https://github.com/TU_ORG/hoteles.git .
```

## 3. Instalación automática

**Producción:**

```bash
chmod +x scripts/server-first-install.sh
bash scripts/server-first-install.sh
```

**Staging (datos demo + Stripe test):**

```bash
bash scripts/server-first-install.sh --staging
cp .env.staging.example .env   # si aún no existe
```

Clave SSH para GitHub Actions: [SSH-DEPLOY-KEY.md](SSH-DEPLOY-KEY.md)

El script:

- Crea `.env` desde `.env.production.example` si no existe
- Genera `APP_KEY`
- Instala dependencias, compila Vite, migra BD
- Ejecuta `php artisan hotel:preflight`

**Antes de migrar**, edita `.env` con `DB_*`, `APP_URL` (https) y `MAIL_*` si usarás reservas web.

## 4. Servidor web

Document root **obligatorio**: `.../hoteles/public`

Ver ejemplos Apache/Nginx en [PRODUCCION.md](PRODUCCION.md#5-servidor-web).

## 5. Verificación

```bash
php artisan hotel:about
bash scripts/verify-health.sh https://hotel.tudominio.com
```

Navegador:

- `https://hotel.tudominio.com/auth/login`
- `https://hotel.tudominio.com/reservar`

## 6. Configurar GitHub Actions (deploys siguientes)

Secrets en el repositorio (ver [DEPLOY-GITHUB.md](DEPLOY-GITHUB.md)):

| Secret | Valor |
|--------|--------|
| `DEPLOY_HOST` | IP o dominio SSH |
| `DEPLOY_USER` | usuario deploy |
| `DEPLOY_PRIVATE_KEY` | clave PEM |
| `DEPLOY_PATH` | `/var/www/hoteles` |
| `DEPLOY_HEALTH_URL` | `https://hotel.tudominio.com` (opcional, verificación post-deploy) |

Luego: **Actions → Deploy → Run workflow**.

## Staging

Repite en otro directorio (ej. `/var/www/hoteles-staging`) con secrets `STAGING_*` y workflow **Deploy Staging**.
