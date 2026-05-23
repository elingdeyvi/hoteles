# Prompts — Inventario almacén: **entradas**, **salidas**, **entregas** (documentos + vista profesional)

Documento para coordinar **agentes en Cursor** al diseñar e implementar módulos sobre el dominio **`inventory_*`** (insumos / almacén): **recepción de mercancía (entradas IN)**, **salidas documentadas (OUT)**, **entregas internas a planta (OUT documentado con destino)**. **Sin mezclar** con el ticket de lavandería ni con `POST /api/lavanderia/ordenes` salvo ADR + instrucción explícita del humano.

**Contexto ya implementado en el repo (no reinventar sin motivo):**

- Movimientos por producto: `GET|POST /api/inventory/products/{inventory_product}/movements` → `InventoryMovementController` + `InventoryMovementService::record` (+ `recordMovementWithoutTransaction` para transacciones agregadas).
- Tipos vigentes en modelo: `InventoryMovement::TYPE_IN`, `TYPE_OUT`, `TYPE_ADJUSTMENT`.
- Documentos de transferencia/salida (ADR-002, fases 1–2 en código): tablas `inventory_transfer_documents` / `inventory_transfer_lines`, servicio `InventoryTransferDocumentService` (confirmar/cancelar borrador).
- Permisos: `inventory.view`, `inventory.manage` (`routes/api.php`, `/api/inventory`).
- Stock en `inventory_products.stock_quantity`; aritmética `bcmath`.

**Lectura previa:** `docs/ANALISIS_LOGICA_NEGOCIO.md` (inventario), `docs/GAP_ANALYSIS_LAVANDERIA.md` §2, `.cursorrules`, vista existente `resources/js/src/views/inventory/products.vue` (patrón layout/paneles).

**ADR:** [`docs/ADR-002-inventario-entrega-salida.md`](ADR-002-inventario-entrega-salida.md) — **v2:** `inbound_receipt` | `internal_delivery` | `outbound`; referencias `receipt:` / `transfer:`; columnas `supplier_id` / `supplier_reference` en cabecera (**fase 1b** migración).

---

## 0. Qué debe aclarar el humano antes del primer prompt

| Pregunta | Opciones típicas |
|----------|------------------|
| **“Entrada”** ¿qué documenta? | **Recepción a almacén** (compra, donación, devolución de campo a central); confirma con movimientos `type=in`. Opcional: número de factura/remisión, proveedor (`inventory_suppliers`), notas. |
| **“Entrega”** ¿a qué se refiere? | (A) **Entrega de insumos** almacén → área/usuario planta (`internal_delivery`). (B) **Despacho a tercero** (`outbound` + notas). (C) **Entrega orden lavandería al cliente** → **no** es este módulo; usar `ordenes_lavanderia`. |
| **“Salida”** | Atómica actual `POST .../movements` **out** + **documento agregado** (borrador → líneas → confirmar) ya alineado en ADR-002. |
| ¿Descuento automático por orden/etapa de lavado? | **Fuera** de este documento; ADR y reglas aparte. |

---

## 0.1 Criterios de **vista profesional** (exigibles en fase Frontend)

El agente **debe** tratar la UI de inventario al mismo estándar que el resto de la app (Bootstrap 5, paneles, tablas). Checklist mínima:

- **Layout:** `layout-px-spacing`, breadcrumb vía `<teleport to="#breadcrumb">`, títulos claros, espaciado consistente con `inventory/products.vue`.
- **Listados:** tabla responsive (`table-responsive`), cabeceras fijas visibles, paginación o “cargar más” si el API pagina; **empty state** ilustrado o con mensaje accionable (“Crear primera recepción”).
- **Estados:** badges de color para `draft` / `confirmed` / `cancelled` (y equivalentes de entrada); no solo texto plano.
- **Formularios:** labels asociados (`for`/`id`), validación en vivo donde sea trivial, deshabilitar botón principal durante `loading`, mensajes de error bajo el campo o toast coherente con el proyecto.
- **Acciones:** botón primario único por pantalla (p. ej. “Confirmar documento”); secundarios outline; **modal de confirmación** antes de confirmar o cancelar documento con impacto en stock.
- **Detalle documento:** cabecera en card resumen (folio, tipo, estado, fechas, usuario); líneas en tabla con SKU/nombre producto (desde relación o API); totales por cantidad si aplica.
- **i18n:** todas las cadenas nuevas en `resources/js/src/locales/*.json`; claves bajo prefijo acordado p. ej. `inventory.transfer_documents.*` y `inventory.receipts.*`.
- **Accesibilidad básica:** contraste suficiente en badges, `aria-label` en icon-only, foco manejable en modales.
- **Impresión (opcional v1.1):** vista imprimible o PDF del documento confirmado (folio + líneas); si no hay tiempo, dejar TODO en código comentado + i18n “Próximamente”.

