# Checklist – Estado del proyecto Lavandería

Resumen de lo **hecho** y lo que **falta** (opcional o futuro) según el Gap Analysis y la Estructura de Datos Crítica.

---

## Hecho

### Base de datos y flujo
- [x] Tabla `ordenes_lavanderia` con campos de auditoría: `recepcionista_id`, `operador_lavado_id`, `operador_secado_id`, `operador_planchado_id`, `entregado_por_id`, `cancelado_at`, `cancelado_por_id`, `motivo_cancelacion`
- [x] Campos peso/cobro: `peso_kg`, `unidad_cobro` (pieza | kg)
- [x] Tiempos de fin de etapa: `lavado_finished_at`, `secado_finished_at`, `planchado_finished_at`
- [x] Tabla `orden_estado_log` (auditoría de cambios de estado)
- [x] Tabla `notificaciones_log` (registro de envío de correos)

### Backend
- [x] Crear orden con `recepcionista_id` (usuario autenticado)
- [x] Avanzar etapa con `operador_id` y registro en `orden_estado_log`
- [x] Marcar entregada con `entregado_por_id`
- [x] Cancelar orden con motivo obligatorio y auditoría
- [x] Listeners de correo registran en `notificaciones_log` (éxito/fallo)

### Frontend
- [x] Recepción: campos **Peso (kg)** y **Unidad de cobro** (pieza/kg)
- [x] Lista: botón **Cancelar** con modal y motivo; permiso `lavanderia.recepcion`
- [x] Lista: modal **Ver detalle** con datos de la orden
- [x] Lista: **filtros** conectados a la API (estatus, etapa, cliente, folio); Refrescar aplica filtros

### Seguridad y permisos
- [x] Recepcionista sin editar/borrar ventas (no hay rutas PUT/DELETE de órdenes)
- [x] Cancelar solo para Administrador y Recepcionista (API + UI con `lavanderia.recepcion`)

### Inventario almacén (insumos / `inventory_*`, distinto del ticket de lavandería)
- [x] Productos, movimientos atómicos, catálogo auxiliar, CSV de ajustes (`inventory.view` / `inventory.manage`)
- [x] **Documentos agregados (ADR-002):** tablas `inventory_transfer_documents` / `inventory_transfer_lines`; API y Vue (`/inventario/documentos`); recepción IN, entrega interna y salida OUT con confirmación en transacción — ver `docs/ADR-002-inventario-entrega-salida.md`, QA manual `docs/CHECKLIST_QA_INVENTARIO_DOCUMENTOS_ENTREGA_SALIDA.md`

---

## Pendiente / opcional (según prioridad)

### Rápido / mejora UX
- [x] **Mensaje en Escanear:** texto de ayuda: "Si el escáner falla, ingrese el folio manualmente".
- [x] **Dashboard tiempo real:** polling cada 15 s; se limpia al salir de la vista.
- [x] **Paginación en Lista:** controles Anterior/Siguiente; la lista pide la página al API. “Cargar más”.

### Medio (módulos o features)
- [ ] **Escaneo por cámara:** en `escanear.vue`, integrar lector QR por cámara (ej. `html5-qrcode` o `vue-qrcode-reader`).
- [ ] **PWA:** `manifest.json` + service worker para instalar la app y, si se desea, uso offline.
- [ ] **Alerta sonora:** cuando una orden pase a rojo (>30 min en etapa), notificación sonora o push para el operador.

### Grande (módulos nuevos)
- [ ] **Consumo automático de insumos vs lavandería:** reglas que descuenten almacén al avanzar orden/etapa, costo de insumos por orden y margen (el **almacén `inventory_*` y documentos ADR-002** ya existen; la brecha es el **enlace automático** orden ↔ stock — ver `docs/GAP_ANALYSIS_LAVANDERIA.md` §2).
- [ ] **Caja y arqueo:** apertura/cierre de caja, registro de cobros por forma de pago, arqueo diario (ver Gap Analysis §3).
- [ ] **Ticket fiscal:** si el comprobante debe ser fiscal, definir requisitos y módulo de facturación/comprobantes.
- [ ] **Turnos / sesiones operador:** tabla de turnos y registrar operador activo por dispositivo para productividad (ver Estructura de Datos Crítica).

---

## Recordatorio operativo

- **Migraciones:** si aún no se han ejecutado las migraciones de auditoría (campos en `ordenes_lavanderia`, `orden_estado_log`, `notificaciones_log`), ejecutar en el servidor y en local:
  ```bash
  php artisan migrate --force
  ```

---

## Documentos de referencia

- `docs/GAP_ANALYSIS_LAVANDERIA.md` – Análisis de brechas vs estándares POS/ERP
- `docs/ESTRUCTURA_DATOS_CRITICA.md` – Campos y tablas obligatorios por flujo (incl. §5 inventario documentos ADR-002)
- `docs/ANALISIS_LAVANDERIA_5_PASOS.md` – Cobertura de los 5 pasos de la propuesta
- `docs/ADR-002-inventario-entrega-salida.md` – Documentos de entrada/salida/entrega interna almacén
- `docs/PROMPTS_MODULOS_ENTREGA_SALIDA_INVENTARIO.md` – Orquestación por fases del módulo inventario documentos
