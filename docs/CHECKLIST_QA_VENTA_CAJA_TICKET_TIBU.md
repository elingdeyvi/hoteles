# Checklist QA manual — venta, caja y ticket (TIBU y catálogo POS)

Checklist operativo para el equipo antes de un release o tras cambios en POS, caja o tickets. Complementa `REGRESION_MANUAL_TIBU.md` y la suite `php artisan test`.

**Regresión automática (C8, `PROMPTS_CORRECCION_LOGICA_NEGOCIO_TIBU.md`):** ejecutar la suite enfocada en órdenes, tickets y caja:

```bash
composer run test:tibu-c8
```

(equivalente: `php artisan test --testsuite=TibuC8Regression`). Incluye precios C2 / líneas de lavandería, PDF de ticket, historial, observaciones por línea, políticas de caja y órdenes.

**Regresión automática catálogo POS (ADR-001, fase 4/6):**

```bash
php artisan test --filter=LaundryPosSaleCatalogQaTest
php artisan test --filter=LaundrySaleCatalogAdminTest
```

**Entorno:** indicar URL y versión desplegada.  
**Ejecutó:** ______________ · **Fecha:** ______________ · **Resultado global:** ☐ OK · ☐ Fallos (detallar abajo)

---

## 1) Caja abierta → venta POS → ticket (dos QR y observaciones)

| Paso | Acción | Resultado esperado | ☐ |
|------|--------|-------------------|---|
| 1.1 | Iniciar sesión con usuario que tenga permiso de caja y abrir sesión de caja (flujo habitual en la app). | Caja en estado abierta; sin errores en consola de red. | ☐ |
| 1.2 | Ir al **Punto de venta (POS)** y registrar una venta que incluya líneas con **observaciones** (por prenda TIBU y/o por línea de **catálogo POS**, según corresponda). | Orden creada; respuesta API coherente; sin bloqueo por caja cerrada. | ☐ |
| 1.3 | Generar o abrir el **ticket** (jsPDF en navegador o PDF servidor, según el flujo activo). | El ticket muestra **dos bloques de QR** (dos copias del mismo contenido) alineado con el spec TIBU. | ☐ |
| 1.4 | Revisar el **listado de observaciones** en el ticket (o en el bloque previsto al ticket térmico). | Las observaciones coinciden con lo capturado en POS; en líneas **catálogo POS** el título de agrupación usa el **nombre del producto** (`laundry_sale_product`), no tipo de prenda. | ☐ |

**Notas / incidencias:**  
________________________________________________________________________________

---

## 1b) Catálogo POS (producto) — venta y ticket

*Requiere productos activos en **Administración → Catálogo POS** (`/admin/laundry-pos-catalog`) y permiso `administracion.areas` para administrarlos; en POS basta `pos.vender` + caja abierta.*

| Paso | Acción | Resultado esperado | ☐ |
|------|--------|-------------------|---|
| 1b.1 | En POS, agregar **al menos una línea** desde el bloque **Catálogo POS** (categoría/producto) y completar la venta. | Total en pantalla alineado con **preview** backend; orden creada con `laundry_sale_product_id` en detalle (ver API `GET /api/lavanderia/ordenes/{id}` si se desea verificar). | ☐ |
| 1b.2 | **Opcional:** misma orden con **línea TIBU + línea catálogo POS** (carrito mixto). | Suma de subtotales correcta; sin error 422; no se mezcla `garment_type_id` con `laundry_sale_product_id` en la misma línea. | ☐ |
| 1b.3 | Generar **ticket jsPDF** (navegador) y/o **PDF servidor** para esa orden. | En el concepto de línea aparece el **nombre del producto** del catálogo POS (no solo “Ítem” genérico). | ☐ |
| 1b.4 | **Historial de órdenes** → export **CSV** (`export-csv`, permiso `reportes.ver`). | Encabezado incluye columna **`laundry_sale_lines_count`**; para órdenes solo con líneas catálogo POS el valor es ≥ 1. | ☐ |

**Notas / incidencias (catálogo POS):**  
________________________________________________________________________________

---

## 2) Reimprimir desde historial de tickets

| Paso | Acción | Resultado esperado | ☐ |
|------|--------|-------------------|---|
| 2.1 | Ir a **Ventas → Historial de tickets** (o ruta equivalente). | Lista carga con filtros básicos. | ☐ |
| 2.2 | Localizar la orden del paso 1 (folio o búsqueda). | La orden aparece y coincide con datos de venta. | ☐ |
| 2.3 | Usar **reimprimir** (o PDF) desde el historial. | Se genera ticket/PDF con la misma semántica que en POS (incl. dos QR y observaciones si aplica). | ☐ |

**Notas / incidencias:**  
________________________________________________________________________________

