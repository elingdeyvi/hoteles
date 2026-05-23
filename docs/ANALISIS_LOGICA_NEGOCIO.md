# Análisis completo de la lógica de negocio – Control Lavado

Documento para que otra IA (o equipo) analice y adapte el sistema. Incluye backend (Laravel), frontend (Vue 3), roles, permisos, flujos y estructura de datos. No se omite ningún módulo relevante.

**Documentación complementaria (profundidad):** `docs/ESTRUCTURA_DATOS_CRITICA.md`, `docs/ADR-001-pos-catalogo-productos.md`, `config/lavanderia.php`, `.cursorrules`.

---

## 1. Resumen del sistema

**Nombre:** Sistema de Control de Lavado (ControlLavado).

**Propósito:** Gestionar órdenes de lavandería desde recepción hasta entrega: **venta/POS** con precios snapshot. El **camino preferente para líneas nuevas** es el **catálogo POS por producto** (`laundry_sale_categories` / `laundry_sale_products`, ADR-001); conviven **TIBU por matriz servicio × prenda × carga** (`laundry_piece_prices`, etc.) y el **legacy** `productos` para histórico o compatibilidad — **flujo operativo por áreas** (escaneos QR/folio, presencia simultánea en varias áreas cuando aplique), **cabecera legacy** (`current_step`, timestamps por etapa clásica lavado/secado/planchado) que convive con el modelo por áreas, dashboard tipo semáforo, entrega, reportes, administración de usuarios/roles, **inventario de almacén** (insumos, tablas `inventory_*`, separado del ticket de lavandería) y configuración de empresa.

**Identidad de la orden:** Cada venta genera una orden con `folio_unico` y un **`codigo_qr` único** por orden. En recepción puede imprimirse el ticket más de una vez; el **mismo** QR/folio sigue siendo la referencia operativa y de seguimiento.

**Stack:**

- **Backend:** Laravel (API REST), Laravel Sanctum, Spatie Laravel Permission (permisos y roles; muchas rutas de lavandería usan `permission:` en lugar de `role:`).
- **Frontend:** Vue 3, Vue Router, **Vuex** (layout global en `main.js`), stores **Pinia** definidos en `store/*Store.js` donde los consumen vistas/composables, Vite, Bootstrap 5, Axios, i18n.
- **Base de datos:** MySQL o equivalente; migraciones en `database/migrations`.

---

## 2. Backend – Estructura y responsabilidades

### 2.1 Rutas API (`routes/api.php`)

Comentario de cabecera del archivo: la mayoría de rutas van bajo `auth:sanctum`; las públicas llevan throttle donde aplica.

#### Públicas (sin `auth:sanctum`)

| Método y ruta | Uso |
|---------------|-----|
| `POST /api/registro` | Registro de usuario |
| `GET /api/configuracion-empresa/public` | Configuración pública (logo, favicon, bienvenida, etc.) |
| Grupo `middleware('throttle:public-tracking')` | |
| `GET /api/public/tracking/{folio}` | Seguimiento público agregado (estado, presencia, envío, últimos escaneos; sin datos personales identificables) |
| `GET /api/public/orden-estatus/{folio}` | Estatus de orden por folio (compatibilidad / QR simple) |
| Grupo `middleware('dev.setup')` | `GET /api/storage-link`, `GET /api/generate-key` (solo entorno con middleware configurado) |
| Prefijo `/api/tokens` | `POST /create` (login, throttle `login`), `POST /pw`, `POST /savepw`, `POST /getpwuuid` |
| `GET /api/configuracion-empresa/` | Índice configuración (sin Sanctum en el prefijo mostrado; revisar si debe protegerse en despliegues) |

#### Protegidas (`auth:sanctum`)

**Tokens:** `POST /api/tokens/logout`, `DELETE /api/tokens/{token}`, `GET /api/tokens/permissions` (permisos y roles; Administrador → `all: true`).

**Usuarios:** CRUD y utilidades heredadas (`mediciones`, `sucursales`, `terapista`, `revision`, `setconfig`, etc.). **`GET /api/users`** devuelve el usuario autenticado con `roles` y relación **`area`** (asignación de estación para escaneo).

