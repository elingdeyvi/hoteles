# Análisis de Brechas (Gap Analysis) – Sistema de Gestión de Lavandería

**Rol:** Arquitecto de Soluciones Senior  
**Objetivo:** Comparar la propuesta técnica y la implementación actual con estándares de industria POS/ERP para identificar qué falta para que el sistema sea escalable, seguro y funcional en producción.

---

## 1. Manejo de excepciones: pistola/QR y búsqueda manual

**Pregunta:** ¿Qué pasa si la pistola falla? ¿Hay búsqueda manual por folio?

| Aspecto | Estado actual | Estándar POS/ERP | Brecha |
|--------|----------------|-------------------|--------|
| Fallback cuando el QR no se lee | **Parcial** | Siempre debe existir entrada manual por folio o código. | La API ya acepta **folio_unico** además de **codigo_qr** (`required_without`). La vista `escanear.vue` tiene un input de texto con placeholder "Escanee el código QR o **ingrese el folio**" y envía primero como QR, luego como folio si falla. |
| Documentación al usuario | **Falta** | El recepcionista/operador debe saber explícitamente que puede teclear el folio si el escáner falla. | No hay mensaje de ayuda tipo "Si el escáner falla, ingrese el folio manualmente". |
| Cámara como alternativa a pistola | **Falta** | En entornos móviles suele ofrecerse escaneo por cámara. | No hay integración de lector QR por cámara (solo input manual). |

**Conclusión:** Existe **búsqueda manual por folio** a nivel API y flujo en front (input + reintento por folio). Falta claridad en la UI y, opcionalmente, escaneo por cámara para sustituir la pistola en tablets/celulares.

---

## 2. Gestión de inventarios (insumos)

**Pregunta:** El documento menciona "Control de costos de insumos", pero ¿falta la lógica de descuento automático de detergente/suavizante por cada carga de lavado?

**Actualización (2026):** existe **inventario de almacén** en tablas **`inventory_*`** (productos, movimientos, proveedores, etc.) y **documentos** de recepción/salida/entrega interna (**ADR-002**, `docs/ADR-002-inventario-entrega-salida.md`) con confirmación y stock en **`inventory_products`**. Eso **no** sustituye el descuento automático ligado al avance de la **orden de lavandería**.

| Aspecto | Estado actual | Estándar POS/ERP | Brecha |
|--------|----------------|-------------------|--------|
| Catálogo de insumos (almacén) | **Parcial / existe** | Tabla de productos/insumos con unidad y stock. | Hay **`inventory_products`** y catálogos auxiliares; no es el catálogo POS de lavandería (`laundry_sale_*`). |
| Descuento por orden/etapa | **No existe** | Al registrar "lavado" (o por carga), descontar cantidades según reglas (ej. X ml por kg, o por tipo de servicio). | **No** hay acoplamiento automático orden → `inventory_movements`; el flujo documentado es manual o por documento de inventario / movimiento atómico. |
| Costo por orden | **Parcial** | Se guarda `total` (precio al cliente) pero no costo de insumos ni margen. | No hay campos `costo_insumos`, `margen` ni enlace canónico orden → consumo de almacén. |
| Alertas de stock bajo | **Parcial** | Avisos cuando insumo < mínimo. | Comando / consultas sobre **`inventory_products`** (p. ej. umbral); no integrado al ticket de lavandería. |

**Conclusión:** la **brecha principal** sigue siendo el **descuento automático por carga/orden** y la **trazabilidad costo por orden** frente al estándar ERP; el **módulo de almacén `inventory_*`** y los **documentos ADR-002** cubren inventario **gestionado aparte** del ticket de lavandería.

---

## 3. Módulo de caja y arqueo

**Pregunta:** Se menciona "Procesamiento de cobros", pero ¿existe un flujo para apertura y cierre de caja diario para el Recepcionista?

