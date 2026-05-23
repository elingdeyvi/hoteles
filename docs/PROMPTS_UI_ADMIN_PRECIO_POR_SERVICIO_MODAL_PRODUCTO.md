# Prompts: UI admin — precios dinámicos por tipo de servicio (modal de producto POS)

**Propósito:** documento **independiente** para equipos o hilos que **ya ejecutaron** la migración / backend / [`docs/PROMPTS_CATALOGO_PRECIOS_POR_SERVICIO_COMPOSICION_MENUS.md`](PROMPTS_CATALOGO_PRECIOS_POR_SERVICIO_COMPOSICION_MENUS.md) u otras fases, y necesitan **cerrar la parte visual** sin re-leer el paquete completo: formulario **dinámico** precio × servicio en el **modal de producto** del catálogo POS.

**Referencias:** [`docs/ANALISIS_LOGICA_NEGOCIO.md`](ANALISIS_LOGICA_NEGOCIO.md), [`docs/ADR-001-pos-catalogo-productos.md`](ADR-001-pos-catalogo-productos.md), ADR de precios por servicio si ya existe. Código objetivo: `resources/js/src/views/admin/laundry-pos-catalog.vue` (`#modalLaundryProd`), `resources/js/src/repositories/LaundryPosCatalogAdminRepository.js`. Convenciones: `.cursorrules`.

**Git:** misma rama de trabajo acordada (p. ej. `main`); commits pequeños.

---

## 1. Por qué hace falta este archivo (brecha)

- Los prompts genéricos hablan de **“matriz”** o **“panel en lote”** sin fijar **pantalla concreta**; un agente puede dejar **solo API** y considerar el requisito cumplido.
- La UX habitual es el **modal crear/editar producto** del catálogo POS, **no** la pantalla legacy **Catálogos / Productos** (nombre + stock).
- El admin POS puede tener `unit_price` y defaults de servicio/carga, pero **sin** una lista **N filas × (tipo de servicio + precio)** no hay forma operativa de mantener precios distintos por servicio **desde la UI**.
- Este doc exige **criterios de aceptación verificables en navegador** (sin Postman).

---

## 2. Lo que debe decidir el ADR (contrato UI ↔ API)

Antes o durante la implementación UI, el ADR (o acuerdo breve) debe cerrar al menos:

1. Nombre y forma del array en **GET** producto, p. ej. `service_prices: [{ service_type_id, amount }]`.
2. Mismo array en **POST/PUT** del producto (sustitución completa del listado vs patch).
3. Rol de **`unit_price`**: precio base / fallback con texto en pantalla, u obsoleto si la matriz lo reemplaza (sin ambigüedad en UI).

---

## 3. Prompt maestro de contexto (pegar con cualquier §4.x de abajo)

```text
Estás en ControlLavado (Laravel + Vue 3).

Fase: implementar o completar la UI del catálogo POS para precios por tipo de servicio en el MODAL de crear/editar producto (laundry-pos-catalog.vue, #modalLaundryProd). Filas dinámicas: agregar/quitar líneas, select de service_type + input de precio, validación cliente, hidratar desde GET y enviar en POST/PUT, errores 422 visibles, i18n es/en.

Rama acordada; no romper permisos ni flujos de caja/POS de venta salvo cambio mínimo acoplado al JSON del producto.

Si el humano pegó debajo un §4.x, ejecuta solo ese bloque.
```

---

## 4. Prompts por bloque (copiar solo el interior del fence `text`)

### §4.1 — Admin Vue + API (modal + contrato backend)

