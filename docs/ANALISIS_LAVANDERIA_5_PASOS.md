# Análisis: Cobertura de los 5 pasos del sistema de lavandería

Este documento compara el estado actual del proyecto (migraciones, controladores, permisos, vistas JS, repositories, stores, notificaciones) con los requisitos de los 5 prompts definidos.

---

## Paso 1: Base de datos y roles

### Requisitos del prompt
- **Roles:** Administrador (Dueño), Recepcionista, Operadores (Lavado, Secado, Planchado).
- **Permisos:** Admin = total; Recepcionista = registrar, cobrar, entregar; **restricción:** no editar ni borrar ventas; Operadores = solo ver órdenes de su área y cambiar estados.
- **Tabla Orders:** cliente, tipo servicio, detalles, folio único, estado (Pendiente, Lavando, Secado, Planchado, Terminado), instrucciones especiales.

### Estado en el proyecto

| Requisito | ¿Cubierto? | Dónde |
|-----------|------------|--------|
| Rol Administrador | Sí | `database/migrations/2024_01_15_000000_create_roles_and_test_users.php`, `2026_02_09_000001_create_roles_lavanderia.php` |
| Rol Recepcionista | Sí | Misma migración; permisos `lavanderia.recepcion`, `lavanderia.consultar`, `lavanderia.dashboard` |
| Rol Operador | Sí | Permisos `lavanderia.operador`, `lavanderia.dashboard` |
| Admin acceso total | Sí | `givePermissionTo($permisosLavanderia)` para Administrador |
| Recepcionista sin editar/borrar ventas | Sí | No existen rutas `PUT/PATCH/DELETE` para órdenes en `routes/api.php`; solo `GET`, `POST` (crear, listar, avanzar, entregar). |
| Operadores solo su área | Sí | Vista `operador.vue` filtra por `current_step` (lavado/secado/planchado); API `getDashboardResumen` devuelve todas y el front filtra por etapa. |
| Tabla tipo Orders | Sí | `ordenes_lavanderia`: `cliente_nombre`, `cliente_email`, `tipo_servicio`, `detalles_ropa`, `instrucciones`, `folio_unico`, `current_step` (recepcion, lavado, secado, planchado, terminado, entregado), `estatus` (pendiente, en_proceso, terminado, entregado, cancelado). |
| Instrucciones especiales | Sí | Campo `instrucciones` (nullable) en migración y modelo. |

**Conclusión Paso 1:** Cubierto. La restricción de Recepcionista se cumple por ausencia de endpoints de edición/borrado de órdenes.

---

## Paso 2: Vista de recepción y generación de tickets

### Requisitos del prompt
- Formulario: nombre, servicio, detalles.
- Al guardar: 1) Folio único, 2) Vista previa de dos tickets (cliente con QR + interno autoadhesivo), 3) Notificación (mockup correo) al dueño con número de orden y total.

### Estado en el proyecto

| Requisito | ¿Cubierto? | Dónde |
|-----------|------------|--------|
| Formulario nombre, servicio, detalles | Sí | `recepcion.vue`: `cliente_nombre`, `tipo_servicio`, `detalles_ropa`, `instrucciones`, `total`, `is_priority`, `cliente_email`. |
| Folio único al guardar | Sí | `OrdenLavanderiaService::crearOrden()` → `generarFolioUnico()` (LMS-YYYYMMDD-XXXXXX). |
| Vista previa ticket cliente con QR | Sí | Modal en `recepcion.vue`: "Ticket Cliente" con folio, cliente, servicio, total, prioridad, imagen QR (`getQRCodeUrl(codigo_qr)`). |
| Vista previa ticket interno | Sí | Mismo modal: "Ticket Interno" con detalles e instrucciones; texto "Coloque este ticket en la bolsa/canasta". |
| Notificación al dueño (orden + total) | Sí (real, no mockup) | Evento `OrdenLavanderiaCreada` → `EnviarCorreoOrdenLavanderiaCreada` → `Mail::to(config('mail.from.address'))` con `OrdenLavanderiaCreadaAdmin` (folio, cliente, servicio, total). |

