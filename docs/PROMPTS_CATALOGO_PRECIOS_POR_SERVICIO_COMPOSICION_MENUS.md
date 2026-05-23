# Equipo de agentes: precios por tipo de servicio, tipos de servicio extensibles, composición del catálogo y menús

Documento complementario a **[`docs/PROMPTS_EQUIPO_MIGRACION_PRODUCTOS_POS.md`](PROMPTS_EQUIPO_MIGRACION_PRODUCTOS_POS.md)**. Úsalo cuando el catálogo POS por producto **ya existe o está en curso** y se necesita:

- **Precios distintos por producto según tipo de servicio** (y reglas de resolución / snapshot en línea).
- **Alta y mantenimiento de tipos de servicio** (admin, permisos, sin romper histórico).
- **Composición y reordenamiento del catálogo** (orden de categorías y productos, visibilidad en POS, secciones opcionales).
- **Navegación**: router, sidebar, i18n para las nuevas pantallas.
- **Interfaz admin del producto**: formulario con **filas dinámicas** precio × tipo de servicio (no solo API ni “matriz” sin pantalla).

**Leer antes:** [`docs/ANALISIS_LOGICA_NEGOCIO.md`](ANALISIS_LOGICA_NEGOCIO.md), [`docs/ESTRUCTURA_DATOS_CRITICA.md`](ESTRUCTURA_DATOS_CRITICA.md), ADR de catálogo POS ([`docs/ADR-001-pos-catalogo-productos.md`](ADR-001-pos-catalogo-productos.md)). Convenciones de agente: `.cursorrules`.

**Git (alineado con el doc de migración POS):** una sola rama de trabajo acordada (p. ej. `main`); `git pull` al iniciar sesión; no abrir rama nueva por rol salvo instrucción explícita del humano.

**Invariantes** (salvo decisión nueva en ADR):

- Folio/QR, estados de orden, áreas, caja, permisos existentes.
- No mezclar `inventory_*` con ticket TIBU sin decisión documentada.

---

## 0. Libertad operativa (opcional)

Si el humano pega **§4 B** de este archivo, el agente puede tocar migraciones, modelos, servicios, API, Vue (POS + admin), router, sidebar, i18n, tests y `docs/` (nuevo ADR si hace falta) en el mismo hilo, con commits pequeños.

---

## 1. Objetivo de negocio (definición corta)

| Dimensión | Objetivo |
|-----------|----------|
| Precio | Un producto de catálogo puede tener **precio distinto por `service_type`** (y reglas claras si falta combinación). |
| Tipos de servicio | **Catálogo administrable** de tipos de servicio (crear, editar, desactivar; evitar borrado en duro si hay FK histórica). |
| Catálogo en UI | **Orden** y **visibilidad** configurables; opcionalmente **secciones** del catálogo en POS según ADR. |
| Menús | Rutas y entradas de menú **coherentes** con permisos y el patrón actual del proyecto. |
| UI admin producto | En crear/editar producto del **catálogo POS**, bloque visible **“Precios por servicio”** con filas agregables/eliminables; guardado y errores **sin Postman**. |

### 1.1 Por qué faltaba “la parte visual” en los prompts iniciales

- Los textos hablaban de **“matriz”** o **“panel en lote”** de forma genérica: un agente puede implementar **solo backend** o una pantalla secundaria y dar por cumplido el requisito.
- No se fijó **dónde** vive la UX: el flujo habitual es el **modal (o panel) de producto** — en este repo: `resources/js/src/views/admin/laundry-pos-catalog.vue` (`#modalLaundryProd`), no la pantalla legacy **Catálogos / Productos** (nombre + stock, ventas históricas).
- Hoy el admin POS ya tiene `unit_price` y defaults de servicio/carga, pero **no** lista N precios por N servicios en UI; eso hay que **pedirlo como criterio de aceptación explícito** (filas dinámicas, validación, i18n).

---

## 2. Decisiones que un agente “Arquitecto” debe cerrar (ADR sugerido)

Antes de implementar en grande, conviene un **`docs/ADR-00X-precios-producto-por-servicio.md`** (número libre) que cierre:

1. **Modelo de datos**: tabla pivote o matriz `product_id` + `service_type_id` + monto (+ vigencia opcional); o alternativa justificada.
2. **Resolución de precio**: orden de fallback, error si falta precio, interacción con precio “base” del producto si existe.
3. **Ámbito del servicio**: cabecera de orden vs por línea (coherente con el análisis de negocio actual).
4. **Catálogo “componible”**: solo `sort_order` + `visible_in_pos` vs tablas de secciones; impacto en API `pos/catalog`.
5. **Migración**: datos actuales con un solo precio → rellenar matriz por servicio default o script único.
6. **Contrato UI ↔ API** para el formulario de producto: forma exacta del JSON en GET (p. ej. `service_prices: [{ service_type_id, amount }]`) y en PUT/POST; cómo se muestra `unit_price` si sigue existiendo (etiqueta “precio base”, tooltip, o solo lectura).

En modo **solo arquitecto**, el entregable es ADR + lista de rutas/tablas + **boceto del payload del modal de producto**; el código puede ir en otro hilo pegando **§5.2** en adelante.

---

## 3. Reparto de agentes (orden sugerido)

| Orden | Rol | Contenido a pegar |
|-------|-----|-------------------|
| 1 | Arquitecto / ADR | **§5.1** |
| 2 | Backend datos + dominio | **§5.2** |
| 3 | Frontend POS | **§5.3** |
| 4 | Admin catálogo + **precios por servicio en UI** | **§5.4** (obligatorio leer criterios visuales) |
| 4b | Solo UI del modal de producto (backend ya listo) | **§5.8** |
| 5 | Router, sidebar, i18n | **§5.5** |
| 6 | QA | **§5.6** |

Alternativa: un solo mensaje con **§4 B** (libertad total).

---

## 4. Prompt maestro (contexto compartido — pegar con cualquier §5.x)

### §4 — Prompt maestro (modo por fases)

```text
Estás en el repo ControlLavado (Laravel 10+, Vue 3, Sanctum, Spatie Permission).

Misión de esta fase: extender el dominio del catálogo POS y del POS para soportar precios por tipo de servicio por producto, administración de tipos de servicio, composición/reordenamiento del catálogo, entradas de menú/router coherentes, y **admin Vue con formulario dinámico** (filas precio×servicio en el modal de producto, no solo endpoints).

Git:
- Trabaja solo en la rama acordada (p. ej. main). Antes: git checkout <rama>, git pull si aplica, git branch --show-current.
- No crees ramas nuevas por rol sin instrucción del humano.

Restricciones:
- No romper: folio/QR, estados, áreas, caja, permisos salvo extensión documentada.
- No mezclar inventory_* con ticket sin ADR explícito.
- Backend: Services + FormRequest; PHP snake_case en BD; inglés en nombres de clases.
- Front: repositorios JS, locales en resources/js/src/locales.

Contexto de documentación: lee o asume alineado docs/ANALISIS_LOGICA_NEGOCIO.md y docs/PROMPTS_CATALOGO_PRECIOS_POR_SERVICIO_COMPOSICION_MENUS.md. Si el humano pegó un §5.x debajo, ejecuta solo ese rol y entrega tests cuando el rol lo pida.
```

### §4 B — Libertad total (un solo hilo)

```text
Estás en ControlLavado (Laravel + Vue 3).

Libertad total: implementa de punta a punta precios por producto según tipo de servicio, CRUD/admin de tipos de servicio (con policies y permisos), resolución de precio en preview-totals y al crear orden con snapshots coherentes, reordenamiento y visibilidad del catálogo (y secciones solo si las justificas en un ADR breve), **vista admin del catálogo POS con sección dinámica “Precios por servicio” en crear/editar producto** (`laundry-pos-catalog.vue` o equivalente), actualización de router/sidebar/i18n, y tests Feature que cubran los casos felices y el caso “falta precio” según la regla que documentes.

Rama única acordada; commits pequeños; no mezclar inventory_* sin ADR; no romper caja/QR/áreas.

Entrega: código + migraciones + tests en verde + docs/ADR si hay decisión nueva.
```

---

## 5. Prompts por rol (copiar el bloque `text` completo del rol)

### §5.1 — Arquitecto / ADR (precio × servicio y catálogo componible)

```text
Rol: Arquitecto (solo decisiones y ADR salvo que el humano pida código).

Repo: ControlLavado. Ya existe o está planificado el catálogo POS por producto/categoría (ver ADR-001 y ANALISIS_LOGICA_NEGOCIO.md).

Misión:
1) Proponer modelo de datos para precio por par (producto de venta POS, tipo de servicio): tablas, índices únicos, soft-delete o activo/inactivo.
2) Definir regla cuando no exista precio para (producto, servicio): error vs fallback vs precio base.
3) Definir si el tipo de servicio vive en cabecera de orden, en línea, o ambos con prioridad clara.
4) Definir “composición” mínima del catálogo: sort_order categoría/producto, visible_in_pos; opcional secciones y su API.
5) Escribir docs/ADR-00X-... (nombre descriptivo) + diagrama mermaid o lista entidad-relación.
6) Listar rutas API y archivos PHP/Vue más probables a tocar en implementación posterior.
7) Especificar contrato JSON del modal de producto (GET + POST/PUT) para la lista dinámica de precios por `service_type_id`.

No implementes migraciones ni Vue en este mensaje salvo que el humano diga explícitamente que continúes con código.
```