---

## 1. Libertad operativa del agente (igual criterio que migración POS)

- Puede tocar **backend + front + tests + docs** si el humano pega **§4 B** o indica “libertad total”.
- **Invariantes:** no romper API actual de movimientos salvo versión/deprecación documentada; transacciones y bloqueo pesimista como `InventoryMovementService`; permisos Spatie coherentes con el resto del módulo inventario.

**Git (por defecto):** una sola rama acordada (`main` u otra); `git pull` al inicio; no abrir rama nueva por cada sub‑módulo salvo instrucción del humano.

---

## 2. Prompt maestro (pegar al abrir hilo nuevo)

### §4 — Modo por fases / contexto compartido

```text
Estás en el repo ControlLavado (Laravel 10+, Vue 3, Sanctum, Spatie Permission).

Misión: diseñar e implementar **entradas (recepción IN)**, **salidas documentadas (OUT)** y **entregas internas** en inventario (`inventory_*`), según §0 y el ADR-002 (y su ampliación para entradas). Incluir **vista profesional** en frontend según §0.1.

Git: trabaja solo en la rama acordada (p. ej. main); git checkout + git pull al inicio; no crees ramas paralelas sin que el humano lo pida.

Restricciones:
- No acoplar inventario al ticket POS de lavandería sin ADR y sin instrucción explícita del humano.
- Reutiliza patrones existentes: InventoryMovementService, FormRequest, Policies, repositorios JS, i18n.
- Toda mutación de stock en transacción con lockForUpdate sobre inventory_products, igual que hoy.

Lee: docs/ANALISIS_LOGICA_NEGOCIO.md (inventario), app/Services/InventoryMovementService.php, app/Http/Controllers/InventoryMovementController.php, routes/api.php (/api/inventory).

Indica archivos a tocar y ejecuta tests al final (php artisan test filtrando por los archivos nuevos o suite Feature de inventario).
```

### §4 B — Libertad total (un agente, punta a punta)

```text
Estás en ControlLavado (Laravel + Vue 3 + Sanctum + Spatie).

MANDATO: LIBERTAD TOTAL para **entradas, salidas y entregas** de inventario (según §0), incluyendo ampliación de ADR si hace falta, migraciones, servicios, API, **Vue con UI profesional (§0.1)**, router/sidebar, i18n, tests y docs.

Reutiliza `InventoryMovementService` y el patrón de documento borrador → confirmar. Las entradas deben generar movimientos `type=in` en la misma transacción atómica que las salidas documentadas con `type=out`.

Git: rama acordada; commits lógicos. No rompas endpoints inventory existentes sin deprecación clara.

Al final: lista rutas API nuevas/cambiadas, permisos nuevos si los hay, y comando de tests ejecutados.
```

---

## 3. Orden sugerido de roles (una fila ≈ un hilo o una secuencia)

| Orden | Rol | Entrega esperada |
|-------|-----|-------------------|
| 0 | **Arquitecto / ADR** | **`docs/ADR-002-inventario-entrega-salida.md`** + **subsección o revisión Entradas** (`inbound_receipt` u homónimo): mismas máquinas de estado, movimientos `in`, campos cabecera (proveedor, factura opcional). |
| 1 | **Backend — datos** | Migraciones/columnas nuevas si el ADR las exige; modelos y casts alineados. |
| 2 | **Backend — dominio** | `InventoryTransferDocumentService` extendido o `InventoryReceiptDocumentService`; confirmación IN/OUT; cancelación borrador. |
| 3 | **API** | CRUD documentos + líneas + confirm/cancel; filtros listado; comentarios en `routes/api.php`. |
| 4 | **Frontend Vue** | **Vista profesional §0.1**: listados entradas/salidas/borradores, editor de documento, modales de confirmación; repos + i18n. |
| 5 | **QA** | Feature: permisos, confirmación entrada aumenta stock, salida lo reduce, idempotencia confirm. |
| 6 | **Documentación** | `ANALISIS_LOGICA_NEGOCIO.md`, `.cursorrules`, `GAP_ANALYSIS` §2 si aplica. |
| 7 | **Cierre estructura / changelog** | `ESTRUCTURA_DATOS_CRITICA.md` (tablas documentos + ref. cruzada), `CHANGELOG_INTERNO_TIBU.md`, tabla §7 actualizada. |

