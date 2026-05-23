# Equipo de agentes: migración POS de “tipo de prenda” a catálogo por producto

Documento para coordinar **varios agentes** (o iteraciones en un solo chat) al rediseñar la lógica de negocio del POS de lavandería: **dejar de usar tipo de prenda / matriz servicio×prenda** y **manejar líneas por producto de catálogo** (categorías, precios, opcionalmente stock).

**Contexto real del repo (Laravel + Vue 3):**

- Líneas de orden: `OrdenLavanderiaDetalle` con `service_type_id`, `garment_type_id`, `load_type_id`, `billing_mode`, snapshots de precio (`app/Models/OrdenLavanderiaDetalle.php`).
- Catálogo POS hoy: `LavanderiaPosCatalogController` expone `garment_types` + `laundry_piece_prices` + tipos de servicio/carga/prioridad.
- Inventario almacén: tablas `inventory_*` / `InventoryProduct` con categorías propias — **hoy está separado** del ticket TIBU (ver `.cursorrules`).

**Documento donde vive la lógica de negocio descrita para el equipo / otras IAs:** [`docs/ANALISIS_LOGICA_NEGOCIO.md`](ANALISIS_LOGICA_NEGOCIO.md). Ese archivo es la **fuente narrativa** de rutas, permisos, modelos, frontend y flujos; **debe leerse antes** de ejecutar las fases de migración. La primera versión de este archivo de prompts **no lo citaba por nombre**; queda corregido aquí.

| Sección en `ANALISIS_LOGICA_NEGOCIO.md` | Qué toca la migración POS → producto |
|----------------------------------------|--------------------------------------|
| §1 Resumen | Sustituir la frase “matriz servicio × prenda × carga” por catálogo de productos; mantener QR/folio, áreas, caja, inventario almacén como conceptos. |
| §2.1 Rutas API | Sustituir o versionar `GET .../pos/catalog`, `.../pos-catalog`, `preview-totals`; admin `garment-types`; payload de `POST /lavanderia/ordenes`. |
| §2.4 Modelos | `OrdenLavanderiaDetalle` deja de depender de `garment_type_id` en líneas nuevas; `LaundryPiecePrice` / matriz C2 deprecada según ADR. |
| §2.5 Controladores y servicios | `OrdenLavanderiaController`, `OrdenLavanderiaService`, `LaundryPricingService`, `LavanderiaPosCatalogController`, validadores asociados. |
| §3.2 Router, §3.4 Menú, §3.5 Repos | POS (`/ventas/pos`), admin `garment-types`, `GarmentTypesAdminRepository` → CRUD de productos/categorías POS. |
| §4 Flujo 2 (Venta POS) | Reescribir pasos: carrito por producto de catálogo, preview y creación con snapshots coherentes. |
| §6 BD | Nuevas tablas catálogo POS; relación con `orden_lavanderia_detalles`; histórico `garment_types` si aplica. |
| §7 Notas para adaptación | **Crítico:** el análisis dice explícitamente no mezclar `inventory_*` con líneas del ticket TIBU; el ADR debe **afirmar** si eso sigue igual (catálogo POS propio) o documentar una **excepción** (p. ej. FK opcional y movimientos solo cuando el negocio lo exija). |

Complemento de datos y campos por etapa: `docs/ESTRUCTURA_DATOS_CRITICA.md`. Claves y límites: `config/lavanderia.php`. Convenciones de agente: `.cursorrules`.

Este documento sirve como **guía de prompts** (fases o roles) y como **mandato de libertad**: según el bloque que pegues (§4 o §4 B), el agente puede tener **alcance completo** de implementación.

---

## 0. Libertad operativa del agente (mandato “hacer todo”)

Cuando el humano indique **libertad total** o pegue el **§4 B**, el agente **no** está limitado a un solo rol ni a “solo ADR sin código”. Puede:

- Tocar **todo el stack** necesario: migraciones, modelos, seeders, `app/Services/*`, controladores, `routes/api.php`, `FormRequest`, policies, **Vue** (POS, admin, router, sidebar), repositorios JS, i18n, `ticketPosPdf.js`, **tests** (`tests/Feature`, `tests/Unit`), y **documentación** (`docs/*`, `.cursorrules`).
- **Crear o actualizar** el ADR en `docs/` en el mismo flujo que el código (no hace falta esperar otro chat).
- **Eliminar o sustituir** código y rutas legacy de `garment_types` / matriz C2 cuando el reemplazo esté cubierto por tests o por decisión documentada en el ADR.
- Ejecutar comandos razonables: `php artisan migrate`, `php artisan test`, `npm run build` / `npm run dev` según haga falta para verificar (evitar acciones destructivas masivas en producción sin que el humano lo pida explícitamente).

**Invariantes** (sí o sí hay que preservar el comportamiento existente salvo que el humano autorice un cambio mayor):

- Identidad de orden: **folio único** y **QR**; estados de orden coherentes.
- Flujo por **áreas**, escaneos, permisos `area.*`, asignación de operador.
- **Caja** abierta para vender, reglas de cancelación y reportes que ya existan.
- Si mezclas venta POS con **stock de almacén**, deja la decisión y el riesgo **explícitos** en el ADR (coherente con `ANALISIS_LOGICA_NEGOCIO.md` §7).

Si el humano contradice una regla de este doc (p. ej. ramas Git), **prima la instrucción más reciente del humano**.

---

## 1. Objetivo de negocio (definición corta)

| Antes | Después |
|--------|---------|
| Cobro por combinación **servicio × tipo de prenda × (tipo de carga)** vía `laundry_piece_prices` y defaults en `garment_types`. | Cobro por **ítem de catálogo** (producto vendible) con **categoría**, precio vigente o reglas claras, y **snapshot en la línea** al vender. |
| UI y API hablan de “prenda”. | UI y API hablan de **producto / categoría**; “prenda” solo como etiqueta legacy si aún hay datos históricos. |

**No tocar sin decisión explícita:** flujo por **áreas**, QR/folio, caja, permisos, notificaciones, tracking público.

---

## 2. Decisiones que un agente “Arquitecto” debe cerrar (ADR)

El ADR de arquitectura está en **`docs/ADR-001-pos-catalogo-productos.md`**. Revisiones mayores futuras pueden numerarse ADR-002, etc. El borrador inicial lo cerró la fase 0; cámbialo solo con acuerdo explícito.

1. **Un solo catálogo o dos dominios**
   - **Opción A — Catálogo POS propio** (`laundry_sale_products` o nombre acordado): FK opcional a `inventory_products` solo si ese producto descuenta stock de almacén al venderse en recepción.
   - **Opción B — Reutilizar `inventory_products`** con flags (`sellable_at_pos`, precio POS, unidad de cobro): menos tablas, más acoplamiento operativo (almacén vs recepción).
2. **Qué queda de TIBU “transversal”**
   - ¿Se mantienen `service_types`, `load_types`, `priority_levels` como atributos de **cabecera** o de **línea**?
   - Recomendación típica: **cabecera** = prioridad, envío; **línea** = producto + cantidad + modo cobro (pieza/kg) + observaciones; **carga** solo si sigue afectando rutas operativas (áreas).
3. **Migración de datos históricos**
   - Mapeo `garment_type_id` + `service_type_id` → producto “sintético” de transición, o líneas legacy solo lectura.
4. **Deprecación**
   - Rutas admin de `GarmentType`, seeders `LaundryPiecePrice`, campos en API: marcar deprecated, fechas de eliminación.

En **modo por fases**, los prompts §5.2 en adelante asumen ADR listo; en **libertad total** (§4 B / §0) el agente **crea el ADR y el código** sin esperar otro hilo.

---

## 3. Reparto de agentes (orden sugerido)

