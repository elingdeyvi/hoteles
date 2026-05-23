# D7 - Criterios de aceptacion (DoD) defaults prenda + modulos

Referencia: `PROMPTS_DEFAULTS_PRENDA_CARGA_Y_MODULOS_TIBU.md` seccion D7.

## Estado DoD

- [x] POS autocompleta tipo de carga y modo de cobro al elegir prenda.
- [x] Usuario puede sobreescribir defaults por linea (carga y modo).
- [x] Precio inicial y total se resuelven en backend (matriz/price_per_kg + defaults de prenda).
- [x] Servicios y productos legacy estan separados en navegacion y API.
- [x] Existe modulo admin para CRUD de tipos de prenda con defaults.
- [x] Tests backend relevantes en verde (ordenes, pricing, ticket, caja y nuevos modulos/prendas).

## Evidencia tecnica por punto

1) Defaults en POS + override:
- `resources/js/src/views/ventas/pos.vue`
- Endpoint preview: `POST /api/laundry/pos/preview-totals`

2) Resolucion backend (source of truth):
- `app/Services/LaundryPricingService.php`
- `app/Services/OrdenLavanderiaService.php`

3) Separacion modulos:
- API: `GET /api/catalogos/servicios`, `GET /api/catalogos/productos-legacy`
- UI: `resources/js/src/views/catalogos/servicios.vue`,
  `resources/js/src/views/catalogos/productos-legacy.vue`

4) Admin prendas:
- API: `GET/POST/PUT/PATCH /api/admin/garment-types*`
- UI: `resources/js/src/views/admin/garment-types.vue`

## Suite recomendada para cierre D7

```bash
composer run test:tibu-d7-dod
```

Incluye:
- Suite C8 (`TibuC8Regression`)
- D1: defaults por prenda
- D4: modulos catalogos separados
- D5: CRUD admin garment types
- C7: legacy congelado/compatibilidad