**Roles:** `GET /api/roles/all`, `GET /api/roles/permissions`, `PUT /api/roles/{role}/permissions`.

**Administración TIBU (áreas, tipos de prenda y catálogo POS de venta):**

- `GET|POST /api/admin/areas`, `PUT /api/admin/areas/{area}`, `PATCH /api/admin/areas/{area}/disable` → `permission:administracion.areas`.
- `GET|POST /api/admin/garment-types`, `PUT /api/admin/garment-types/{garmentType}`, `PATCH /api/admin/garment-types/{garmentType}/disable` → mismo permiso (iteración D5).
- `GET|POST /api/admin/laundry-sale-categories`, `PUT …`, `PATCH …/disable` y análogo **`/api/admin/laundry-sale-products`** → mismo permiso `administracion.areas` (ADR-001: categorías y productos vendibles en recepción).

**Configuración empresa (mutaciones):** `PUT /api/configuracion-empresa/update`, uploads y deletes de logo/favicon/icono mensaje.

**Productos legacy (catálogo unificado `productos`):** CRUD bajo `permission:pos.vender` / `caja.administrar` para lectura según ruta. Con `LAVANDERIA_PRODUCTOS_LEGACY_FROZEN=true` (por defecto en `config/lavanderia.php`) las **altas** nuevas vía API pueden rechazarse como defensa en profundidad.

**Catálogos D4 (lectura):** `GET /api/catalogos/servicios`, `GET /api/catalogos/productos-legacy` → `pos.vender|caja.administrar`.

**Clientes:** CRUD; lectura también con `caja.administrar`; `GET /api/clientes/mostrador` para POS.

**Lavandería – POS catálogo (prioridad, prendas TIBU, matriz C2, catálogo POS por producto):**

- `GET /api/lavanderia/pos/catalog` → `permission:pos.vender` (alias histórico).
- `GET /api/laundry/pos-catalog` y `POST /api/laundry/pos/preview-totals` → `permission:pos.vender` (canónico C6; JSON `data` incluye `laundry_sale_categories`, `laundry_sale_products` además de `garment_types`, `laundry_piece_prices`, etc.; preview con mismas reglas que `POST /lavanderia/ordenes` → `items`).

**Lavandería – Órdenes** (`/api/lavanderia/ordenes`):

| Ruta | Middleware principal |
|------|-------------------------|
| `GET /export-csv` | `permission:reportes.ver` (límite de filas en `config/lavanderia.php` → `export_csv_max_rows`; columnas incluyen `laundry_sale_lines_count` = cantidad de líneas con `laundry_sale_product_id`) |
| `GET /` | `permission:pos.vender|lavanderia.consultar` |
| `POST /` | `permission:pos.vender`, **`caja.abierta`** |
| `POST /escanear-qr` | `permission:lavanderia.operador` |
| `POST /{orden}/avanzar` | `permission:lavanderia.operador` (flujo legacy por `current_step`) |
| `POST /{orden}/entregar` | `permission:lavanderia.recepcion` |
| `POST /{orden}/cancelar` | `permission:lavanderia.recepcion` |
| `GET /dashboard/resumen` | `permission:lavanderia.dashboard` (tamaño acotado por `dashboard_max_orders`) |
| `GET /{orden}/ticket-pdf` | `lavanderia.consultar|operador|recepcion` |
| `GET /{orden}` | idem |

**Flujo TIBU – escaneo por área:**

- `POST /api/lavanderia/workflow/register-scan` → `ensure.area.operate`, `throttle:laundry-scans`.
- `POST /api/laundry/scans` → mismo middleware y throttle (contrato paralelo bajo prefijo `/laundry`).

**Lavandería – API auxiliar** (`/api/laundry`):

- `GET /areas` → listado operativo para UI → `lavanderia.consultar|operador|recepcion`.
- `GET /orders/{orden}/presence`, `GET /orders/{orden}/laundry-state` → `pos.vender|lavanderia.consultar|lavanderia.operador`.