---

## 4. Prompts por rol (copiar / pegar)

### 4.1 Arquitecto — ADR inventario (entradas + salidas + entregas)

```text
Rol: Arquitecto de dominio — inventario almacén.

Tarea: Actualizar **docs/ADR-002-inventario-entrega-salida.md** (o ADR siguiente si se decide tabla separada) incluyendo:

1) **Entregas / salidas** ya descritas: `internal_delivery`, `outbound`, documento + líneas, estados draft/confirmed/cancelled, movimientos OUT y `reference` transfer:{folio}:line:{id}.

2) **Entradas (recepción a almacén):** nuevo valor de `transfer_type` (p. ej. `inbound_receipt`) **o** entidad paralela; confirmación genera movimientos `type=in` con `reference` estable (p. ej. `receipt:{folio}:line:{id}`). Cabecera: proveedor opcional (FK `inventory_suppliers` si existe), número de factura/remisión opcional (string acotado), notas.

3) Reglas de validación por tipo (ej.: `internal_delivery` exige área o receptor al confirmar; `inbound_receipt` puede exigir proveedor o no — decidir y documentar).

4) Permisos: reutilizar `inventory.manage` / `inventory.view` salvo necesidad de rol fino (documentar).

5) Fuera de alcance: ticket lavandería, descuento automático por orden sin ADR aparte.

Salida: ADR actualizado + diagrama Mermaid actualizado. Si el humano pidió libertad total, continúa con implementación en el mismo hilo.
```

### 4.2 Backend — migraciones y modelos

```text
Rol: Backend Laravel — capa datos (inventario).

Según el ADR (entradas + salidas + entregas):
- Migraciones reversibles; FK a inventory_products, users, areas (si aplica).
- Modelos Eloquent: $fillable, relaciones, casts; seguir estilo de InventoryProduct / InventoryMovement.
- No duplicar lógica de stock fuera de servicios; la escritura de stock_quantity sigue centralizada.

Seeders solo si el humano lo pide para demos.
```

### 4.3 Backend — servicios y reglas de negocio (salidas / entregas)

```text
Rol: Backend Laravel — dominio inventario.

Extiende o usa `InventoryTransferDocumentService` + `InventoryMovementService::recordMovementWithoutTransaction` para:
- Confirmar documentos OUT (`internal_delivery`, `outbound`): validar borrador, destino si aplica, líneas ordenadas por producto+id, stock suficiente, una transacción.
- Cancelar borrador sin tocar stock.

Tests Feature obligatorios para confirmación con dos líneas mismo producto y fallo stock insuficiente.
```

### 4.3a Backend — servicios de ENTRADAS (recepción `in`)

```text
Rol: Backend Laravel — dominio inventario (entradas).

Tras actualizar el ADR con `inbound_receipt` (o equivalente):

- Implementar confirmación de documento de entrada: por cada línea llamar a `recordMovementWithoutTransaction` con `InventoryMovement::TYPE_IN` y cantidad positiva (misma firma que usa `record` para entradas).
- Cabecera: persistir proveedor/factura si el ADR los define; validar productos activos.
- Transacción única + lock por producto en orden determinista (mismo patrón que salidas).
- `reference` acordada en ADR (prefijo `receipt:` o `transfer:` según decisión única de trazabilidad).

No dupliques aritmética de stock fuera de `InventoryMovementService`.
```

### 4.4 API — rutas y validación

```text
Rol: API Laravel.

- FormRequest por acción (crear borrador, agregar línea, confirmar, cancelar, listar, export).
- Rutas bajo prefijo /api/inventory coherente con el grupo existente; comenta permisos junto a cada ruta en routes/api.php.
- JSON estable: convención data/meta/errors como el resto del proyecto.

No elimines rutas actuales de movements sin deprecación en el ADR.
```

### 4.5 Frontend — Vue 3 (**vista profesional** + entradas y salidas)