| Orden | Rol | Entrega |
|-------|-----|---------|
| 0 | **Arquitecto / ADR** | ADR + diagrama de entidades + lista de rutas/tablas a tocar. |
| 1 | **Backend datos** | Migraciones, modelos, factories, seeders de catálogo producto/categoría. |
| 2 | **Backend dominio** | Servicios de precio, validación de líneas, creación de orden, preview totals; eliminar dependencia de `GarmentType` / `LaundryPiecePrice` en flujo nuevo. |
| 3 | **API** | `FormRequest`, controladores, `routes/api.php`, respuestas JSON estables. |
| 4 | **Frontend POS** | `pos.vue`, repositorios, PDF ticket, textos i18n. |
| 5 | **Admin catálogo** | CRUD categorías/productos, permisos Spatie. |
| 6 | **QA** | Tests Feature reemplazando o extendiendo `LaundryPricingC2Test`, `OrdenLavanderiaC1LaundryLineTest`, etc. |
| 7 | **Documentación** | Actualizar `docs/ANALISIS_LOGICA_NEGOCIO.md`, `docs/ESTRUCTURA_DATOS_CRITICA.md`, `.cursorrules` sección dominio. |
| 8 | **Cierre integración** | Reportes (`ventas/mensual` + CSV), checklist §6 pendientes razonables, suite C8, ADR/changelog. |

**Rama Git:** toda la migración va en **una sola rama** acordada al inicio (en tu caso puede ser **`main`**). Cada fase o chat distinto **no** abre otra rama: haces `git checkout` a esa rama y acumulas **commits pequeños** ahí. Si en otro momento prefieres flujo con PR, la misma regla aplica: una sola rama de trabajo hasta merge, no ramas por rol.

Trabajar **por commits pequeños** dentro de esa misma rama (backend catálogo → API → front → tests) para poder revertir por commit si hace falta. En `main`, conviene hacer `git pull` antes de cada sesión para partir actualizado.

---

## 4. Prompt maestro (pegar al abrir un hilo nuevo)

**Cómo copiar y pegar en Cursor (clic, atajos, qué incluir):** ver **§8.0** más abajo.

Usa **§4** para trabajo por fases/rol. Usa **§4 B** cuando quieras **libertad total** en un solo hilo (ver también **§0**).

### §4 — Prompt maestro (modo por fases / contexto compartido)

```text
Estás en el repo ControlLavado (Laravel 10+, Vue 3, Sanctum, Spatie Permission).

Misión: migrar el POS de lavandería para que las líneas de venta se basen en CATÁLOGO DE PRODUCTOS (con categorías, precios en snapshot) y dejar de usar tipo de prenda / garment_types / laundry_piece_prices en el flujo nuevo.

Git (obligatorio salvo otra instrucción del humano):
- Trabaja **solo** en la rama acordada (p. ej. **`main`**). Antes de editar: `git checkout main` (o la rama indicada), `git pull` si aplica, y confirma con `git branch --show-current`.
- **No** crees ramas nuevas por fase ni por “rol”. Todos los commits van a **esa misma rama**.

Restricciones:
- No romper: folio/QR único por orden, estados de orden, flujo por áreas y escaneos, caja abierta para vender, inventario almacén (inventory_*) salvo que el ADR explícitamente una POS con stock vía FK.
- Backend: controladores delgados, lógica en Services + FormRequest; PHP snake_case en BD, inglés en nombres de clases.
- Front: repositorios JS, i18n en resources/js/src/locales, Axios central.

Antes de editar, lee en orden: docs/ANALISIS_LOGICA_NEGOCIO.md (§2.1, 2.4, 2.5, 3, 4, 7), docs/PROMPTS_EQUIPO_MIGRACION_PRODUCTOS_POS.md (§0 libertad si aplica) y docs/ADR-001-pos-catalogo-productos.md. Si el código diverge del análisis, prima el código y actualiza el análisis en el mismo cambio (como indica §8 de ese documento).

Indica qué archivos vas a tocar y ejecuta tests relevantes al final.
```

### §4 B — Prompt maestro **libertad total** (un agente, todo el alcance)

Pega esto cuando quieras **un solo agente** con permiso explícito de **implementar de punta a punta** sin detenerse en “solo arquitecto” ni “solo backend”:

