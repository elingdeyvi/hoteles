# Regresión TIBU — pruebas automáticas y flujo manual

Documentación alineada con el **prompt §19** (`PROMPTS_ACTUALIZACION_TIBU.md`): ejecutar la suite Feature y validar en entorno real el flujo vertical de lavandería.

## Pruebas automáticas (API crítica)

Desde la raíz del proyecto:

```bash
php artisan test
```

**Cobertura actual (Feature):**

| Área | Archivo | Qué valida |
|------|---------|------------|
| Lavandería API | `tests/Feature/LaundryApiTest.php` | `GET /api/laundry/areas`, presencia de orden |
| Escaneos / workflow | `tests/Feature/LaundryWorkflowRegisterScanTest.php` | `POST /api/lavanderia/workflow/register-scan`, `POST /api/laundry/scans` (camelCase), 401/403 |
| Seguimiento público | `tests/Feature/PublicTrackingTest.php` | Contrato JSON, sin PII, alias `orden-estatus` |
| Seguridad | `tests/Feature/SecurityAuthorizationTest.php` | Revocación de token en logout, permisos inventario |

Si algo falla, **priorizar validaciones de negocio y autorización antes que ajustes solo de UI**.

### Notas para quien mantenga los tests

- En tests con **varias peticiones HTTP en el mismo proceso**, los guards de `Auth` pueden quedar resueltos en memoria. Donde haga falta, llamar `$this->app['auth']->forgetGuards()` entre peticiones (ver `SecurityAuthorizationTest`).
- El aviso de PHPUnit sobre esquema XML deprecado se puede abordar con `phpunit --migrate-configuration` cuando convenga.

## Flujo manual (checklist QA)

Objetivo: recorrer el circuito **venta POS → dos QR en ticket → separación por carga → lavados en paralelo → planchado → almacén → entrega/envío**, usando los **códigos de área** sembrados (`AreaSeeder`): `reception`, `selection`, `heavy_wash`, `light_wash`, `ironing`, `storage`, `shipping`.

Pasos sugeridos (ajustar nombres de menú según el despliegue):

1. **Venta POS (recepción)**  
   Crear una orden con líneas que disparen el flujo de lavandería; confirmar folio único y generación de ticket/PDF si aplica.

2. **Dos QR impresos**  
   Verificar en el ticket **dos bloques idénticos** de QR (o el mecanismo definido en el producto) y que ambos resuelvan la misma orden por folio/QR.

3. **Selección / patio (`selection`)**  
   Escanear en el área de selección; confirmar que la orden queda en la ruta correcta según **tipo de carga** (pesada vs ligera).

4. **Lavado en paralelo**  
   En **dos estaciones o sesiones**: una orden (o lote) en **lavado pesado** (`heavy_wash`) y otra en **lavado ligero** (`light_wash`). Registrar escaneos y comprobar que el estado/presencia avanza sin cruzar áreas indebidas.

5. **Planchado (`ironing`)**  
   Escanear y avanzar etapa hasta planchado según reglas del flujo.

6. **Almacén (`storage`)**  
   Confirmar escaneo de entrada/salida si el área exige dos lecturas (`requires_scan_twice` en recepción u otras áreas configuradas).

7. **Entrega / envío (`shipping`)**  
   Marcar entrega o estado de envío según la UI y API; validar seguimiento público (`/api/public/tracking/{folio}`) sin exponer datos privados.

Registrar incidencias con: pasos, usuario/rol, folio de prueba y respuesta HTTP o mensaje de error.