```text
Rol: Vue 3 — módulo inventario (UI producción).

Contexto visual: alinearse a `resources/js/src/views/inventory/products.vue` (paneles, tablas, layout-px-spacing, teleport breadcrumb).

Implementar:

1) **Rutas Vue** bajo inventario: p. ej. `/inventario/documentos` (listado unificado con filtros por tipo: entrada | salida/entrega | estado) y `/inventario/documentos/:id` (detalle/editor de borrador). Alternativa aceptable: dos entradas de menú “Recepciones” y “Salidas y entregas” si el listado unificado se vuelve pesado — documentar en commit.

2) **Listado profesional (§0.1):** tabla con columnas folio, tipo (badge), estado (badge color), fecha creación, creador, acciones (Ver | Continuar borrador si manage). Paginación coherente con API.

3) **Detalle / editor borrador:** cards para cabecera; tabla de líneas con buscador de producto (autocomplete contra catálogo inventario si el API existe); añadir/eliminar línea; validación cantidad > 0; botones “Guardar borrador” (si aplica), “Confirmar” (modal con resumen de líneas y advertencia de impacto en stock), “Cancelar borrador” (modal).

4) **Separación mental entrada vs salida:** textos i18n claros (“Recepción a almacén” vs “Salida / entrega interna”); iconos o colores distintos por `transfer_type`.

5) **Repositorios:** `resources/js/src/repositories/InventoryTransferDocumentsRepository.js` (o nombre acordado); métodos list, get, create, patch, postLine, deleteLine, confirm, cancel.

6) **Sidebar + router:** permisos `inventory.view` para ver; mutaciones `inventory.manage`; meta `permission` como el resto del proyecto.

7) **i18n:** claves bajo `inventory.documents.*` (o prefijo que definas); cero strings nuevos en español suelto en template.

Entrega mínima aceptable: listado + detalle + confirmación de **un** flujo (entrada O salida) en vertical completo; el segundo flujo en la misma PR o PR siguiente con tests E2E manuales descritos en el mensaje del agente.
```

### 4.6 QA — tests automatizados

```text
Rol: QA Laravel Feature.

- Usuario con inventory.manage y sin permiso (403).
- **Entrada:** confirmar documento `inbound_receipt` aumenta `stock_quantity` y crea movimientos `in` con `reference` acordada.
- **Salida / entrega:** confirmar reduce stock; stock insuficiente → error claro; doble confirmación rechazada.
- Listados paginados o filtros si el API los expone.

Ejecuta: php artisan test --filter=InventoryTransfer (o el prefijo que definan los tests nuevos).
```

### 4.7 Documentación y `.cursorrules`

```text
Rol: Documentación.

Actualiza docs/ANALISIS_LOGICA_NEGOCIO.md (sección inventario y rutas §2.1), y la sección de inventario en .cursorrules con los nuevos endpoints y permisos.

Referencia cruzada al **ADR-002**. No duplicar párrafos largos: enlazar al ADR para detalle de estados/tablas.
```

### 4.8 Mega-prompt — Un solo hilo (entradas + salidas + vista pro)

Pega **§4 B** + este bloque si quieres que un solo agente haga ADR + backend + API + Vue + tests en cadena:

```text
Alcance incremental sugerido (orden interno):
1) Ampliar ADR-002 con entradas `inbound_receipt` y referencias `receipt:`.
2) Migración solo si faltan columnas (supplier_id, supplier_invoice nullable en cabecera).
3) Extender InventoryTransferDocumentService (o servicio hermano) para confirmar IN y OUT según transfer_type.
4) API REST completa bajo /api/inventory/transfer-documents (nombres exactos según ADR).
5) Vue: listado + detalle según §0.1 y prompt 4.5.
6) Tests Feature para IN y OUT + 403 sin permiso.

Criterio de calidad UI: un recepcionista debe entender qué documento aumenta stock y cuál lo disminuye sin leer el código.
```

---

## 5. Checklist de integración (humano u orquestador)

- [x] ADR **`docs/ADR-002-inventario-entrega-salida.md`** — **v2 fase 0:** entradas `inbound_receipt`, salidas/entregas, `receipt:` / `transfer:`, cabecera proveedor (**migración 1b** en código).
- [x] Stock y movimientos auditables (usuario, timestamps, referencia/folio) — verificar en QA manual §2–4 y movimientos por producto.
- [x] Permisos y policies alineados; no exponer mutaciones solo con `inventory.view` — tests Feature + checklist §1.
- [x] UI y API documentadas en ANALISIS + `.cursorrules` (incl. rutas Vue de documentos) — **fase 6**.
- [ ] **Vista profesional §0.1** validada en revisión humana — `docs/CHECKLIST_QA_INVENTARIO_DOCUMENTOS_ENTREGA_SALIDA.md` §6.
- [ ] `php artisan migrate` en entorno limpio sin error (validar antes de release).
- [x] Tests Feature en verde: `php artisan test --filter=InventoryTransferDocument`.
- [x] **Fase 7:** tablas documentos en `docs/ESTRUCTURA_DATOS_CRITICA.md` y entrada `docs/CHANGELOG_INTERNO_TIBU.md`.

