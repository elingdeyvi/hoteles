# Mapa de módulos — MVP hotel

## Rutas web (Vue)

| Ruta | Módulo | Permiso |
|------|--------|---------|
| `/dashboard` | Panel KPIs | `dashboard.ver` |
| `/hotel/config/propiedades` | Hoteles (multi-propiedad) | `hotel.configurar` |
| `/hotel/config/habitaciones` | Habitaciones | `hotel.configurar` |
| `/hotel/config/tarifas` | Tarifas | `hotel.configurar` |
| `/hotel/recepcion/huespedes` | Huéspedes | `recepcion.huespedes` |
| `/hotel/recepcion/reservas` | Reservaciones | `recepcion.reservas` |
| `/hotel/recepcion/planning` | Calendario | `recepcion.reservas` |
| `/hotel/recepcion/checkin/:id` | Check-in | `recepcion.checkin` |
| `/hotel/pos` | POS consumos | `pos.vender` |
| `/hotel/pos/catalogo` | Catálogo POS | `pos.catalogo` |
| `/hotel/facturacion/folios` | Folios | `facturacion.folios` |
| `/hotel/housekeeping` | Limpieza | `housekeeping.gestionar` |
| `/hotel/reportes` | Reportes | `reportes.ver` |
| `/reservar` | Booking público | (sin auth) |
| `POST /api/booking/payments/checkout` | Anticipo Stripe | (sin auth) |
| `POST /api/booking/payments/demo-confirm` | Anticipo demo (solo dev) | (sin auth) |
| `POST /api/booking/webhooks/stripe` | Webhook Stripe | (sin auth) |
| `/users/lista` | Usuarios | `administracion.usuarios` |
| `/roles/lista` | Roles | `administracion.roles` |

## API principales

| Prefijo | Uso |
|---------|-----|
| `GET /api/health` | Monitoreo |
| `GET /api/booking/{code}/config` | Config por hotel |
| `POST /api/booking/{code}/reservations` | Crear reserva web |
| `GET /api/hotel/properties` | Listar hoteles (auth) |
| `GET/POST /api/hotel/*` | Operación (Sanctum) |
| `GET /api/dashboard/resumen` | Dashboard |

## Servicios de dominio

- `HotelPricingService` — tarifas y totales
- `ReservationService` — disponibilidad y folios de reserva
- `OnlineBookingService` — motor web + confirmación
- `BookingPaymentService` — anticipo en línea (demo / Stripe)
- `FolioService` — cargos, pagos, saldo
- `PosSaleService` — venta POS a folio
- `PosReportService` — reportes POS
- `ReservationNotificationService` — correos web

## Roles

| Rol | Acceso típico |
|-----|----------------|
| Administrador | Todo |
| Recepcionista | Recepción, folios, reportes, POS |
| Housekeeping | Tablero habitaciones |
| Cajero | Solo POS |