**Conclusión Paso 2:** Cubierto. La notificación es correo real al admin/dueño; los dos tickets están en un solo modal imprimible (`@media print`).

---

## Paso 3: Vistas operativas y escaneo QR

### Requisitos del prompt
- Interfaz para operadores de área.
- Optimizada para móviles, botones grandes.
- Usar cámara del dispositivo para escanear QR del ticket interno.
- Al escanear: mostrar Instrucciones Especiales de forma llamativa si existen.
- Botón de acción principal para cambiar estado (ej. "Iniciar Lavado", "Pasar a Secado").

### Estado en el proyecto

| Requisito | ¿Cubierto? | Dónde |
|-----------|------------|--------|
| Interfaz operadores por área | Sí | `operador.vue`: pestañas Lavado / Secado / Planchado; lista de órdenes de esa etapa. |
| Móvil, botones grandes | Parcial | Clases `btn-lg`, `operador-btn` con padding; no hay meta viewport específica ni diseño PWA móvil-first documentado. |
| Escanear QR con cámara | No | `escanear.vue` solo tiene input de texto para pegar código o folio; no hay integración de lector de cámara (ej. `html5-qrcode`, `vue-qrcode-reader`). |
| Instrucciones especiales llamativas al escanear | Sí | En `escanear.vue`, al mostrar orden encontrada: `ordenEncontrada.instrucciones` en `<span class="text-warning">`. En `operador.vue` cada tarjeta muestra `orden.instrucciones` en `text-warning`. |
| Botón acción principal (Iniciar/Avanzar etapa) | Sí | "Iniciar/Avanzar Etapa" en `escanear.vue`; en `operador.vue` cada orden es un botón que llama `avanzar(orden)`. |

**Conclusión Paso 3:** Casi todo cubierto. **Falta:** uso de la cámara del dispositivo para escanear el QR (solo entrada manual de código/folio).

---

## Paso 4: Dashboard de control (semáforo)

### Requisitos del prompt
- Dashboard administrador, diseño PWA.
- Semáforo: Verde = terminadas; Amarillo = en proceso (tiempo en etapa); Rojo = alerta si > 30 min en la misma área.
- Actualización en tiempo real conforme operadores cambian estados desde celulares.

### Estado en el proyecto

| Requisito | ¿Cubierto? | Dónde |
|-----------|------------|--------|
| Dashboard administrador | Sí | `dashboard.vue` + ruta `/lavanderia/dashboard`; acceso por rol (Administrador, Recepcionista, Operador). |
| Semáforo Verde / Amarillo / Rojo | Sí | `OrdenLavanderiaController::dashboard()`: `color` = verde si terminado/entregado; rojo si `minutos_en_etapa > 30`; sino amarillo. Vista con clases `orden-verde`, `orden-amarillo`, `orden-rojo`. |
| Tiempo en etapa actual | Sí | `minutos_en_etapa` calculado con `current_step_started_at` (o `created_at`); mostrado en tarjeta. |
| Alerta > 30 min en misma área | Sí | Lógica en controlador; badge rojo en UI. |
| Diseño PWA | No | No hay `manifest.json` ni service worker en el proyecto para instalación PWA. |
| Tiempo real | Parcial | Solo botón "Refrescar"; en código hay comentario `// setInterval(cargarDatos, 30000)` sin activar. No hay WebSockets ni polling automático. |

**Conclusión Paso 4:** Semáforo y reglas de color cubiertos. **Faltan:** diseño explícito PWA (manifest + SW) y actualización en tiempo real (polling cada X s o WebSockets).

---

## Paso 5: Lógica de finalización y notificaciones finales

### Requisitos del prompt
- Al marcar orden como "Terminado": 1) Orden desaparece del flujo operativo, 2) Notificación automática al dueño, 3) (Opcional) Aviso al cliente de que la ropa está lista para recolección.

### Estado en el proyecto