---

## 6. Cómo usar estos prompts en Cursor

1. Abre **§0** (alcance entrada/salida/entrega) y **§0.1** (criterios de vista profesional).
2. Pega **§4** (o **§4 B**) en **modo Agent**; si quieres todo en un hilo, añade el bloque **§4.8**.
3. Orden típico: **4.1** (ADR con entradas) → **4.2** → **4.3** + **4.3a** → **4.4** → **4.5** (Vue) → **4.6** → **4.7**.
4. Referencias: `@docs/ANALISIS_LOGICA_NEGOCIO.md` `@docs/ADR-002-inventario-entrega-salida.md` `@docs/ESTRUCTURA_DATOS_CRITICA.md` `@docs/CHECKLIST_QA_INVENTARIO_DOCUMENTOS_ENTREGA_SALIDA.md` `@resources/js/src/views/inventory/products.vue` `@resources/js/src/views/inventory/transfer-documents.vue`

---

*Alineación con código: `app/Services/InventoryMovementService.php`, `app/Services/InventoryTransferDocumentService.php`, `app/Http/Controllers/InventoryMovementController.php`, `app/Http/Controllers/InventoryTransferDocumentController.php`, `resources/js/src/views/inventory/products.vue`, `transfer-documents.vue`, `transfer-document-detail.vue`, prefijo `/api/inventory`.*

---

## 7. Estado de fases (ejecución)

| Fase | Estado | Notas |
|------|--------|--------|
| 0 — Arquitecto / ADR | **Hecho (v2)** | `docs/ADR-002-inventario-entrega-salida.md` — entradas IN + salidas/entregas OUT + migración 1b documentada |
| 1 — Backend datos | **Hecho** | `2026_04_24_120000_*` tablas documento/líneas; **`2026_04_24_130000_*`** `supplier_id`, `supplier_reference`; modelo `InventoryTransferDocument` + constante `TRANSFER_INBOUND_RECEIPT` + relación `supplier()`; tests `InventoryTransferDocumentSchemaTest` |
| 2 — Backend dominio | **Hecho** | `InventoryTransferDocumentService::confirm()` OUT (`internal_delivery`, `outbound`, `transfer:`) e **IN** (`inbound_receipt`, `receipt:`); `InventoryMovementService::recordMovementWithoutTransaction`; tests en `InventoryTransferDocumentServiceTest` |
| 3 — API | **Hecho** | `GET|POST /api/inventory/transfer-documents`, `GET|PUT .../transfer-documents/{id}`, `POST .../confirm`, `POST .../cancel-draft`; FormRequests dedicados; `permission:inventory.view|inventory.manage` (lectura) y `inventory.manage` (mutación); tests `InventoryTransferDocumentApiTest` |
| 4 — Frontend Vue | **Hecho** | Rutas `/inventario/documentos`, `/crear`, `/:id`; vistas `transfer-documents.vue`, `transfer-document-detail.vue`; repo `InventoryTransferDocumentsRepository.js`; i18n `inventory.transfer_documents*`; catálogo API incluye `areas` para destinos |
| 5 — QA | **Hecho** | Tests Feature ampliados (403 PUT/cancel sin manage, 422 segunda confirmación); checklist manual `docs/CHECKLIST_QA_INVENTARIO_DOCUMENTOS_ENTREGA_SALIDA.md`; comando regresión documentado |
| 6 — Documentación | **Hecho** | `docs/ANALISIS_LOGICA_NEGOCIO.md` §2.1/2.4/2.5/3/4/6/7/8; `.cursorrules` inventario + referencia ADR-002; `docs/GAP_ANALYSIS_LAVANDERIA.md` §2 alineado con `inventory_*` |
| 7 — Cierre estructura / changelog | **Hecho** | `docs/ESTRUCTURA_DATOS_CRITICA.md` §5 (tablas ADR-002 + referencias); `docs/CHANGELOG_INTERNO_TIBU.md` entrada inventario documentos; §3 tabla roles fila 7 |
