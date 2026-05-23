# Stripe en staging (modo test)

Pruebe pagos reales con tarjetas de test **antes** de activar claves Live en producción.

## 1. Variables

Copie [`.env.staging.example`](../.env.staging.example) a `.env` en el servidor staging:

```env
APP_ENV=staging
APP_URL=https://staging.tudominio.com

HOTEL_BOOKING_PAYMENTS_ENABLED=true
HOTEL_BOOKING_PAYMENT_PROVIDER=stripe
HOTEL_BOOKING_DEPOSIT_PERCENT=30
HOTEL_BOOKING_CURRENCY=mxn

STRIPE_SECRET=sk_test_xxxxxxxx
STRIPE_WEBHOOK_SECRET=whsec_xxxxxxxx
```

## 2. Webhook en Stripe (modo Test)

Dashboard Stripe → **Developers → Webhooks** (toggle **Test mode** activado)

| Campo | Valor |
|-------|--------|
| URL | `https://staging.tudominio.com/api/booking/webhooks/stripe` |
| Evento | `checkout.session.completed` |

Copie el signing secret de **test** a `STRIPE_WEBHOOK_SECRET`.

## 3. Verificación en servidor

```bash
php artisan config:cache
php artisan hotel:stripe-check
php artisan hotel:preflight
```

`hotel:stripe-check` debe mostrar **modo TEST** y conexión API OK.

## 4. Prueba manual end-to-end

1. Reserva en `https://staging.tudominio.com/reservar/costa-azul`
2. Complete datos y pulse **Pagar anticipo con tarjeta**
3. En Stripe Checkout use tarjeta test: `4242 4242 4242 4242`, fecha futura, CVC cualquiera
4. Tras el pago, vuelve a `/reservar/costa-azul?payment=success&folio=...`
5. Consulte estado: reserva **confirmada**, `payment_status` = paid
6. Dashboard recepción: reserva ya no pendiente de pago

## 5. Stripe CLI (desarrollo local)

Si prueba en localhost sin HTTPS público:

```bash
stripe listen --forward-to http://localhost:8000/api/booking/webhooks/stripe
```

Use el `whsec_...` que imprime la CLI en `STRIPE_WEBHOOK_SECRET`.

## 6. Tarjetas de prueba útiles

| Escenario | Número |
|-----------|--------|
| Pago exitoso | `4242 4242 4242 4242` |
| Requiere autenticación 3DS | `4000 0025 0000 3155` |
| Rechazada | `4000 0000 0000 9995` |

Documentación: [stripe.com/docs/testing](https://stripe.com/docs/testing)

## 7. Pasar a producción

Cuando staging funcione:

1. Cambie a claves `sk_live_` / webhook Live
2. `APP_ENV=production`
3. `php artisan hotel:secure-demo-users --force`
4. [`PAGOS-STRIPE.md`](PAGOS-STRIPE.md) + [`GO-LIVE.md`](GO-LIVE.md)
