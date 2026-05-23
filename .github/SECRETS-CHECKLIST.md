# Checklist de secrets — GitHub Actions

Copia esta lista al configurar el repositorio en **Settings → Secrets and variables → Actions**.

## CI (opcional)

- [ ] `SLACK_WEBHOOK_URL` — notificación si falla CI en `main`/`develop`

## Producción (`Deploy` workflow)

- [ ] `DEPLOY_HOST`
- [ ] `DEPLOY_USER`
- [ ] `DEPLOY_PRIVATE_KEY` (PEM completo, incluye `-----BEGIN...`)
- [ ] `DEPLOY_PATH` (ej. `/var/www/hoteles`)
- [ ] `DEPLOY_PORT` (opcional, default SSH 22 si se omite en appleboy)
- [ ] `DEPLOY_HEALTH_URL` (opcional, ej. `https://hotel.tudominio.com`)

Crear entorno **production** en Settings → Environments si quieres aprobación manual antes de cada deploy.

## Staging (`Deploy Staging` workflow)

- [ ] `STAGING_DEPLOY_HOST`
- [ ] `STAGING_DEPLOY_USER`
- [ ] `STAGING_DEPLOY_PRIVATE_KEY`
- [ ] `STAGING_DEPLOY_PATH`
- [ ] `STAGING_DEPLOY_PORT` (opcional)
- [ ] `STAGING_DEPLOY_HEALTH_URL` (opcional)

Entorno **staging** recomendado para pruebas sin tocar producción. Guía paso a paso: [`docs/DEPLOY-STAGING.md`](../docs/DEPLOY-STAGING.md).

## Verificación local antes del primer deploy

En el servidor, tras `server-first-install.sh` o `server-first-install.sh --staging`:

```bash
php artisan hotel:preflight
bash scripts/verify-health.sh https://tu-dominio.com
```

Clave SSH: [`docs/SSH-DEPLOY-KEY.md`](../docs/SSH-DEPLOY-KEY.md)