| Aspecto | Estado actual | Estándar POS/ERP | Brecha |
|--------|----------------|-------------------|--------|
| Apertura de caja | **No existe** | Registro de monto inicial, usuario, fecha/hora, turno. | No hay tabla `caja` ni `aperturas_caja`. |
| Cobro por orden | **Parcial** | El `total` se guarda en la orden; no hay registro explícito de "pago recibido" (efectivo/tarjeta/transferencia). | No hay tabla `pagos` ni `cobros` (orden_id, monto, forma_pago, fecha). El cobro es implícito (total en orden). |
| Cierre de caja / arqueo | **No existe** | Cierre con conteo de efectivo, total cobrado, diferencias, firma o validación. | No hay flujo de cierre ni reporte de arqueo. |
| Corte por turno / usuario | **No existe** | Saber qué recepcionista cobró qué en cada turno. | No hay relación cobro → usuario ni turno. |

**Conclusión:** **Falta** el módulo de caja: apertura, registro de cobros por forma de pago, cierre diario y arqueo. "Procesamiento de cobros" hoy es solo el campo `total` en la orden, sin flujo de caja real.

---

## 4. Seguridad y auditoría: cancelaciones y devoluciones

**Pregunta:** Se prohíbe borrar ventas, pero ¿cómo se manejan las cancelaciones justificadas o devoluciones?

| Aspecto | Estado actual | Estándar POS/ERP | Brecha |
|--------|----------------|-------------------|--------|
| No borrado de ventas | **Cumplido** | No hay DELETE de órdenes; uso de `softDeletes` y estatus. | No existen rutas `PUT/DELETE` para órdenes; modelo usa `SoftDeletes`. |
| Cancelación justificada | **Parcial** | Estatus `cancelado` existe en BD y en filtros de lista; no hay flujo guiado ni motivo. | No hay campo `cancelado_por`, `cancelado_at`, `motivo_cancelacion` ni permiso explícito "lavanderia.cancelar" con auditoría. |
| Devoluciones | **No existe** | Orden ya entregada → devolución (parcial/total), motivo, posible reingreso a proceso. | No hay concepto de devolución ni estados "devuelto" o "reclamación". |
| Auditoría de cambios | **No existe** | Log de quién cambió qué y cuándo (estados, cancelación, entrega). | No hay tabla `audit_log` ni `orden_historial`; no se registra `user_id` en transiciones de estado. |

**Conclusión:** La prohibición de borrar ventas se cumple. **Faltan:** flujo formal de cancelación (motivo, usuario, timestamp), manejo de devoluciones y auditoría (historial de cambios por orden y usuario).

---

## 5. Persistencia de datos y estrategia offline (PWA)

**Pregunta:** Dado que es una PWA, ¿hay estrategia para trabajar offline si falla el internet?

| Aspecto | Estado actual | Estándar PWA/offline | Brecha |
|--------|----------------|----------------------|--------|
| PWA instalable | **No** | `manifest.json` + service worker para "Añadir a pantalla de inicio". | No hay `manifest.json` ni service worker en el proyecto. |
| Cache de assets | **No** | Service worker cachea JS/CSS/HTML para cargar sin red. | No implementado. |
| Cola de acciones offline | **No** | Crear orden / avanzar etapa se guardan en cola local y se envían al reconectar. | No hay IndexedDB/LocalStorage para cola de órdenes ni sincronización al volver online. |
| Indicador de conectividad | **No** | Banner o icono "Sin conexión" / "Modo offline". | No hay detección ni mensaje al usuario. |
| Conflictos al sincronizar | **N/A** | Política de resolución (último gana, merge, etc.). | Sin cola offline no hay política definida. |

**Conclusión:** **No hay** estrategia offline: ni PWA completa ni cola de operaciones. Si el internet de la lavandería falla, no se puede crear órdenes ni avanzar estados de forma confiable hasta recuperar conexión.

---

## 6. Atributos de orden: peso (kg) vs. pieza

**Pregunta:** ¿Falta definir el manejo de prendas por peso (kg) vs. por pieza?

| Aspecto | Estado actual | Estándar lavandería | Brecha |
|--------|----------------|---------------------|--------|
| Detalle de prendas | **Sí (texto)** | Campo `detalles_ropa` libre. | Solo texto; no hay estructura "cantidad + unidad (pieza/kg) + tipo". |
| Peso en kg | **No** | Campo `peso_kg` para cobro o logística por peso. | No existe `peso_kg` ni `unidad_cobro` (pieza | kg). |
| Cobro por pieza vs. por kg | **No** | Reglas de precio (precio por kg, por pieza, o mixto). | El `total` es único; no hay desglose ni fórmula (ej. precio_unitario × cantidad o × peso). |
| Confirmación de peso en recepción | **No** | Balanza → peso registrado en orden. | No hay campo ni flujo para "peso confirmado" (recepción o antes de lavado). |