```text
Estás en el repo ControlLavado (Laravel 10+, Vue 3, Sanctum, Spatie Permission).

MANDATO: LIBERTAD TOTAL para completar la migración POS → venta por **catálogo de productos** (categorías, productos, precios en snapshot en líneas). Objetivo: el flujo nuevo **no** dependa de tipo de prenda / garment_types / laundry_piece_prices salvo compatibilidad mínima documentada para datos históricos.

Puedes hacer **todo** lo necesario en este mismo hilo:
- Backend completo (migraciones, modelos, servicios, validadores, rutas API, permisos, deprecación o sustitución de endpoints viejos).
- Frontend completo (POS, admin catálogo, router, sidebar, repos JS, i18n, PDF ticket).
- Tests (crear/actualizar Feature y Unit hasta cobertura razonable del flujo).
- Documentación: ADR en docs/, actualizar ANALISIS_LOGICA_NEGOCIO.md, .cursorrules y este archivo si hace falta.

Lee docs/ANALISIS_LOGICA_NEGOCIO.md (§2.1, 2.4, 2.5, 3, 4, 7) y docs/PROMPTS_EQUIPO_MIGRACION_PRODUCTOS_POS.md §0 y §2. Si no existe ADR, créalo y sigue codificando.

Git: rama acordada con el humano (p. ej. `main`); `git checkout` + `git pull` al inicio. No pidas permiso para cada archivo: planifica, ejecuta, commitea en pasos lógicos.

Invariantes: conservar folio/QR por orden, estados de orden, flujo por áreas y caja; cualquier acoplamiento inventario POS ↔ almacén debe quedar explícito en el ADR.

Al final: resume cambios, rutas API nuevas/cambiadas, y ejecuta la batería de tests que corresponda al proyecto.
```

---

## 5. Prompts por rol (copiar / pegar)

### 5.1 Agente Arquitecto

```text
Rol: Arquitecto de dominio.

Tarea: Redactar o actualizar docs/ADR-001-pos-catalogo-productos.md (o ADR-n siguiente) con:
- Modelo objetivo de entidades (ProductoVenta, Categoría, línea de orden).
- Decisión A vs B (catálogo POS propio vs extender inventory_products).
- Estrategia de migración desde orden_lavanderia_detalles con garment_type_id.
- Lista de endpoints a deprecar y los nuevos contratos JSON del POS.

Salida por defecto: ADR + diagrama Mermaid en el mismo archivo. Si el humano pidió **libertad total** (§0 / §4 B), entonces ADR **y** implementación en el mismo flujo (no te detengas en “solo documento”).
```

### 5.2 Agente Backend — migraciones y modelos

```text
Rol: Backend Laravel — capa datos.

Según el ADR en docs/ADR-001-pos-catalogo-productos.md:
- Crea migraciones para categorías y productos vendibles en POS (o extiende inventory_* si el ADR lo mandata).
- Añade FK nullable en orden_lavanderia_detalles hacia el nuevo producto; mantén garment_type_id nullable para histórico hasta migración de datos.
- Modelos Eloquent con relaciones y $fillable alineados al estilo existente.

Si trabajas en **modo por fases**, no cambies aún el POS Vue; prioriza migraciones reversibles y seeders idempotentes. En **libertad total** (§4 B), avanza también el front cuando el API y el modelo estén listos.
```

### 5.3 Agente Backend — precios y órdenes

```text
Rol: Backend Laravel — dominio venta.

Reemplaza la lógica que hoy usa LaundryPricingService / LaundryPiecePrice / GarmentType para líneas NUEVAS:
- Precio unitario resuelto desde el catálogo de producto (y reglas acordadas en ADR, p. ej. precio por kg si billing por peso).
- OrdenLavanderiaDetalleLineValidator y OrdenLavanderiaService deben aceptar items con producto de catálogo y rechazar dependencia de garment_type en flujo nuevo.
- LaundryPosPreviewTotalsService debe calcular igual que la persistencia.

Mantén tests verdes o actualiza tests con datos del nuevo catálogo.
```

### 5.4 Agente API

