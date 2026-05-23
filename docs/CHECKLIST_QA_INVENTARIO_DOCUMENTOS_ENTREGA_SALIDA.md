# Checklist QA — inventario: documentos de entrada, salida y entrega interna (ADR-002)

Checklist para validar **API**, **permisos** y **UI** de documentos de almacén (`inventory_transfer_*`), complemento de `php artisan test` y de `docs/ADR-002-inventario-entrega-salida.md`.

**Regresión automática (API + dominio):**

```bash
php artisan test --filter=InventoryTransferDocument
```

Incluye: esquema y cabecera proveedor, servicio (IN/OUT, stock, destino entrega interna), API (listado, CRUD borrador, confirm, cancel, 403 sin `inventory.manage`, **idempotencia** segunda confirmación → 422), catálogo con `areas`.

**Entorno:** URL ______________ · **Build / commit:** ______________  
**Ejecutó:** ______________ · **Fecha:** ______________ · **Resultado global:** ☐ OK · ☐ Fallos (detallar al final)

---

## 1) Permisos API

| Paso | Acción | Resultado esperado | ☐ |
|------|--------|---------------------|---|
| 1.1 | Usuario con solo `inventory.view`: `GET /api/inventory/transfer-documents` y `GET .../transfer-documents/{id}`. | **200**; JSON con `data`. | ☐ |
| 1.2 | Mismo usuario: `POST /api/inventory/transfer-documents`, `PUT .../{id}`, `POST .../confirm`, `POST .../cancel-draft`. | **403** (no mutar sin `inventory.manage`). | ☐ |
| 1.3 | Usuario con `inventory.manage` (+ `inventory.view` si aplica): crear borrador con líneas. | **201**; `status` = `draft`. | ☐ |

**Notas:**  
________________________________________________________________________________

---

## 2) Recepción (`inbound_receipt`) — stock aumenta

| Paso | Acción | Resultado esperado | ☐ |
|------|--------|---------------------|---|
| 2.1 | Crear borrador `inbound_receipt` con folio único, una línea cantidad > 0 sobre producto **activo**. | **201**. | ☐ |
| 2.2 | Anotar `stock_quantity` del producto antes de confirmar. | — | ☐ |
| 2.3 | `POST .../confirm`. | **200**; `status` = `confirmed`. | ☐ |
| 2.4 | Revisar stock del producto y movimientos (`GET /api/inventory/products/{id}/movements` o BD). | Stock **aumentó** en la suma de líneas; movimiento `type=in`; `reference` con prefijo **`receipt:`**. | ☐ |
| 2.5 | Repetir `POST .../confirm` sobre el mismo id. | **422** (no doble confirmación). | ☐ |

**Notas:**  
________________________________________________________________________________

---

## 3) Salida documentada (`outbound`) — stock disminuye

| Paso | Acción | Resultado esperado | ☐ |
|------|--------|---------------------|---|
| 3.1 | Producto con stock suficiente; borrador `outbound` con cantidad ≤ stock. | **201**. | ☐ |
| 3.2 | `POST .../confirm`. | **200**; stock **disminuye**; movimiento `type=out`; `reference` con prefijo **`transfer:`**. | ☐ |
| 3.3 | Intento de confirmar de nuevo. | **422**. | ☐ |

**Notas:**  
________________________________________________________________________________

---

## 4) Entrega interna (`internal_delivery`)

| Paso | Acción | Resultado esperado | ☐ |
|------|--------|---------------------|---|
| 4.1 | Borrador **sin** `destination_area_id` ni `recipient_user_id`; `POST .../confirm`. | **422** (mensaje sobre entrega interna / destino). | ☐ |
| 4.2 | Editar borrador (`PUT`) con **área** o **usuario receptor**; confirmar. | **200**; stock sale como OUT; `transfer:` en referencia. | ☐ |

**Notas:**  
________________________________________________________________________________

---

## 5) Borrador — actualizar y cancelar

| Paso | Acción | Resultado esperado | ☐ |
|------|--------|---------------------|---|
| 5.1 | `PUT` borrador: cambiar folio (único) y/o líneas. | **200**; líneas coherentes con payload. | ☐ |
| 5.2 | `POST .../cancel-draft` en borrador. | **200**; `status` = `cancelled`; **sin** movimientos nuevos de stock. | ☐ |
| 5.3 | `PUT` sobre documento ya `confirmed` o `cancelled`. | **422** (solo borrador editable vía API). | ☐ |

**Notas:**  
________________________________________________________________________________

---

## 6) UI Vue (§0.1)

| Paso | Acción | Resultado esperado | ☐ |
|------|--------|---------------------|---|
| 6.1 | Menú **Inventario → Documentos (entrada / salida)**; listado carga con filtros. | Tabla, badges estado/tipo, paginación o vacío con CTA. | ☐ |
| 6.2 | **Nuevo documento** (requiere `inventory.manage`): completar cabecera, añadir línea vía buscador de producto, **Guardar borrador**. | Redirección a detalle con id; datos persistidos. | ☐ |
| 6.3 | Modal **Confirmar documento**; aceptar. | Documento confirmado; stock coherente con §2–4. | ☐ |
| 6.4 | Usuario solo `inventory.view`: no debe poder abrir flujo de creación (ruta `/inventario/documentos/crear` redirige a Home) ni ver botones de mutación en UI. | Comportamiento alineado con permisos. | ☐ |

**Notas:**  
________________________________________________________________________________

## Incidencias globales

________________________________________________________________________________  
________________________________________________________________________________
