# Estructura de datos crítica – Flujo "Recorrido de la ropa"

Basado en los pasos **Recepción → Lavado → Secado/Planchado → Liberación**, esta lista detalla campos que **DEBEN** estar en la base de datos y que no están explícitamente detallados en la propuesta, necesarios para semáforo, productividad y auditoría.

---

## 1. Campos por paso del flujo

### Paso 1: Recepción
| Campo | ¿Existe hoy? | Uso |
|------|----------------|-----|
| `cliente_nombre`, `cliente_email` | Sí | Identificación del cliente. |
| `tipo_servicio`, `detalles_ropa`, `instrucciones` | Sí | Detalle del servicio. |
| `folio_unico`, `codigo_qr` | Sí | Identificación única y escaneo. |
| `total` | Sí | Monto a cobrar. |
| `recepcion_id` / `recepcionista_id` | **No** | Auditoría: qué usuario recibió la orden. |
| `recepcion_at` | **Parcial** | Hoy se usa `created_at`; podría ser explícito si recepción ≠ creación. |
| `peso_kg` | **No** | Cobro o logística por peso; confirmación en balanza. |
| `unidad_cobro` (pieza | kg) | **No** | Define si el total se calcula por pieza o por peso. |

### Paso 2: Lavado
| Campo | ¿Existe hoy? | Uso |
|------|----------------|-----|
| `lavado_started_at` | Sí | Inicio de etapa (semáforo). |
| `lavado_finished_at` | **No** | Fin de etapa; duración real = finished - started. |
| `operador_lavado_id` | **No** | Productividad: quién lavó esta orden. |
| `current_step_started_at` | Sí | Usado para tiempo en etapa (semáforo). |

### Paso 3: Secado / Planchado
| Campo | ¿Existe hoy? | Uso |
|------|----------------|-----|
| `secado_started_at`, `planchado_started_at` | Sí | Inicio de cada etapa. |
| `secado_finished_at`, `planchado_finished_at` | **No** | Fin de etapa; duración y productividad. |
| `operador_secado_id`, `operador_planchado_id` | **No** | Productividad por área. |

### Paso 4: Liberación (Terminado → Entregado)
| Campo | ¿Existe hoy? | Uso |
|------|----------------|-----|
| `terminado_at`, `entregado_at` | Sí | Cierre de proceso y entrega. |
| `entregado_por_id` | **No** | Quién entregó (recepcionista o admin). |
| `cobrado_at` / `pagado_at` | **No** | Si el cobro se registra al momento de entrega. |

### Cancelación y auditoría
| Campo | ¿Existe hoy? | Uso |
|------|----------------|-----|
| `estatus` (incl. cancelado) | Sí | Estado general. |
| `cancelado_at`, `cancelado_por_id`, `motivo_cancelacion` | **No** | Cancelación justificada y trazabilidad. |

---

## 2. Timestamps para cada cambio de estado (semáforo y reportes)

**Requisito:** Poder calcular "cuánto tiempo llevó en la etapa actual" y "cuánto tiempo total por etapa".

| Dato | Estado actual | Recomendación |
|------|----------------|---------------|
| Inicio de etapa | `current_step_started_at` + `lavado_started_at`, etc. | **Sí**; suficiente para semáforo (diferencia con `now()`). |
| Fin de etapa | No guardado | Añadir `lavado_finished_at`, `secado_finished_at`, `planchado_finished_at` para duración real y reportes. |
| Historial de cambios | No existe | Tabla **`orden_estado_log`** (orden_id, step_anterior, step_nuevo, user_id, created_at) para auditoría y análisis. |

**Campos que DEBEN existir para semáforo (ya cubiertos):**
- `current_step`
- `current_step_started_at` (o timestamps por etapa)
- Cálculo en backend: `minutos_en_etapa = diff(now(), current_step_started_at)` → color rojo si > 30.

---

## 3. Relación Operador de área ↔ Productividad

**Requisito:** Saber "quién lavó / secó / planchó qué orden" para productividad y calidad.

| Elemento | Estado actual | Recomendación |
|----------|----------------|---------------|
| Asignación operador por etapa | No | Añadir en `ordenes_lavanderia`: `operador_lavado_id`, `operador_secado_id`, `operador_planchado_id` (FK a `users`), o una sola tabla **`orden_etapa_operador`** (orden_id, step, user_id, started_at, finished_at). |
| Turno activo | No | Tabla **`turnos`** o **`sesiones_operador`** (user_id, apertura_at, cierre_at, dispositivo) y en cada avance registrar user_id del token + opcionalmente turno_id. |

**Campos que DEBEN existir (no están hoy):**
- `operador_lavado_id` (nullable, FK users) — o equivalente vía tabla de historial.
- `operador_secado_id`, `operador_planchado_id` (nullable, FK users).
- O tabla: `orden_etapas_ejecucion` (orden_id, step, user_id, started_at, finished_at).

---

## 4. Logs de notificaciones

**Requisito:** ¿Se guardó registro de que el correo al dueño / al cliente realmente se envió?

| Elemento | Estado actual | Recomendación |
|----------|----------------|---------------|
| Registro de envío | No | Tabla **`notificaciones_log`** (orden_id, tipo: 'creada_admin' | 'terminada_admin' | 'lista_cliente', destinatario_email, enviado_at, estado: enviado | fallido, error_message nullable). |
| Fallos | Laravel guarda en `failed_jobs` los jobs en cola que fallan; los listeners de correo son ShouldQueue. | Para trazabilidad explícita por orden, conviene además un log propio (notificaciones_log) al enviar el correo (en el listener). |