**Caja:** `GET /estado`, `POST /abrir`, `GET|POST /movimientos`, `POST /cortar` → `permission:caja.administrar`.

**Ventas:** `GET /api/ventas/mensual` → `permission:reportes.ver` (órdenes entregadas por año/mes; cada fila incluye **`lineas_catalogo_pos`** = suma de líneas de detalle con `laundry_sale_product_id` en ese mes; el CSV `export-csv` sigue trayendo **`laundry_sale_lines_count`** por orden).

**Inventario almacén** (`/api/inventory/*`, tablas `inventory_*`): catálogo de referencia (`GET /api/inventory/catalog`, incluye `areas` activas para destinos en documentos), productos CRUD, movimientos atómicos por producto, ajuste masivo CSV, y **documentos agregados** (`GET|POST /api/inventory/transfer-documents`, `GET|PUT .../transfer-documents/{id}`, `POST .../confirm`, `POST .../cancel-draft`) — recepción `inbound_receipt` (movimientos `in`, referencia `receipt:`), entrega interna y salida documentada `internal_delivery` / `outbound` (movimientos `out`, referencia `transfer:`). Ver **`docs/ADR-002-inventario-entrega-salida.md`**. Permisos `inventory.view` (lectura) / `inventory.manage` (mutación) según ruta. **No** sustituye el armado de líneas del POS de lavandería.

---

### 2.2 Autenticación y autorización

- **Guard API:** Sanctum. Header `Authorization: Bearer {token}`; el frontend suele guardar el token en `localStorage` como `token`.
- **Login:** `POST /api/tokens/create` con `email` y `password`; usuarios con `estatus = activo`.
- **Roles:** Administrador, Recepcionista, Operador (nombres canónicos en código y seeders).
- **Autorización:** además de Spatie, **Policies** en órdenes (`OrdenLavanderiaPolicy`), middleware **`ensure.area.operate`** (`EnsureAreaOperatePermission`) que exige permiso `area.{code}.operate` (o rol Administrador, o fallback `lavanderia.operador` si el permiso por área aún no existe en BD) y delega la **coincidencia de estación** en `UserAreaAssignment` dentro de `LaundryWorkflowService`.
- **Asignación física:** `users.area_id` nullable, FK a `areas`. El operador debe escanear en el área asignada salvo **Administrador**.

---

### 2.3 Permisos (Spatie) – visión ampliada

Además de los permisos históricos (`dashboard.ver`, `administracion.usuarios`, `administracion.roles`, `lavanderia.*`, `pos.vender`, `caja.administrar`, `reportes.ver`), el sistema incorpora:

| Permiso | Uso |
|---------|-----|
| `administracion.areas` | CRUD administrativo de áreas TIBU, tipos de prenda y **catálogo POS** (`/admin/areas`, `/admin/garment-types`, `/admin/laundry-pos-catalog`) |
| `inventory.view` / `inventory.manage` | Módulo inventario almacén |
| `area.{code}.operate` / `area.{code}.view` | Operación o consulta por código de área estable (seed dinámico según filas en `areas`) |

Los roles concretos y la asignación exacta dependen de migraciones/seeders (`AreaPermissionSeeder`, etc.). El endpoint `GET /api/tokens/permissions` alimenta el frontend (`use-permissions.js`).

---

### 2.4 Modelos principales (ampliado)