```text
Rol: API Laravel.

- Actualiza LavanderiaPosCatalogController (o reemplázalo) para devolver categorías + productos activos + reglas de precio necesarias al front; elimina garment_types del payload nuevo (o déjalo solo si ADR pide convivencia temporal).
- FormRequest para store orden y preview totals: validar product_id del catálogo nuevo, cantidad, billing_mode, weight_kg cuando aplique.
- Documenta en comentario junto a rutas en routes/api.php cualquier breaking change.

Incluye ejemplo JSON request/response en comentario PHPDoc del controlador principal del catálogo POS.
```

### 5.5 Agente Frontend — POS

```text
Rol: Vue 3 — POS recepción.

Archivos típicos: resources/js/src/views/ventas/pos.vue, repositorios, ticketPosPdf.js, locales.

Elimina selección por “tipo de prenda”; implementa selección por categoría → producto, carrito con mismos campos que espera el backend nuevo.
Mantén prioridad, observaciones por línea, preview de totales vía endpoint existente o renombrado según API.

Usa i18n para todas las cadenas nuevas; no hardcodees español en plantillas salvo que el proyecto ya lo haga en ese archivo.
```

### 5.6 Agente Frontend — administración

```text
Rol: Vue 3 — administración.

CRUD de categorías y productos del catálogo POS (lista, filtros, activar/desactivar, orden).
Permisos: alinear con convención existente (Spatie); rutas y sidebar como garment-types.vue hasta reemplazar esa vista.

Entrega: vistas + repositorio JS + entradas de router y sidebar coherente con el resto del admin.
```

### 5.7 Agente QA

```text
Rol: QA automatizado Laravel.

Actualiza o crea tests Feature para:
- Catálogo POS devuelve estructura nueva.
- Crear orden con líneas solo por producto de catálogo; totales y snapshots correctos.
- Rechazo de payload legacy si el proyecto decide cortar compatibilidad (según ADR).

Ejecuta php artisan test filtrando por los archivos tocados; corrige hasta verde.
```

### 5.8 Agente Documentación / `.cursorrules`

```text
Rol: Documentación.

Actualiza docs/ANALISIS_LOGICA_NEGOCIO.md y la sección de dominio en .cursorrules para reflejar “venta por catálogo de productos” en lugar de matriz servicio×prenda.

Menciona explícitamente qué tablas son canónicas y cuáles legacy en solo lectura.
```

### 5.9 Agente Cierre integración (fase 8)

```text
Rol: Cierre de migración POS — integración y reportes.

- Alinear reportes con ADR-001: ventas mensuales y/o CSV deben exponer volumen de líneas con catálogo POS (`laundry_sale_product_id`) o dejar el mapeo explícito en docs/ANALISIS_LOGICA_NEGOCIO.md.
- Añadir o extender tests Feature donde aplique; incluir el archivo en la suite `TibuC8Regression` de phpunit.xml si es regresión crítica de negocio.
- Actualizar checklist §6 de este documento, ADR-001 §8 y docs/CHANGELOG_INTERNO_TIBU.md.
- Ejecutar `composer run test:tibu-c8` hasta verde.
```

---

## 6. Checklist de integración (humano o agente “orquestador”)