---

## 3) Corte de caja y verificación frente a ventas del período

| Paso | Acción | Resultado esperado | ☐ |
|------|--------|-------------------|---|
| 3.1 | Con la misma sesión de caja abierta, revisar en la pantalla de **caja** (o resumen previo al corte) totales por método de pago / efectivo esperado si el producto lo muestra. | Cifras coherentes con las ventas registradas en el turno. | ☐ |
| 3.2 | Ejecutar **corte de caja** con arqueo si el negocio lo usa (efectivo contado, notas). | Cierre sin error; PDF o JSON de resumen según configuración. | ☐ |
| 3.3 | **Contrastar** totales del corte vs suma de ventas del período (muestra manual o reporte). | Diferencias solo explicadas por arqueo/retiros/movimientos manuales documentados. | ☐ |

**Notas / incidencias:**  
________________________________________________________________________________

---

## 4) Cierre de sesión API (logout) y 401 en rutas protegidas

| Paso | Acción | Resultado esperado | ☐ |
|------|--------|-------------------|---|
| 4.1 | Obtener un token Sanctum válido (login habitual en la app o `POST /api/tokens/create` según entorno). | Token recibido y usable en `Authorization: Bearer …`. | ☐ |
| 4.2 | Llamar a una ruta **protegida** (p. ej. `GET /api/tokens/permissions` o `GET /api/caja/estado`) con el token. | Respuesta **200** (o la esperada con usuario autenticado). | ☐ |
| 4.3 | Llamar a **`POST /api/tokens/logout`** con el mismo token (según contrato actual del proyecto). | Respuesta de logout correcta (p. ej. 204). | ☐ |
| 4.4 | Repetir la petición del paso 4.2 **con el mismo token revocado**. | Respuesta **401** (no autorizado). | ☐ |

**Herramientas sugeridas:** pestaña Red del navegador, Postman, Insomnia o `curl`.

**Notas / incidencias:**  
________________________________________________________________________________

---

## C8 — Checklist mínimo (corrección lógica negocio TIBU + catálogo POS)

Comprueba escenarios de `PROMPTS_CORRECCION_LOGICA_NEGOCIO_TIBU.md` § C8 (C0: precio snapshot, tipo de carga, QR, áreas) y regresión **catálogo POS** (`LaundryPosSaleCatalogQaTest`). Ejecutar antes **Composer:** `composer run test:tibu-c8` y los filtros de la sección introductoria.

| Paso | Acción | Resultado esperado | ☐ |
|------|--------|-------------------|---|
| C8.1 | **Caja abierta** → **POS:** venta con servicio TIBU + **al menos dos tipos de prenda** y **distintos tipos de carga** en líneas (donde aplique al precio/operación). | Totales alineados con matriz **C2**; ticket con bloque de **observaciones por prenda** si se capturaron; **tipo de carga** visible en líneas si operación lo exige. | ☐ |
| C8.2 | Movimiento de **inventario** (entrada/salida en `inventory_*`), distinto del POS. | No aparece en ticket de lavandería / venta. | ☐ |
| C8.3 | **Operador** con área asignada (otro operador con mismo rol en otra área): en **su** área, 1.er QR = inicio de etapa, 2.º = fin; intento en **área incorrecta** → error claro (**C5**). | Escaneos coherentes con presencia por área. | ☐ |
| C8.4 | **Una sola** carga en las líneas del ticket → **dos** bloques QR **idénticos** (dos copias); **dos** cargas distintas en líneas → **dos** QR **distintos** (**C4b**); validar escaneo posterior. | Paridad cliente (jsPDF) / servidor (Blade) según flujos activos. | ☐ |
| C8.5 | **Catálogo POS:** venta con `laundry_sale_product_id` (pieza o kg según producto), con **observación de línea**; reimprimir ticket. | Snapshot de precio coherente con `unit_price` del producto; observaciones agrupadas bajo el nombre del producto POS; ticket térmico y jsPDF coherentes. | ☐ |

**Notas / incidencias:**  
________________________________________________________________________________

---

## Registro de fallos

| ID | Sección | Descripción breve | Severidad |
|----|---------|-------------------|-----------|
| | | | ☐ Bloqueante · ☐ Mayor · ☐ Menor |

---

*Documento según prompt **§29** (`PROMPTS_ADICIONALES_TIBU.md`). Suite de regresión y checklist manual **C8**: `PROMPTS_CORRECCION_LOGICA_NEGOCIO_TIBU.md` — `composer run test:tibu-c8` y sección **C8** arriba. **Fase 6 (migración POS producto):** sección **1b**, filas **C8.5** y comandos `LaundryPosSaleCatalogQaTest` / `LaundrySaleCatalogAdminTest`.*