- **User:** `area_id` (estación de planta). Relación `area()`. **`ordenesLavanderia()`** en código usa FK `usuario_recepcionista_id`, pero en BD la columna es **`recepcionista_id`** → la relación no coincide con el esquema actual; para auditoría de recepción usar `OrdenLavanderia::recepcionista()` / `recepcionista_id`.
- **Area:** `code`, `name`, `sort_order`, `is_active`, `floor_label`, `requires_scan_twice`; relación `settings()` → **AreaSetting** (`is_enabled` para considerar el área en flujo operativo; scope típico `operational()` en consultas).
- **OrderAreaScanEvent:** historial de escaneos por orden y área; `scan_sequence` 1 o 2 (ciclos alternos por orden+área).
- **OrderAreaPresence:** una fila por par orden+área; estados inicio/completado con timestamps y usuario.
- **OrdenLavanderia:** además de lo ya documentado, `priority_level_id`; relaciones `priorityLevel()`, `shippingOrder()`, `areaPresences()`, `areaScanEvents()`.
- **OrdenLavanderiaDetalle:** modelo mixto: (1) **legacy** con `producto_id` → `productos`; (2) **TIBU** sin `producto_id` ni `laundry_sale_product_id` → `service_type_id`, `garment_type_id`, `load_type_id`, `billing_mode`, `weight_kg`, `observations`; (3) **catálogo POS** con **`laundry_sale_product_id`** → `laundry_sale_products` (sin `garment_type_id` en la misma línea). Snapshots de importe en `precio_unitario` / `subtotal`.
- **LaundrySaleCategory, LaundrySaleProduct:** catálogo de venta en recepción (ADR-001); precio vigente en `unit_price`; `billing_unit` `piece` | `kg`; FK opcional `inventory_product_id` para futuro acoplamiento almacén.
- **ShippingOrder:** domicilio asociado a la orden (`is_delivery`, dirección, estados de entrega); creado/actualizado desde el servicio al crear orden con flags de envío.
- **ServiceType, GarmentType, LoadType, PriorityLevel:** catálogos TIBU (matriz C2 y defaults de prenda; no son el catálogo POS `laundry_sale_*`).
- **LaundryPiecePrice, PricingRule:** matriz y reglas de precio (C2) para líneas TIBU; los importes quedan en la línea.
- **InventoryProduct** y relacionados: dominio almacén (prefijo `inventory_` en tablas según migraciones).
- **InventoryTransferDocument**, **InventoryTransferLine:** documento borrador → confirmar → movimientos `inventory_movements` vía **`InventoryTransferDocumentService`** (transacción + `InventoryMovementService::recordMovementWithoutTransaction`); estados `draft` / `confirmed` / `cancelled`; cabecera opcional `supplier_id`, `supplier_reference`; destino `destination_area_id` o `recipient_user_id` obligatorio al confirmar solo si `transfer_type = internal_delivery`.

Modelos ya descritos en la versión anterior (ConfiguracionEmpresa, Caja, CajaMovimiento, Cliente, Producto, OrdenEstadoLog, NotificacionLog) siguen vigentes; convenciones de columnas de caja (`saldo_inicial`, `saldo_final`, `abierto_at`, `cerrado_at`, etc.) se mantienen.

---

### 2.5 Controladores y servicios de negocio

- **OrdenLavanderiaController:** creación con carrito valida ítems **TIBU**, **catálogo POS** (`laundry_sale_product_id`) y **legacy** (`service_type_id`, `garment_type_id`, `billing_mode`, `priority_level_id`, envío, etc.); `show` carga detalles con `producto`, `serviceType`, `garmentType`, **`laundrySaleProduct`**, `loadType`, `priorityLevel`, `shippingOrder`.
- **OrdenLavanderiaService:** `crearOrden`, `crearOrdenDesdeCarrito` (precios vía **LaundryPricingService**: TIBU / matriz C2, catálogo POS `previewSaleCatalogLineMoney`, legacy `producto_id`; stock solo en productos físicos legacy), `createShippingOrderForOrden`, `avanzarStep`, `marcarEntregada`, `cancelarOrden` (stock + movimiento de caja si aplica).
- **LaundryWorkflowService:** `registerScan` con bloqueo por orden+área, validación de permiso y **UserAreaAssignment**, resolución de orden por folio o payload QR (base64 JSON con `folio`), secuencia de escaneos vía **OrderAreaScanEventService**, sincronización de **OrderAreaPresence**.
- **LaundryApiController:** áreas operativas, presencia, `laundry-state` para UI.
- **LaundryWorkflowController:** `registerScan`, `storeScan`.
- **LavanderiaPosCatalogController:** catálogo POS (TIBU + `laundry_sale_*`) + **previewTotals** (`LaundryPosPreviewTotalsRequest` / `LaundryPosPreviewTotalsService`).
- **AreaAdminController**, **GarmentTypeAdminController**, **LaundrySaleCategoryAdminController**, **LaundrySaleProductAdminController**, **InventoryCatalogController**, **InventoryProductController**, **InventoryMovementController**, **InventoryBulkAdjustCsvController**, **InventoryTransferDocumentController**: administración e inventario (incl. documentos ADR-002).
- **PublicTrackingController** + **LaundryTrackingPresentationService:** respuesta pública enriquecida para `/public/tracking/{folio}`.
- **ImpresionInstruccionesLavanderiaService**, **VentasController**, **CajaController**, **ProductoController**, **ClienteController**, **TokenController**, **ConfiguracionEmpresaController**, **RoleController**, **UserController:** mismos roles generales descritos antes, con extensiones TIBU donde aplica.