- [ ] Toda la migración en **una rama** acordada (p. ej. `main`; sin ramas paralelas por fase salvo decisión explícita del equipo).
- [ ] ADR en `docs/` (`ADR-001-pos-catalogo-productos.md`) revisado y vigente para las fases siguientes.
- [ ] Migraciones aplican en entorno limpio (`php artisan migrate:fresh --seed` o flujo del proyecto).
- [ ] POST `/api/lavanderia/ordenes` y preview totals alineados.
- [x] POS carga catálogo nuevo: la **venta por producto POS** no usa `garment_types`; la UI puede conservar flujo **TIBU** (prenda/matriz) en paralelo hasta deprecación explícita.
- [x] Ticket PDF y reimpresión muestran nombre de producto y categoría si aplica (checklist manual §1b / C8.5 en `docs/CHECKLIST_QA_VENTA_CAJA_TICKET_TIBU.md`; código: `laundry_sale_product` en ticket cliente y servidor).
- [x] Reportes CSV / ventas mensuales: CSV `export-csv` → `laundry_sale_lines_count` por orden; `GET /api/ventas/mensual` → **`lineas_catalogo_pos`** por mes (fase 8); ver `ANALISIS_LOGICA_NEGOCIO.md` §2.1.
- [x] Permisos admin y políticas: sin cambios en fase 8; convención vigente (`administracion.areas` para catálogo POS, `reportes.ver` para mensual) documentada en `ANALISIS_LOGICA_NEGOCIO.md` §2.1–§2.3.
- [x] `docs/ANALISIS_LOGICA_NEGOCIO.md` actualizado (resumen §1, rutas §2.1, modelos §2.4, servicios §2.5, frontend §3, flujos §4, tablas §6, notas §7, §8) para reflejar venta por catálogo de productos (fase 5 documentación).
- [x] **Fase 7 (§5.8):** §6.1 en ANALISIS + bloque «Tablas: canónico vs legacy» en `.cursorrules` (matriz TIBU vs `laundry_sale_*` vs `productos` / histórico); ADR §8.
- [x] **Fase 8 (cierre integración):** `lineas_catalogo_pos` en ventas mensuales + UI + test C8; ADR §8 / changelog.
- [x] `.cursorrules` y `docs/ESTRUCTURA_DATOS_CRITICA.md` alineados con catálogo POS (`laundry_sale_*`) y ADR-001.

---

## 7. Riesgos y mitigación

| Riesgo | Mitigación |
|--------|------------|
| Romper órdenes antiguas en reportes | Mantener columnas legacy + vistas o accessors que etiqueten “Producto (hist.: prenda X)”. |
| Duplicar concepto de “producto” con `productos` legacy | Nombres claros en BD y en UI; ADR nombra la tabla canónica. |
| Mezclar stock almacén con servicio de lavado | FK opcional y movimientos solo si el negocio lo pide; si no, catálogo POS sin stock. |

---

## 8. Cómo ejecutar estos prompts en Cursor

No son comandos de terminal: son **texto que pegas en el chat del agente** (Composer o panel de chat) para que la IA trabaje con contexto y tareas acotadas.

### 8.0 Instrucciones: cómo pegar los prompts (paso a paso)

1. **Abre este mismo archivo** en Cursor: `docs/PROMPTS_EQUIPO_MIGRACION_PRODUCTOS_POS.md`.
2. **Baja hasta la sección que necesites:**
   - **§4** (modo por fases) o **§4 B** (libertad total), o el rol **§5.1** … **§5.9**.
3. Verás un bloque delimitado por una línea que dice exactamente ` ```text ` al inicio y una línea que solo dice ` ``` ` al final (cierre del bloque de código en Markdown).
4. **Selecciona solo el cuerpo del prompt:** todo lo que está **entre** esas dos líneas (sin incluir la línea ` ```text ` ni la línea final ` ``` `). Ese es el texto que debe ir al chat.
5. **Copia** con `Ctrl+C` (Windows/Linux) o `Cmd+C` (Mac).
6. **Abre el chat del agente en Cursor:**
   - Atajo habitual: `Ctrl+L` (abre el panel de chat).
   - O el ícono de **Chat** en la barra lateral.
7. **Activa el modo Agent** (no “Ask” solo lectura), para que pueda editar archivos y ejecutar comandos si lo necesita.
8. **Opcional pero útil:** en la primera línea del mensaje escribe referencias con `@`, por ejemplo:
   - `@docs/ANALISIS_LOGICA_NEGOCIO.md`
   - `@docs/PROMPTS_EQUIPO_MIGRACION_PRODUCTOS_POS.md`  
   Luego **Enter** dos veces y **pega** el prompt debajo (`Ctrl+V` / `Cmd+V`).
9. **En la misma burbuja** puedes añadir una frase tuya al final, por ejemplo: *“Rama `main`, `git pull` al inicio.”* o *“Libertad total, no pares entre fases.”*
10. **Envía el mensaje** (botón enviar o `Enter` según tu configuración de Cursor; si Enter inserta salto, usa el botón de enviar).