### §5.2 — Backend (tipos de servicio + matriz precio + servicios de orden)

```text
Rol: Backend datos y dominio.

Implementar:
1) Tipos de servicio administrables: reutilizar tabla existente si ya hay service_types; API admin (list/create/update/deactivate), Policy, permisos Spatie, FormRequest. Evitar DELETE físico si hay órdenes históricas.
2) Tabla/modelo de precios por (laundry_sale_product_id o nombre canónico del ADR, service_type_id) con validaciones y factories mínimas.
3) LaundryPricingService (o equivalente): resolver precio al preview y al persistir detalle; escribir snapshot en OrdenLavanderiaDetalle alineado con campos actuales de facturación.
4) Extender respuesta del catálogo POS para incluir precios por servicio o estructura acordada en ADR.
5) Tests Feature: dos servicios, dos productos, totales distintos; servicio desactivado; falta de precio según regla acordada.

Rama única; commits pequeños; controladores delgados.
```

### §5.3 — Frontend POS (servicio cambia precio)

```text
Rol: Frontend POS.

Actualizar pos.vue, repositorios y i18n:
1) Selector de tipo de servicio según contrato del backend (cabecera o línea).
2) Al cambiar servicio, refrescar totales vía preview-totals o flujo documentado.
3) Mostrar en catálogo el precio contextual o “desde” según diseño mínimo sin rediseño completo.
4) Mensajes de error claros si el backend rechaza por precio faltante.

No tocar áreas/caja/QR salvo imprescindible. Incluir strings en es.json y en.json.
```

### §5.4 — Admin catálogo (orden, visibilidad, **UI dinámica precio × servicio**)

```text
Rol: Admin Vue + API soporte. Archivo principal del catálogo POS: resources/js/src/views/admin/laundry-pos-catalog.vue (modal #modalLaundryProd). Repositorio: resources/js/src/repositories/LaundryPosCatalogAdminRepository.js.

Criterios de aceptación UI (OBLIGATORIOS — no basta con “exponer la matriz en API”):

1) En el modal crear/editar producto, sección titulada p. ej. “Precios por tipo de servicio”:
   - Lista reactiva de filas: cada fila = select de tipo de servicio (activos, cargados desde API existente o nueva ruta) + input numérico de precio (moneda local según patrón del proyecto).
   - Botón “+ Agregar” que añade una fila vacía al final.
   - Por fila: botón o icono para eliminar esa línea (quitada del payload al guardar).
   - Validación en cliente: no duplicar el mismo service_type_id en dos filas; montos ≥ 0; al menos una fila opcional según regla ADR (si es obligatorio al menos un precio por servicio, deshabilitar Guardar y mostrar mensaje).
2) Al abrir edición: hidratar las filas desde la respuesta del GET del producto (array anidado acordado en ADR).
3) Al guardar: enviar en el mismo POST/PUT del producto el array de precios por servicio; mapear errores 422 del backend a fila/campo o mensaje global (Swal/toast ya usado en la vista).
4) Comportamiento de unit_price (precio único actual): según ADR — mostrar como “precio base / fallback” con texto de ayuda, o ocultar si la matriz reemplaza totalmente al precio único; no dejar ambigüedad sin texto en pantalla.
5) Reordenar categorías y productos (drag-and-drop o flechas) persistiendo sort_order si ya está en alcance.
6) Toggle visible en POS por categoría/producto si existe el campo.
7) Opcional en misma fase: segunda vista “Matriz rápida” (tabla todos los productos × servicios). Si el tiempo aprieta, priorizar el modal por producto (punto 1–4).
8) i18n: todas las etiquetas y mensajes nuevos en resources/js/src/locales/es.json y en.json.

Entregable verificable: un usuario admin puede definir 3 servicios con 3 importes distintos para un mismo producto, guardar, recargar la página y ver los mismos valores en el modal sin usar Postman.
```

### §5.8 — Solo UI admin: precios dinámicos por servicio (backend ya implementado)