---

### 2.6 Eventos y listeners (correo)

Sin cambio sustancial de la idea de negocio: **OrdenLavanderiaCreada**, **OrdenLavanderiaTerminada** con listeners en cola y **NotificacionLog**.

---

### 2.7 Repositorio e inyección

**OrdenLavanderiaRepositoryInterface** → **EloquentOrdenLavanderiaRepository**; filtros ampliados en el modelo (`HistorialFilters`, fechas, `priority_level_id`, `caja_id`, etc.).

---

### 2.8 Migraciones y datos (orientación)

Orden sugerido: usuarios y Spatie → configuración empresa → órdenes y logs → POS/caja/clientes/productos → **áreas** (`areas`, `area_settings`) → **presencia y escaneos** → **catálogos TIBU** (service_types, garment_types, load_types, piece prices, pricing_rules, priority) → **shipping_orders** → **inventory_*** → permisos `administracion.areas`, `inventory.*`, permisos dinámicos `area.*` → **`users.area_id`**.

Comandos y claves de configuración relevantes: `config/lavanderia.php` (`dashboard_max_orders`, `export_csv_max_rows`, `tracking_recent_scans_limit`, `productos_legacy_frozen`, mapeo de backfill `backfill_step_to_area_code`, flags de seed, `weight_price_depends_on_load_type`).

---

### 2.9 Flujo operativo TIBU (áreas, escaneos, presencia)

1. El operador envía **folio o QR** + **`area_code`** (cuerpo JSON) a `register-scan` o `/laundry/scans`.
2. El backend resuelve el **área operativa** (`areas` + `area_settings.is_enabled`), comprueba permisos (**AreaPermission** + middleware), valida **asignación** `users.area_id` ↔ área (salvo Administrador).
3. Se registra **OrderAreaScanEvent** con secuencia 1 o 2 alternada por orden+área (reglas en **OrderAreaScanEventService**); la **presencia** (**OrderAreaPresence**) pasa a en curso en la secuencia 1 y a completada en la secuencia 2 en el flujo estándar de sincronización.
4. El campo **`requires_scan_twice`** en `areas` informa a la UI si el área está pensada para doble lectura; la alternancia 1/2 del backend define ciclos de escaneo por orden y área.
5. Órdenes **terminadas, entregadas o canceladas** no admiten nuevos escaneos (respuesta 422).
6. Convive con **`current_step`** y **`POST .../avanzar`** para operación legacy o transición; no sustituir sin plan de migración de datos.

---

## 3. Frontend – Estructura y responsabilidades

### 3.1 Stack y entrada

- **Entrada:** `resources/js/src/main.js` monta la app con **Vuex** (`store`), Router, i18n y plugins existentes. Existen stores **Pinia** (`AuthStore`, `ConfiguracionEmpresaStore`, `StateStore`) para módulos que los importan explícitamente.
- **Router:** `resources/js/src/router/index.js` — `createWebHistory()`.

### 3.2 Router – rutas relevantes