| Requisito | ¿Cubierto? | Dónde |
|-----------|------------|--------|
| Orden desaparece del flujo al terminar | Sí | `getPendientesEnProceso()` filtra `estatus` in (pendiente, en_proceso). Al marcar "Terminado", `estatus` → `terminado`, por tanto ya no aparece en dashboard/operador. |
| Notificación automática al dueño al terminar | Sí | Implementado: `OrdenLavanderiaTerminada` → `EnviarCorreoOrdenLavanderiaTerminadaAdmin` envía correo al dueño (`config('mail.from.address')`) con folio y total. Ver `app/Mail/OrdenLavanderiaTerminadaAdmin.php` y `app/Listeners/EnviarCorreoOrdenLavanderiaTerminadaAdmin.php`. |
| Aviso opcional al cliente (ropa lista) | Sí | `OrdenLavanderiaTerminada` → `EnviarCorreoOrdenLavanderiaListaCliente` si `cliente_email` está definido; template "Tu ropa está lista para ser recogida". |

**Conclusión Paso 5:** Cubierto. La notificación al dueño al marcar "Terminado" está implementada (correo `OrdenLavanderiaTerminadaAdmin`).

---

## Resumen por capa

### Migraciones
- Tabla `ordenes_lavanderia` con todos los campos necesarios (incl. `instrucciones`, `current_step`, timestamps por etapa).
- Roles y permisos de lavandería en migraciones propias; sin permisos de "editar/borrar órdenes" para Recepcionista.

### Controladores
- `OrdenLavanderiaController`: index, store, show, avanzar, marcarEntregada, dashboard, escanearQR.
- Sin `update` ni `destroy` para órdenes → Recepcionista no puede editar/borrar por diseño.

### Permisos y rutas
- Rutas bajo `lavanderia/ordenes` con `middleware('role:...')` según acción (Recepcionista: index, store, entregar; Operador: escanear-qr, avanzar; todos: dashboard, show).
- Sidebar y rutas Vue alineadas con permisos `lavanderia.recepcion`, `lavanderia.operador`, `lavanderia.dashboard`, `lavanderia.consultar`.

### Repositories / Stores
- `OrdenLavanderiaRepository` (Eloquent) con `getPendientesEnProceso`, `paginate` con filtro `current_step`.
- Front: `OrdenLavanderiaRepository.js` con createOrden, avanzarOrden, marcarEntregada, getDashboardResumen, escanearQR, escanearFolio.
- No hay helpers PHP específicos de lavandería; lógica en Service y Repository.

### Vistas JS
- Recepción: formulario + modal dos tickets (cliente con QR + interno) + estilos print.
- Operador: por área (lavado/secado/planchado), botones grandes, instrucciones visibles.
- Escanear: entrada manual código/folio, instrucciones en destacado; sin cámara.
- Dashboard: semáforo (verde/amarillo/rojo), tiempo en etapa; sin auto-refresh ni PWA.

### Notificaciones
- Orden creada → correo al dueño (folio + total).
- Orden terminada → correo al cliente (opcional si tiene email) y correo al dueño (`OrdenLavanderiaTerminadaAdmin`).

---

## Acciones recomendadas

1. **Escaneo con cámara (Paso 3)**  
   En `escanear.vue`, integrar un lector de QR por cámara (p. ej. `html5-qrcode` o `vue-qrcode-reader`) y rellenar el campo de código/folio con el valor leído para mantener el flujo actual de escanear → mostrar instrucciones → avanzar.

3. **Dashboard en tiempo real (Paso 4)**  
   Activar polling periódico (ej. `setInterval(cargarDatos, 15000)`) en `dashboard.vue` o, si se desea verdadero tiempo real, implementar Broadcasting (WebSockets) cuando se llame a `avanzar`/`marcarEntregada`.

3. **PWA (Paso 4)**  
   Añadir `manifest.json` y, si se desea soporte offline, un service worker (p. ej. con Vite PWA plugin) y meta viewport adecuada para uso en móvil como app instalable.