**Modo por fases (dos bloques en un solo mensaje):** pega primero todo el **§4**, luego en la misma burbuja pega el **§5.x** que corresponda (p. ej. §5.2) y envía **un solo mensaje**.

**Modo libertad total:** pega **solo** el bloque **§4 B** y envía; no hace falta pegar §5 si no quieres dividir por roles.

**Nuevo chat en otra sesión:** repite los pasos 4–10; Cursor **no** recuerda el chat anterior salvo que pegues un resumen o uses `@` en archivos modificados.

### 8.1 Preparación (una vez)

1. Define la rama única de trabajo. Si usas **`main`**: `git checkout main` y `git pull`. (Si más adelante usas otra rama, el criterio es el mismo: **todas** las fases y chats en **esa** rama; no una rama por rol.)
2. Opcional: en el chat usa `@docs/ANALISIS_LOGICA_NEGOCIO.md` y `@docs/PROMPTS_EQUIPO_MIGRACION_PRODUCTOS_POS.md` para adjuntar contexto.

### 8.2 Modo recomendado: Agent (puede editar y probar)

1. Abre el **chat de Cursor** en modo **Agent** (no solo Ask), para permitir ediciones y `php artisan test` si lo pides.
2. En un **chat nuevo**, pega uno de:
   - **§4** + rol **§5.x** si quieres trabajo **por fases**; o
   - Solo **§4 B** si quieres **libertad total** (un agente hace backend + front + tests + docs en la medida necesaria).
3. Con **§4** por fases, añade la rama y si deben parar, p. ej.: *“Rama `main`… termina solo esta fase; espera mi OK.”* Con **§4 B**, puedes decir: *“No pares entre fases; entrega la migración usable con tests en verde.”*

### 8.3 Orden de ejecución (una fila = una conversación o un mensaje)

| Orden | Contenido a pegar |
|-------|-------------------|
| 1 | Maestro + **§5.1** Arquitecto → salida: `docs/ADR-001-pos-catalogo-productos.md` (fase 0 ya entregada) |
| 2 | Maestro + **§5.2** Backend datos (migraciones) |
| 3 | Maestro + **§5.3** Backend dominio (precios, órdenes) |
| 4 | Maestro + **§5.4** API |
| 5 | Maestro + **§5.5** POS Vue (y otro chat **§5.6** admin si quieres separar) |
| 6 | Maestro + **§5.7** QA |
| 7 | Maestro + **§5.8** Documentación |
| 8 | Maestro + **§5.9** Cierre integración (reportes + checklist + C8) |

Entre pasos revisa cambios y haz **commit en la misma rama**; si abres **otro chat**, pega de nuevo el prompt maestro (incluye la regla Git de la rama única) + el rol siguiente, para que el agente no cree una rama nueva por costumbre.

En el primer mensaje del chat nuevo puedes añadir: *“Continuación de migración POS: todo en `main` (últimos commits: …); no cambies de rama.”*

### 8.4 Un solo chat largo (alternativa)

- **Por fases:** pega **§4** y escribe: *“Sigue el §3 en orden 0→8; detente al final de cada fase.”*
- **Sin frenos:** pega **§4 B** (libertad total); el agente no está obligado a parar entre arquitecto/backend/front.

### 8.5 “Equipo de agentes”

En la práctica son **varias conversaciones** (o una secuencia con pausas), no un botón mágico: cada chat = un rol o una fase. **La rama Git es siempre la misma** en todos esos chats; solo cambia el prompt del rol (§5.x).

### 8.6 Si se desvía

Acota con rutas o archivos: *“Solo `LaundryPricingService` y tests `LaundryPricing*Test` en esta respuesta.”* Corrige con `@routes/api.php` o el §2.1 de `ANALISIS_LOGICA_NEGOCIO.md`.

---

*Última alineación con código y documentación: `docs/ANALISIS_LOGICA_NEGOCIO.md` (abril 2026); catálogo POS en `app/Http/Controllers/LavanderiaPosCatalogController.php`; líneas en `app/Models/OrdenLavanderiaDetalle.php`; inventario en `app/Models/InventoryProduct.php`.*