- `/`, `/dashboard` y `/lavanderia/dashboard` → semáforo; meta `permission: ['dashboard.ver', 'lavanderia.dashboard']`.
- **POS:** `/ventas/pos` → `pos.vender`; `/ventas/caja` → `caja.administrar`; `/ventas/tickets` → `pos.vender` o permisos lavandería recepción/consulta.
- **Catálogos:** `/catalogos/clientes`; `/catalogos/servicios`; `/catalogos/productos-legacy`; redirect de `/catalogos/productos` al legacy.
- **Inventario:** `/inventario/productos` → `inventory.view|inventory.manage`; **`/inventario/documentos`** (listado), **`/inventario/documentos/crear`** → solo `inventory.manage`; **`/inventario/documentos/:id`** (detalle / edición borrador) → `inventory.view|inventory.manage`.
- **Lavandería:** `/lavanderia/recepcion` → redirect a POS; `/lavanderia/operador/entrada`, `/lavanderia/operador/:area?`, `/lavanderia/escanear` → `lavanderia.operador`; **`/lavanderia/area/:areaCode`** → meta **`requireAreaOperate`** (comprueba `area.{areaCode}.operate`); `/lavanderia/lista` → consulta/recepción.
- **Reportes:** `/reportes/cierre-dia`, `/reportes/ventas-producto`, `/ventas/mensual` → `reportes.ver` o, en mensual, `['dashboard.ver', 'reportes.ver']` (alinear con backend si se desea un solo permiso).
- **Admin:** `/admin/configuracion-empresa`, `/admin/areas`, `/admin/garment-types`, `/admin/laundry-pos-catalog` (catálogo POS por producto/categoría, `administracion.areas`).
- **Público:** `/seguimiento/:folio_unico?` → `meta.public: true`, layout auth.

**Guard global:** token, permisos por `meta.permission`, rutas por área con `meta.requireAreaOperate`, redirección inteligente desde Home según permisos (dashboard, POS, caja, inventario, primera área con `area.*.operate`, operador, lista, perfil).

### 3.3 Permisos en frontend

Composable `use-permissions.js` → `GET /api/tokens/permissions`; `hasPermission`, `hasAnyPermission`; Administrador → `all`.

### 3.4 Menú lateral y header

Incluyen **Punto de venta**, **Catálogos** (servicios y productos legacy), **Inventario** (productos almacén y **documentos** entrada/salida/entrega según permisos), **Reportes**, **Lavandería** (dashboard, operador, escanear, lista), **Administración** (usuarios, roles, áreas, tipos de prenda, **catálogo POS**, configuración empresa según permisos). Condicionados con `can()` / mismos nombres de permiso que el backend.

### 3.5 Repositorios (frontend)

Además de Auth, OrdenLavanderia, Producto, Cliente, Caja, ConfiguracionEmpresa: **LaundryScanRepository**, **AreaRepository**, **OrderPresenceRepository**, **AreasAdminRepository**, **GarmentTypesAdminRepository**, **LaundryPosCatalogAdminRepository** (CRUD `laundry_sale_*`), **CatalogosServiciosRepository**, **CatalogosProductosLegacyRepository**, **InventoryProductRepository**, **InventoryCatalogRepository**, **InventoryTransferDocumentsRepository**, **PublicTrackingRepository**, **VentasRepository**, etc.

### 3.6 Vistas principales

- **Lavandería:** `dashboard`, `recepcion` (redirect), `operador`, `operador-entry`, `escanear-unificado`, **`area.vue`** (escaneo por código de área), `lista`.
- **Ventas:** POS (líneas TIBU + catálogo POS `laundry_sale_products`), caja, tickets, mensuales.
- **Inventario:** `inventory/products.vue`, **`inventory/transfer-documents.vue`**, **`inventory/transfer-document-detail.vue`** (alta y detalle/confirmación de documentos ADR-002).
- **Admin:** `areas.vue`, `garment-types.vue`, `laundry-pos-catalog.vue`, `configuracion-empresa.vue`.
- **Público:** `seguimiento.vue` (puede consumir tracking público o estatus según implementación).

### 3.7 Layouts

`app-layout` y `auth-layout` sin cambio conceptual.

---

## 4. Flujos de negocio resumidos