```text
Rol: Admin Vue + API soporte.

Archivos: resources/js/src/views/admin/laundry-pos-catalog.vue (#modalLaundryProd), resources/js/src/repositories/LaundryPosCatalogAdminRepository.js, endpoints admin de producto en Laravel que correspondan.

Criterios de aceptación UI (OBLIGATORIOS):

1) En el modal crear/editar producto, sección titulada p. ej. “Precios por tipo de servicio”:
   - Lista reactiva de filas: select de tipo de servicio (activos, desde API) + input numérico de precio (mismo patrón monetario que el resto del admin).
   - Botón “+ Agregar” que añade una fila vacía al final.
   - Por fila: eliminar línea (excluida del payload al guardar).
   - Cliente: no duplicar service_type_id en dos filas; montos ≥ 0; si regla de negocio exige al menos un precio por servicio, bloquear Guardar con mensaje claro.
2) Edición: hidratar filas desde GET del producto (array acordado en ADR).
3) Guardar: incluir el array en el mismo POST/PUT; mapear 422 a mensaje global o por campo (Swal/toast según patrón de la vista).
4) unit_price: según ADR — etiqueta “precio base / fallback” + ayuda, u ocultar con texto si ya no aplica; nunca dejar dos fuentes de verdad sin explicación en pantalla.
5) i18n: resources/js/src/locales/es.json y en.json para todas las cadenas nuevas.

Entregable: admin puede definir 3 servicios con 3 importes para un producto, guardar, recargar y ver los mismos valores en el modal sin Postman.
```

### §4.2 — Solo UI (backend y JSON del producto ya listos)

```text
Rol: Solo frontend admin (Vue 3 + i18n).

El GET/PUT del producto ya incluye el array de precios por service_type_id según ADR.

Implementar en laundry-pos-catalog.vue (+ repository) la sección dinámica del §4.1 puntos 1–4 y i18n. Cambios en Laravel solo si falta un campo en el JSON y sea un ajuste mínimo al Resource.

Prueba manual: dos filas distintas, guardar, reabrir modal, verificar persistencia.
```

### §4.3 — Arquitecto: solo contrato del modal (sin código)

```text
Rol: Arquitecto.

Sin implementar: define en ADR o anexo el JSON exacto del producto (GET/POST/PUT) para service_prices, reglas de duplicados, vacío permitido o no, y el tratamiento visual de unit_price. Lista rutas y archivos Vue/PHP a tocar.
```

### §4.4 — QA (incluye checklist del modal)

```text
Rol: QA.

1) Tests API si existen para producto con service_prices (crear/actualizar/leer).
2) Checklist manual: añadir fila, quitar fila, duplicado servicio rechazado en cliente, respuesta 422 mostrada, recarga conserva datos.
```

### §4.5 — Pegado rápido (una sola burbuja)

```text
ControlLavado: en admin catálogo POS, modal de producto con filas dinámicas precio×tipo de servicio (GET/PUT coherentes con ADR), validación e i18n; entregable usable en navegador. Mínimo cambio backend si el JSON aún no expone el array.
```

---

## 5. Riesgos y mitigación

| Riesgo | Mitigación |
|--------|------------|
| Solo API sin modal | Exigir entregable §4.1; usar §4.2 si backend ya está. |
| Confundir con Catálogos / Productos legacy | Este trabajo es **catálogo POS** (`laundry-pos-catalog`), no la pantalla histórica nombre+stock. |
| unit_price vs matriz | Decisión explícita en ADR + texto en UI (§2). |

---

## 6. Cómo usar en Cursor

1. Abre **este** archivo: `docs/PROMPTS_UI_ADMIN_PRECIO_POR_SERVICIO_MODAL_PRODUCTO.md`.
2. Copia el texto **entre** ` ```text ` y ` ``` ` del bloque que necesites (**§3** + **§4.1** o **§4.2**, etc.).
3. Modo **Agent**; opcional: `@docs/ANALISIS_LOGICA_NEGOCIO.md` `@docs/PROMPTS_UI_ADMIN_PRECIO_POR_SERVICIO_MODAL_PRODUCTO.md`.
4. Indica rama y si el backend del array **ya** está: si sí, prioriza **§4.2**; si no, **§4.1** (full stack del modal + API).

---

*Anexo de prompts centrados en UI; compatible con el resto de documentos de migración y catálogo POS del repo.*
