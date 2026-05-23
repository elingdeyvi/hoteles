# Pagos en línea con Stripe

Guía para activar **anticipos de reserva web** con Stripe Checkout en producción.

## 1. Cuenta Stripe

1. Crear cuenta en [stripe.com](https://stripe.com)
2. Activar **modo Live** cuando el hotel esté listo
3. Obtener claves en **Developers → API keys**:
   - `sk_live_...` (secreta)
   - Publicar `pk_live_...` solo si luego usas Elements en frontend (Checkout no la requiere en servidor)

## 2. Variables `.env`

```env
HOTEL_BOOKING_PAYMENTS_ENABLED=true
HOTEL_BOOKING_PAYMENT_PROVIDER=stripe
HOTEL_BOOKING_DEPOSIT_PERCENT=30
HOTEL_BOOKING_CURRENCY=mxn

STRIPE_SECRET=sk_live_xxxxxxxx
STRIPE_WEBHOOK_SECRET=whsec_xxxxxxxx
```

En **staging/pruebas** use `sk_test_...` y el webhook de test. Guía paso a paso: [`STRIPE-STAGING.md`](STRIPE-STAGING.md)

## 3. Webhook en Stripe Dashboard

**Developers → Webhooks → Add endpoint**

| Campo | Valor |
|-------|--------|
| URL | `https://tu-dominio.com/api/booking/webhooks/stripe` |
| Eventos | `checkout.session.completed` |

Copie el **Signing secret** (`whsec_...`) a `STRIPE_WEBHOOK_SECRET`.

## 4. URLs de retorno

Stripe redirige automáticamente a:

- Éxito: `{APP_URL}/reservar/{codigo}?payment=success&folio=...`
- Cancelación: `{APP_URL}/reservar/{codigo}?payment=cancel`

Asegúrese de que `APP_URL` use **HTTPS** en producción.

## 5. Verificación en servidor

```bash
php artisan hotel:stripe-check
php artisan hotel:preflight
```

`hotel:stripe-check` valida claves y conectividad con la API de Stripe.

## 6. Flujo del huésped

1. Reserva en `/reservar/costa-azul`
2. Botón **Pagar anticipo con tarjeta**
3. Stripe Checkout (hosted)
4. Webhook confirma pago → reserva pasa a `confirmada` + email

## 7. Modo demo (desarrollo)

Sin Stripe:

```env
HOTEL_BOOKING_PAYMENT_PROVIDER=demo
```

Botón «Pagar anticipo (modo demo)» — **no disponible** con `APP_ENV=production`.

## 8. Monitoreo

- Stripe Dashboard → Payments / Logs
- Logs Laravel: `storage/logs/laravel.log`
- Reservas web pendientes de pago: dashboard recepción

## 9. Checklist producción

- [ ] `APP_ENV=production`, `APP_DEBUG=false`
- [ ] HTTPS activo
- [ ] Webhook Live con secret correcto
- [ ] Prueba con tarjeta real o test según entorno
- [ ] Correo SMTP para confirmación al huésped