1. **Login:** email/password → token Sanctum → almacenamiento local → redirección según permisos.
2. **Venta POS:** caja abierta + `pos.vender` → ítems **TIBU** (servicio/prenda/carga), **catálogo POS** (`laundry_sale_product_id`) y/o **legacy** `producto_id` (no combinar tipos en la misma línea) → preview (`/laundry/pos/preview-totals`) alineado con totales persistidos → `POST /lavanderia/ordenes` → folio, QR único, líneas con snapshot, **ShippingOrder** si hay domicilio, evento de orden creada.
3. **Operación por áreas (TIBU):** operador en vista de área o escaneo unificado → `register-scan` o `/laundry/scans` con `area_code` → eventos y presencia; misma orden puede tener presencia en **varias** áreas.
4. **Operación legacy:** escanear QR para localizar orden → **avanzar** por `current_step` hasta terminado; correos al terminar.
5. **Entrega / cancelación:** mismas reglas de negocio (cancelación: stock legacy, movimiento de caja si aplica, motivo obligatorio).
6. **Reportes / export:** ventas mensuales; CSV de órdenes con techo configurable y conteo de líneas con producto POS (`laundry_sale_lines_count`).
7. **Seguimiento cliente:** URL pública con folio; APIs `public/tracking` y `public/orden-estatus`.
8. **Inventario almacén:** movimientos atómicos y stock en dominio `inventory_*`; **documentos** (recepción / entrega interna / salida) con confirmación masiva y trazabilidad por folio y `reference` en movimientos — independiente del ticket de lavandería (`docs/ADR-002-inventario-entrega-salida.md`). QA manual: `docs/CHECKLIST_QA_INVENTARIO_DOCUMENTOS_ENTREGA_SALIDA.md`.

---

## 5. Roles y permisos – Resumen para adaptación

| Rol | Uso típico |
|-----|------------|
| Administrador | `all: true` en permisos; omite restricción de `area_id` en escaneo |
| Recepcionista | POS, caja, consulta, entrega, dashboard, reportes según seed |
| Operador | Dashboard, escaneo; permisos por área `area.*.operate` y `users.area_id` en planta |

La matriz exacta permiso ↔ rol debe leerse de migraciones/seeders y de la BD en producción.

---

## 6. Base de datos – Tablas y relaciones (ampliado)

Además de las tablas ya listadas en versiones anteriores (`users`, Spatie, `ordenes_lavanderia`, `orden_lavanderia_detalles`, `cajas`, `caja_movimientos`, `clientes`, `productos`, `orden_estado_log`, `notificaciones_log`, `configuracion_empresa`, `personal_access_tokens`):

- **`areas`**, **`area_settings`**, **`order_area_scan_events`**, **`order_area_presences`**
- **`users.area_id`** (FK a `areas`)
- Catálogos TIBU: p. ej. **`service_types`**, **`garment_types`**, **`load_types`**, **`priority_levels`**, **`laundry_piece_prices`**, **`pricing_rules`**
- **Catálogo POS de venta (canónico líneas nuevas por producto):** **`laundry_sale_categories`**, **`laundry_sale_products`**; en **`orden_lavanderia_detalles`** la columna **`laundry_sale_product_id`** (nullable, FK). Histórico y TIBU siguen usando `garment_type_id` donde aplique.
- **`shipping_orders`**
- Inventario: tablas con prefijo / nombre **`inventory_*`** según migraciones del módulo; entre ellas **`inventory_transfer_documents`** y **`inventory_transfer_lines`** (documentos ADR-002), además de **`inventory_movements`** con tipos `in` | `out` | `adjustment`.

#### 6.1 Canónico vs legacy (qué tabla manda en cada tipo de línea)

