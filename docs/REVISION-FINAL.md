# Revisión final MVP — Checklist

Use esta lista antes de entregar o desplegar a producción.

## Funcional

- [ ] `php artisan migrate --seed` sin errores
- [ ] Login con `admin@gmail.com` / `password`
- [ ] Flujo: reserva → check-in → POS → pago → check-out
- [ ] Reserva web en `/reservar` y confirmación en recepción
- [ ] `GET /api/health` responde 200
- [ ] `php artisan test` — 28 tests en verde

## Seguridad

- [ ] `APP_DEBUG=false` en producción
- [ ] `ALLOW_DEV_SETUP_ROUTES=false`
- [ ] Usuarios demo desactivados (`php artisan hotel:secure-demo-users --force`)
- [ ] HTTPS activo
- [ ] MySQL no expuesto a internet

## Frontend

- [ ] `npm run build` genera `public/build/`
- [ ] Sin errores 404 en consola del navegador
- [ ] Logo y nombre del hotel en configuración empresa

## Operación

- [ ] Correo SMTP configurado (reservas web)
- [ ] Backup automático de BD
- [ ] Monitoreo apuntando a `/api/health`
- [ ] CI en verde en GitHub Actions

## Comandos útiles

```bash
php artisan hotel:about      # resumen del sistema
php artisan hotel:preflight  # checklist técnico
php artisan hotel:secure-demo-users  # desactivar demo (producción)
```
