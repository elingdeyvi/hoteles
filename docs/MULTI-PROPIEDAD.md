# Multi-propiedad (varios hoteles)

Una instalación puede gestionar **varios hoteles** (propiedades). Cada propiedad tiene inventario, reservas y POS aislados.

## Modelo

| Tabla | `property_id` |
|-------|----------------|
| `properties` | — (raíz) |
| `room_types`, `rooms`, `reservations` | Sí |
| `pos_outlets` | Sí |
| `configuracion_empresa` | Sí (marca y datos por hotel) |
| `huespedes` | Compartidos (MVP) |

## API autenticada

Todas las rutas `/api/hotel/*` usan el middleware `property.context`:

- Header **`X-Property-Id`**: ID numérico del hotel activo
- Si no se envía, se usa el primer hotel activo

Listar hoteles:

```
GET /api/hotel/properties
```

## Reservas públicas

Por hotel (slug):

```
GET  /api/booking/costa-azul/config
POST /api/booking/costa-azul/reservations
```

Web:

```
/reservar/costa-azul
/reservar/sierra-verde
```

`/reservar` redirige al hotel predeterminado (`costa-azul`).

## Hoteles demo (seed)

| Código | Nombre | Perfil |
|--------|--------|--------|
| `costa-azul` | Hotel Costa Azul | Playa, tipos Sencilla/Doble/Suite |
| `sierra-verde` | Hotel Sierra Verde | Montaña, tipos Cabaña/Vista Montaña/Loft |

Tras `php artisan migrate --seed`, ambos tienen habitaciones, tarifas y POS completos.

## Panel admin

Si hay más de un hotel, el **header** muestra un selector. Al cambiar, se recarga la app con el nuevo `X-Property-Id`.

## Añadir un segundo hotel

1. Insertar fila en `properties` (`code`, `name`, …)
2. Crear `configuracion_empresa` con ese `property_id`
3. Ejecutar seeders o crear tipos/habitaciones/POS con `property_id`

## Panel de administración

Ruta: **Configuración hotel → Hoteles / propiedades** (`/hotel/config/propiedades`)

- Crear hotel (código URL, nombre, contacto)
- Activar/desactivar booking web
- Desactivar hotel (si tiene datos) o eliminar (si está vacío)
- Enlace directo a `/reservar/{codigo}`

## Migración

```bash
php artisan migrate
```

Crea `properties` y asigna `costa-azul` a los datos existentes.

## Usuarios por hotel

Tabla pivote `property_user` (`is_default` indica el hotel predeterminado del usuario).

- **Administrador:** ve y opera todos los hoteles activos.
- **Recepcionista, Housekeeping, Cajero:** solo ven hoteles asignados en el selector del header.
- Si un usuario no tiene hoteles asignados, el MVP permite acceso a todos (compatibilidad con instalaciones de un solo hotel).

Asignación en el panel: **Usuarios → editar → Hoteles asignados** (`property_ids` en la API).