| Rol | Tablas / columnas | Uso |
|-----|-------------------|-----|
| **Canónico — venta por producto POS** | `laundry_sale_categories`, `laundry_sale_products`; en línea `laundry_sale_product_id` (no combinar con `garment_type_id` en la misma fila) | Precio vigente en `laundry_sale_products.unit_price`; importes quedan en snapshot en `orden_lavanderia_detalles`. |
| **Canónico — venta TIBU (matriz C2)** | `service_types`, `garment_types`, `load_types`, `laundry_piece_prices`, `pricing_rules` | Líneas **sin** `laundry_sale_product_id` y **sin** `producto_id`; precio desde matriz + reglas kg al crear la orden. |
| **Legacy — catálogo unificado** | `productos`; en línea `producto_id` | Convive por compatibilidad; con `LAVANDERIA_PRODUCTOS_LEGACY_FROZEN=true` el alta de **nuevos** `productos` vía API puede estar bloqueada (defensa en profundidad). |
| **Lectura / histórico** | Filas ya guardadas en `ordenes_lavanderia` y `orden_lavanderia_detalles` | Tickets y reportes muestran lo persistido; **no** se recalculan totales al cambiar el catálogo después de la venta. |

---

## 7. Notas para otra IA o equipo que adapte el sistema

- **Un solo QR/folio** por orden; impresión múltiple no genera nuevos códigos.
- **No mezclar inventario** `inventory_*` con líneas del ticket de lavandería; el POS usa `/laundry/pos-catalog` (TIBU + **`laundry_sale_*`**). Precios: **C2** en líneas TIBU; **`unit_price`** del producto POS en líneas `laundry_sale_product_id`. Ver **`docs/ADR-001-pos-catalogo-productos.md`**.
- **Áreas:** usar siempre `code` estable, no nombres hardcodeados en lógica nueva; permisos `area.{code}.operate` deben existir en BD para granularidad plena (hasta entonces aplica fallback `lavanderia.operador`).
- **User::ordenesLavanderia()** vs columna **`recepcionista_id`:** corregir relación si se necesita listar órdenes por recepcionista desde User.
- **Variable de entorno:** `VITE_API_URL` para base del API.
- **Referencias:** `docs/ESTRUCTURA_DATOS_CRITICA.md`, `docs/ADR-001-pos-catalogo-productos.md`, **`docs/ADR-002-inventario-entrega-salida.md`**, **`docs/CHECKLIST_QA_INVENTARIO_DOCUMENTOS_ENTREGA_SALIDA.md`**, `docs/PROMPTS_MODULOS_ENTREGA_SALIDA_INVENTARIO.md`, `config/lavanderia.php`, `.cursorrules`.

---

## 8. Verificación: estado del documento (abril 2026)

Este documento fue **reescrito para alinear** con el código y rutas actuales:

- Añadido **flujo TIBU por áreas** (middleware `ensure.area.operate`, `LaundryWorkflowService`, presencia y eventos de escaneo, `users.area_id`).
- Sustituido el enfoque “solo `role:` en lavandería” por el modelo real basado en **`permission:`** en `api.php` para órdenes, dashboard, export y operador.
- Documentados prefijos **`/api/laundry/*`**, **`/api/inventory/*`**, **admin áreas / garment-types**, **catálogos D4**, **preview totals**, **export CSV**, **caja movimientos**.
- **Seguimiento público:** `GET /api/public/tracking/{folio}` además de `orden-estatus`.
- **Frontend:** rutas `lavanderia/area/:areaCode`, inventario (productos y **documentos de almacén**), catálogo servicios/productos legacy, guard `requireAreaOperate`, redirección Home por permisos.
- **Modelo de líneas** TIBU + **catálogo POS** (`laundry_sale_product_id`) + legacy en `orden_lavanderia_detalles` y **ShippingOrder**.
- **ADR-001 / fases 5 y 7:** documentación alineada con catálogo POS propio (`laundry_sale_*`), admin y POS Vue; CSV historial con `laundry_sale_lines_count`. **Fase 7:** §6.1 explicita tablas **canónicas vs legacy** y el POS por producto como camino preferente frente a la matriz TIBU.
- **Fase 8:** `GET /api/ventas/mensual` incluye **`lineas_catalogo_pos`** por mes; suite C8 incluye `VentasMensualCatalogoPosTest`.
- Convenciones de **caja** y **cancelación** de la versión anterior se **mantienen** salvo que el código evolucione.

Si detectas divergencia puntual entre este archivo y `routes/api.php` o los modelos, prima el código y actualiza esta sección en el mismo PR.
