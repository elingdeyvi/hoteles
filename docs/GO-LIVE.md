# Go-live — Checklist de puesta en producción

Lista rápida antes de abrir el sistema a huéspedes y personal real.

## Fase 1 — Servidor

- [ ] VPS con PHP 8.1+, MySQL 8, HTTPS
- [ ] Primera instalación: [`scripts/server-first-install.sh`](../scripts/server-first-install.sh)
- [ ] `.env` desde [`.env.production.example`](../.env.production.example)
- [ ] `php artisan key:generate --force`
- [ ] `php artisan migrate --force` (**sin** `--seed` en producción)

Guía completa: [`PRODUCCION.md`](PRODUCCION.md)

## Fase 2 — Build y verificación

```bash
./scripts/build-production.sh
php artisan hotel:about
php artisan hotel:preflight
bash scripts/verify-health.sh https://hotel.tudominio.com
```

## Fase 3 — Seguridad

```bash
# Si alguna vez corrió --seed en producción:
php artisan hotel:secure-demo-users --force
```

- [ ] Usuarios demo desactivados (`hotel:secure-demo-users`)
- [ ] Usuarios reales creados en el panel
- [ ] `APP_DEBUG=false`, `ALLOW_DEV_SETUP_ROUTES=false`
- [ ] Contraseñas fuertes en MySQL y SMTP

## Fase 4 — Reservas web

- [ ] `HOTEL_BOOKING_ENABLED=true`
- [ ] SMTP configurado (`MAIL_*`) para correos de reserva
- [ ] Probar `/reservar/costa-azul` (y otros hoteles activos)

### Pagos con anticipo (opcional)

```env
HOTEL_BOOKING_PAYMENTS_ENABLED=true
HOTEL_BOOKING_PAYMENT_PROVIDER=stripe
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

```bash
php artisan hotel:stripe-check
php artisan hotel:preflight
```

Guía: [`PAGOS-STRIPE.md`](PAGOS-STRIPE.md)

## Fase 5 — Multi-propiedad

- [ ] Hoteles creados en **Configuración → Hoteles / propiedades**
- [ ] Usuarios asignados a sus hoteles (**Usuarios → Hoteles asignados**)
- [ ] Selector del header probado con recepcionista y cajero

Guía: [`MULTI-PROPIEDAD.md`](MULTI-PROPIEDAD.md)

## Fase 6 — Deploy continuo

- [ ] Clave SSH generada — [`docs/SSH-DEPLOY-KEY.md`](SSH-DEPLOY-KEY.md)
- [ ] Secrets GitHub configurados ([`.github/SECRETS-CHECKLIST.md`](../.github/SECRETS-CHECKLIST.md))
- [ ] Staging probado: **Actions → Deploy Staging** (tests + build + SSH)
- [ ] Producción: **Actions → Deploy** (solo tras staging OK)
- [ ] CI en verde (`php artisan test` — 28 tests)
- [ ] Monitoreo en `/api/health`

Guía Actions: [`DEPLOY-GITHUB.md`](DEPLOY-GITHUB.md)

## Comandos de referencia

| Comando | Uso |
|---------|-----|
| `hotel:about` | Resumen del sistema instalado |
| `hotel:preflight` | Checklist técnico pre-deploy |
| `hotel:secure-demo-users` | Desactivar cuentas demo |
| `hotel:stripe-check` | Validar Stripe |

Checklist detallado: [`REVISION-FINAL.md`](REVISION-FINAL.md)