**Campos que DEBEN existir (no están hoy):**
- Tabla `notificaciones_log`: `orden_id`, `tipo`, `destinatario`, `enviado_at`, `estado`, `mensaje_error` (nullable).

---

## 5. Resumen: campos/tablas a añadir

### En tabla `ordenes_lavanderia` (o equivalente)
- `recepcionista_id` (nullable, FK users).
- `peso_kg` (nullable), `unidad_cobro` (enum: pieza | kg).
- `lavado_finished_at`, `secado_finished_at`, `planchado_finished_at` (nullable).
- `operador_lavado_id`, `operador_secado_id`, `operador_planchado_id` (nullable, FK users).
- `entregado_por_id` (nullable, FK users).
- `cancelado_at` (nullable), `cancelado_por_id` (nullable, FK users), `motivo_cancelacion` (text, nullable).

### Tablas nuevas recomendadas
- **`orden_estado_log`**: id, orden_id, step_anterior, step_nuevo, user_id (nullable), created_at.  
  Para auditoría y reportes de tiempo por etapa.
- **`orden_etapas_ejecucion`** (alternativa a columnas operador_*): id, orden_id, step, user_id, started_at, finished_at.  
  Para productividad por operador y por etapa.
- **`notificaciones_log`**: id, orden_id, tipo (string), destinatario (string), enviado_at, estado (enviado|fallido), mensaje_error (nullable).  
  Para saber si el correo al dueño/cliente se envió.
- **`turnos`** o **`sesiones_operador`** (opcional): id, user_id, apertura_at, cierre_at, para asociar operador activo en la tablet.

### Catálogo POS de lavandería (`laundry_sale_*`, ADR-001)

Tablas **`laundry_sale_categories`** y **`laundry_sale_products`** (precio `unit_price`, unidad de cobro `billing_unit` piece|kg). En **`orden_lavanderia_detalles`**, la columna **`laundry_sale_product_id`** (nullable, FK) identifica líneas vendidas por ese catálogo; **no** deben mezclarse con `garment_type_id` en la misma fila. Convive con líneas **TIBU** (matriz `laundry_piece_prices`) y **legacy** (`producto_id`). El inventario de almacén (`inventory_*`) es un dominio aparte.

### Inventario almacén — documentos agregados (ADR-002)

No forman parte del ticket de lavandería; sirven para **recepción a almacén**, **entrega interna** (área/receptor) y **salida documentada**. Detalle funcional: **`docs/ADR-002-inventario-entrega-salida.md`**.

| Tabla | Rol |
|-------|-----|
| **`inventory_transfer_documents`** | Cabecera: `folio` (único), `transfer_type` (`inbound_receipt` \| `internal_delivery` \| `outbound`), `status` (`draft` \| `confirmed` \| `cancelled`), destino opcional (`destination_area_id`, `recipient_user_id`), proveedor opcional (`supplier_id`, `supplier_reference`), auditoría (`created_by`, `confirmed_*`, `cancelled_*`). |
| **`inventory_transfer_lines`** | Líneas: `inventory_transfer_document_id`, `inventory_product_id`, `quantity`, `sort_order`. |
| **`inventory_movements`** (existente) | Al **confirmar** el documento se crean movimientos `in` o `out` por línea; `reference` con prefijo acordado (`receipt:` / `transfer:`) y trazabilidad al folio/línea. |

---

## 6. Checklist de verificación

Usar esta lista para validar la base de datos frente al flujo completo:

- [ ] Cada cambio de estado tiene timestamp (inicio; recomendable también fin).
- [ ] Se puede responder "quién recibió esta orden" (recepcionista_id).
- [ ] Se puede responder "quién lavó / secó / planchó esta orden" (operador por etapa).
- [ ] Semáforo puede calcular minutos en etapa actual con datos en BD.
- [ ] Cancelaciones tienen motivo, usuario y fecha.
- [ ] Existe registro de envío de notificaciones (correo dueño/cliente) por orden.
- [ ] (Opcional) Peso y unidad de cobro para facturación y reportes.
- [ ] (Opcional) Turnos o sesiones para operadores activos.
- [ ] Líneas con **`laundry_sale_product_id`** quedan con snapshot de precio y sin `garment_type_id` (venta por catálogo POS).

---

## Referencia cruzada

- **Gap Analysis:** Ver `GAP_ANALYSIS_LAVANDERIA.md` para el análisis de brechas frente a estándares POS/ERP (excepciones, inventario, caja, auditoría, offline, peso vs. pieza, turnos, ticket fiscal, alertas).
- **Catálogo POS:** `docs/ADR-001-pos-catalogo-productos.md`, `docs/ANALISIS_LOGICA_NEGOCIO.md` §2.1 y §6.
- **Inventario documentos (almacén):** `docs/ADR-002-inventario-entrega-salida.md`, `docs/CHECKLIST_QA_INVENTARIO_DOCUMENTOS_ENTREGA_SALIDA.md`, `docs/PROMPTS_MODULOS_ENTREGA_SALIDA_INVENTARIO.md`.
