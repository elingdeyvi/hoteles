# Changelog interno TIBU

*(Dos entradas distintas usan el nombre «fase 7»: **2026-04-23** = documentación canónico vs legacy **ADR-001/POS**; **2026-04-24** siguiente = cierre **ADR-002** inventario documentos.)*

## 2026-04-24 — Inventario almacén: documentos ADR-002 (fase 7 cierre estructura / changelog)

- **Datos:** `inventory_transfer_documents`, `inventory_transfer_lines` (+ cabecera proveedor 1b); confirmación genera `inventory_movements` (`in` / `out`) vía `InventoryTransferDocumentService` + `InventoryMovementService::recordMovementWithoutTransaction`.
- **API:** `GET|POST /api/inventory/transfer-documents`, `GET|PUT .../{id}`, `POST .../confirm`, `POST .../cancel-draft`; `GET /api/inventory/catalog` incluye `areas` para destinos.
- **Vue:** `/inventario/documentos`, `/inventario/documentos/crear`, `/inventario/documentos/:id`; i18n `inventory.transfer_documents*`.
- **Tests:** `php artisan test --filter=InventoryTransferDocument`.
- **Docs:** `docs/ESTRUCTURA_DATOS_CRITICA.md` (§5 ADR-002), `docs/ANALISIS_LOGICA_NEGOCIO.md`, `.cursorrules`, `docs/GAP_ANALYSIS_LAVANDERIA.md` §2, `docs/CHECKLIST_QA_INVENTARIO_DOCUMENTOS_ENTREGA_SALIDA.md`, `docs/PROMPTS_MODULOS_ENTREGA_SALIDA_INVENTARIO.md` §7 (fases 0–7), `docs/ADR-002-inventario-entrega-salida.md`.

## 2026-04-24 — Fase 8 Cierre integración (ventas mensuales + catálogo POS)

- `VentasController::mensual`: campo `lineas_catalogo_pos` por bucket año/mes; SQL portable SQLite/MySQL.
- `resources/js/src/views/ventas/mensuales.vue`: columna y tarjeta de resumen.
- `tests/Feature/VentasMensualCatalogoPosTest.php` + inclusión en suite `TibuC8Regression` (`phpunit.xml`).
- Docs: `ANALISIS_LOGICA_NEGOCIO.md`, `.cursorrules`, `ADR-001` §8, `PROMPTS_EQUIPO_MIGRACION_PRODUCTOS_POS.md` (fase 8 / checklist).

## 2026-04-23 — Fase 7 Documentación (canónico vs legacy)

- `docs/ANALISIS_LOGICA_NEGOCIO.md`: resumen §1 (camino preferente catálogo POS); **§6.1** tabla canónico vs legacy vs histórico; matización ServiceType/GarmentType como TIBU C2 (no catálogo `laundry_sale_*`); §8 verificación.
- `.cursorrules`: subsección «Tablas: canónico vs legacy» bajo venta/POS.
- `docs/ADR-001-pos-catalogo-productos.md`: §8 registro fase 7.

## 2026-04-23 — Fase 6 QA checklist (catálogo POS)

- `docs/CHECKLIST_QA_VENTA_CAJA_TICKET_TIBU.md`: escenarios manuales TIBU + **catálogo POS** (venta, ticket, CSV, C8.5); comandos `php artisan test --filter=LaundryPosSaleCatalogQaTest` y `LaundrySaleCatalogAdminTest` documentados junto a C8.
- `docs/ADR-001-pos-catalogo-productos.md`: registro fase 6 en §8.

## 2026-03-24 — D6 Compatibilidad y migracion de datos

- Se documenta compatibilidad en migracion `2026_03_24_130000_add_defaults_to_garment_types_table`:
  - no modifica `orden_lavanderia_detalles` historico;
  - `default_load_type_id` nullable para fallback operativo;
  - fallback de `default_billing_mode` a `by_garment_type`.
- Se agrega comando de verificacion:
  - `php artisan laundry:verify-d6-post-migration`
- Se agrega script composer de chequeo completo:
  - `composer run check:d6-post-migration`
- Se agrega checklist operativo:
  - `docs/CHECKLIST_POST_MIGRACION_D6.md`