```text
Rol: Solo frontend admin (Vue 3 + i18n).

El backend ya persiste precios por (laundry_sale_product, service_type) y el GET/PUT del producto incluye el array acordado.

Implementar únicamente en laundry-pos-catalog.vue (+ repository si hace falta) la sección dinámica del §5.4 puntos 1–4 y 8. No modificar POS de ventas ni OrdenLavanderia en este hilo salvo que falte una clave en la respuesta JSON y necesites un cambio mínimo en el recurso API.

Prueba manual: crear producto de prueba con dos filas de precio por servicio distintos, guardar, editar de nuevo y confirmar persistencia.
```

### §5.5 — Menús, router, sidebar

```text
Rol: Navegación.

1) Añadir rutas en resources/js/src/router/index.js con lazy load y meta de permisos como el resto del proyecto.
2) Actualizar sidebar.vue y header.vue con entradas para: catálogo POS (si falta), tipos de servicio, precios por servicio / matriz (nombres finales según i18n).
3) No duplicar lógica de permisos: mismo patrón que otras vistas admin.
4) Títulos y breadcrumbs coherentes con la app actual.

Entrega: enlaces navegables sin 404 en dev.
```

### §5.6 — QA / regresión

```text
Rol: QA automatizada.

1) API catálogo: orden estable por sort_order; precios por servicio presentes según semilla de test.
2) Orden con mismo producto y distinto service_type: total y snapshots distintos.
3) Admin: cambiar orden de categoría reflejado en JSON público del catálogo.
4) Regresión en reportes mensuales o export si tocan columnas de servicio o importe.
5) Opcional: checklist manual breve para UI (modal): añadir/quitar fila, duplicado servicio rechazado, 422 visible.

Documentar casos borde en comentarios de test o checklist interno.
```

### §5.7 — Prompt maestro corto (un solo pegado rápido)

```text
ControlLavado: precios por producto según tipo de servicio; admin de tipos de servicio; en admin POS modal de producto con filas dinámicas precio×servicio (GET/PUT coherentes); opcional matriz en lote; sort_order y visibilidad; router/sidebar/i18n; preview-totals y creación de orden con snapshots. Respeta ADR-001, ANALISIS_LOGICA_NEGOCIO.md, sin mezclar inventory_* sin ADR. Tests Feature en verde + prueba manual del modal.
```

---

## 6. Riesgos y mitigación

| Riesgo | Mitigación |
|--------|------------|
| Explosión de combinaciones producto×servicio | UI de matriz en lote; defaults por script de migración; validación en servidor. |
| Órdenes históricas sin service_type en línea | Mantener lectura legacy; no recalcular totales pasados al editar precios nuevos. |
| Menús visibles sin permiso | `v-if` / meta route + mismo chequeo que otras pantallas admin. |
| Agente implementa API pero no toca el modal | §5.4 y §5.8 exigen entregable verificable en UI; en §4 B incluir “modal producto” explícitamente. |

---

## 7. Cómo ejecutar estos prompts en Cursor

Igual que en [`docs/PROMPTS_EQUIPO_MIGRACION_PRODUCTOS_POS.md`](PROMPTS_EQUIPO_MIGRACION_PRODUCTOS_POS.md) **§8**: los bloques delimitados por ` ```text ` … ` ``` ` son texto para **pegar en el chat del agente** (modo **Agent**).

1. Abre este archivo: `docs/PROMPTS_CATALOGO_PRECIOS_POR_SERVICIO_COMPOSICION_MENUS.md`.
2. Copia el cuerpo del prompt (solo lo que va **entre** las líneas del fence), sin la línea ` ```text ` ni el cierre ` ``` `.
3. Opcional: en el primer mensaje adjunta contexto con `@docs/ANALISIS_LOGICA_NEGOCIO.md` y `@docs/PROMPTS_CATALOGO_PRECIOS_POR_SERVICIO_COMPOSICION_MENUS.md`.
4. **Por fases:** pega **§4** y luego en el mismo mensaje el **§5.x** que corresponda.
5. **Sin frenos:** pega solo **§4 B**.

Si esta evolución depende fuerte del catálogo por producto, puedes combinar el **§4** del documento de migración POS con el **§5.x** de **este** archivo en un solo mensaje, indicando al humano qué fase tiene prioridad.

---

*Complemento a la migración POS por producto; alinear con código real de `LavanderiaPosCatalogController`, `LaundryPricingService`, `OrdenLavanderiaDetalle` y vistas admin/POS del repo.*
