# Vertical slice TIBU (prompt §20)

Resumen del estado del proyecto frente a los cinco puntos del prompt y cómo operar migraciones y seeds.

## 1) Esquema de datos

Ya existen en el repositorio (entre otros):

- `areas`, `area_settings`
- `order_area_presences`, `order_area_scan_events` (escaneos por área)
- Catálogos: `garment_types`, `load_types`, `priority_levels`
- Ampliación `orden_lavanderia_detalles` (billing, peso, observaciones, FK a catálogos)

Migraciones y seeds típicos:

```bash
php artisan migrate
php artisan db:seed
```

Seeders relevantes: `AreaSeeder`, `AreaPermissionSeeder`, `PriorityLevelSeeder`, `DatabaseSeeder` (ajustar según entorno).

## 2) Servicio y endpoints HTTP

- **`LaundryWorkflowService`** + **`POST /api/laundry/scans`** (y legado `POST /api/lavanderia/workflow/register-scan`) con `StoreLaundryScanRequest` / `RegisterLaundryScanRequest`.
- Estado por orden:
  - **`GET /api/laundry/orders/{orden}/presence`**
  - **`GET /api/laundry/orders/{orden}/laundry-state`** — mismo JSON que `presence` (alias §20).

Front: `OrderPresenceRepository.getLaundryStateByOrderId` apunta al alias `laundry-state`.

## 3) Permisos por área y vista Vue

- Permisos Spatie `area.{code}.operate` (sembrados con áreas).
- Vista **`/lavanderia/area/:areaCode`** → `resources/js/src/views/lavanderia/area.vue`, con comprobación de permiso en el router.

## 4) POS y ticket

- **`resources/js/src/views/ventas/pos.vue`**: prioridad, envío, líneas con modo de cobro (prenda/peso), observaciones por línea, catálogos vía API POS.
- Ticket **cliente (jsPDF)**: dos páginas idénticas con QR (`ticketPosPdf.js`).
- Ticket **servidor (PDF 80mm)**: `resources/views/pdf/ticket_termico_80mm.blade.php` — lista de observaciones (líneas + instrucciones + detalle ropa) y **dos bloques idénticos de QR** de seguimiento.

## 5) Seguimiento público

- **`GET /api/public/tracking/{folio}`**: payload ampliado con **`priority`** (`code`, `name`) cuando aplica; sin PII (sin nombre de cliente, total, email).
- UI: `resources/js/src/views/public/seguimiento.vue` muestra prioridad si viene en la respuesta.

## Pruebas

```bash
php artisan test
```

Incluye contrato de API lavandería, escaneos, seguimiento público y seguridad de tokens.
