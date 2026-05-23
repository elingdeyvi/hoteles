# Checklist post-migracion D6 (defaults por prenda)

Objetivo: validar que la introduccion de defaults en `garment_types` no rompe historico y deja POS/TIBU operable.

## 1) Ejecutar migraciones

```bash
php artisan migrate
```

Esperado:
- columnas presentes en `garment_types`:
  - `default_load_type_id`
  - `default_billing_mode`
  - `is_active`

## 2) Verificacion tecnica D6

```bash
php artisan laundry:verify-d6-post-migration
```

Notas:
- `default_load_type_id = null` es valido (fallback matriz con `load_type_id null` o seleccion manual).
- `default_billing_mode` debe quedar en `by_garment_type` o `by_weight`.

## 3) Regresion automatizada minima

```bash
composer run check:d6-post-migration
```

Incluye:
- `GarmentTypeDefaultsD1Test`
- `LaundryPricingC2Test`
- `OrdenLavanderiaC1LaundryLineTest`

## 4) Verificacion manual rapida en POS

1. Abrir POS, seleccionar servicio.
2. Elegir una prenda con defaults configurados.
3. Confirmar que se autocompletan carga/modo.
4. Cambiar manualmente carga/modo y confirmar recalculo.
5. Guardar orden y validar en detalle de orden:
   - snapshot de `billing_mode` y `load_type_id`.

## 5) Compatibilidad historica

- Revisar una orden antigua con `producto_id` legacy:
  - debe seguir visible y sin mutaciones.
- Revisar orden TIBU pre-D6:
  - no recalcula por defaults nuevos.