**Conclusión:** **Falta** definir y persistir: peso (kg), unidad de cobro (pieza/kg), y si aplica confirmación de peso en recepción. Hoy el cobro es un `total` manual sin regla ni atributo peso/pieza.

---

## Puntos adicionales detectables como faltantes

### Confirmación de peso
El paso 1 menciona "Detalles de la ropa" pero no especifica si el cobro es por kilo o por prenda. **Falta:** campo `peso_kg`, `unidad_cobro` (pieza | kg) y/o desglose de ítems con cantidad y unidad para soportar ambos modelos.

### Gestión de turnos
No se detalla cómo el sistema identifica qué operador está activo en la tablet en ese momento. **Falta:** tabla o relación "turno" / "sesión operador" (user_id, dispositivo, apertura/cierre) y, en cada avance de etapa, registrar `operador_id` o `turno_id` para productividad y auditoría.

### Ticket de pago / comprobante fiscal
Se menciona "Ticket del cliente" con resumen del pedido, pero no queda claro si funciona como comprobante fiscal o legal de pago. **Falta:** definir si el ticket actual es solo informativo o debe cumplir requisitos fiscales (RFC, UUID, sello, leyenda SAT); si es lo segundo, falta módulo de facturación/comprobantes.

### Alertas de tiempo (semáforo + alerta sonora)
El semáforo marca rojo a los 30 minutos en el dashboard. **Falta:** alerta sonora (o push) para el operador en la estación física cuando una orden pasa a rojo, para no depender de que mire la pantalla. No hay notificación sonora ni push en el front ni en backend.

---

## Resumen ejecutivo de brechas

| # | Área | Severidad | Resumen |
|---|------|-----------|--------|
| 1 | Excepciones / fallback QR | Baja | Búsqueda manual por folio sí existe; mejorar UX y opcionalmente cámara. |
| 2 | Inventario de insumos | Alta | No hay catálogo ni descuento automático; necesario para "control de costos". |
| 3 | Caja y arqueo | Alta | No hay apertura/cierre ni registro de cobros por forma de pago. |
| 4 | Cancelaciones y auditoría | Media | Estatus cancelado existe; faltan motivo, usuario, timestamp y auditoría. |
| 5 | Offline / PWA | Media | Sin PWA ni cola offline; impacto ante fallas de internet. |
| 6 | Peso vs. pieza | Media | Sin peso_kg ni unidad de cobro; total manual sin regla. |
| + | Peso confirmado, turnos, ticket fiscal, alerta sonora | Media/Baja | Detalles que redondean un sistema listo para producción. |

---

## Próximos pasos sugeridos

1. **Corto plazo:** Documentar en UI el fallback "Si el escáner falla, ingrese el folio manualmente"; opcionalmente añadir escaneo por cámara.  
2. **Inventario:** Diseñar tablas `insumos`, `movimientos_insumos` y reglas de descuento por etapa/tipo de servicio; opcionalmente `costo_insumos` por orden.  
3. **Caja:** Diseñar `aperturas_caja`, `cobros` (o `pagos`) y flujo de cierre/arqueo para Recepcionista.  
4. **Auditoría:** Añadir `motivo_cancelacion`, `cancelado_por`, `cancelado_at` y tabla de historial de cambios de estado (con user_id).  
5. **PWA/offline:** Introducir manifest, service worker y, si se requiere operación sin red, cola local con sincronización.  
6. **Orden:** Añadir `peso_kg`, `unidad_cobro` (pieza/kg) y, si aplica, confirmación de peso en recepción.  
7. **Turnos y productividad:** Registrar operador (y turno si aplica) en cada cambio de etapa.  
8. **Alertas:** Añadir notificación sonora o push cuando una orden pase a rojo (>30 min en etapa).

---

## Documentos relacionados

- **Estructura de datos crítica:** Ver `ESTRUCTURA_DATOS_CRITICA.md` para la lista de campos y tablas que deben existir en base de datos según el flujo Recepción → Lavado → Secado/Planchado → Liberación (timestamps, operador-productividad, logs de notificaciones).
