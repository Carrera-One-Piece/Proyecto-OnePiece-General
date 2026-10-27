# Módulo de Navegación

Proyecto: **Proyecto-OnePiece-General** (Laravel 12, PHP 8.3, MySQL)
Rama: `feature/navegacion-ubicaciones-eventos` → PR a `develop`

## 1. Qué hace el módulo

Navegación tiene dos partes:

1. **Ubicaciones actuales:** guarda la última posición conocida (x, y) de cada tripulación, personaje, barco y organización, y deja el movimiento en el historial.
2. **Eventos:** CRUD de eventos del mundo (guerras, batallas, Buster Call, etc.) sobre la base de datos oficial `marina_system_db`.

Todo está protegido por el sistema de login y permisos del equipo (`permiso:MODULO.ACCION`).

## 2. Qué se hizo

| Área | Cambio |
|---|---|
| Eventos | Modelo `Evento`, controlador `EventoController` (JSON), rutas y validaciones, ajustados a las tablas reales del `.sql` oficial |
| Eventos | Página de pruebas `/eventos/prueba` con tema One Piece (casos rápidos, carteles WANTED, mensajes de error) |
| Ubicaciones | Migración de `ubicacion_actual` y script `ubicacion_actual.sql` con sus triggers |
| Ubicaciones | Página de pruebas `/ubicaciones/prueba` (rediseño de Karen) |
| Pruebas | `EventoValidationTest` (8) y `UbicacionValidationTest` (10), dentro de las 47 pruebas del proyecto |
| Entorno | PHP 8.3 con SQLite habilitado para las pruebas, XAMPP solo para MySQL y phpMyAdmin |

## 3. Eventos

### Rutas

Todas exigen sesión iniciada (`auth`) y el permiso indicado.

| Método | Ruta | Permiso | Respuesta correcta |
|---|---|---|---|
| GET | `/eventos` | `EVENTOS.VER` | 200, lista de eventos |
| GET | `/eventos/{id}` | `EVENTOS.VER` | 200, un evento |
| POST | `/eventos` | `EVENTOS.CREAR` | 201 `{message, data}` |
| PUT | `/eventos/{id}` | `EVENTOS.EDITAR` | 200 `{message, data}` |
| DELETE | `/eventos/{id}` | `EVENTOS.ELIMINAR` | 200 `{message}` |
| GET | `/eventos/prueba` | `EVENTOS.VER` | Página de pruebas |

### Campos y validación

| Campo | Regla |
|---|---|
| `Nombre` | obligatorio, texto, máx. 150 |
| `ID_tipo_evento` | obligatorio, entero, debe existir en `tipos_evento` |
| `ID_isla` | obligatorio, entero, debe existir en `isla` |
| `Fecha_evento` | opcional, fecha válida |
| `Descripcion` | opcional, texto |
| `Nivel_amenaza` | opcional, texto, máx. 50 |

Nota: en `isla` el ID 1 no existe. Las islas empiezan en 2 (en las pruebas se usa la 8).

### Códigos de respuesta

| Código | Cuándo |
|---|---|
| 200 / 201 | Operación correcta |
| 401 / 419 | Sesión vencida o token CSRF inválido |
| 403 | El rol no tiene el permiso |
| 404 | El evento no existe |
| 422 | Datos no válidos (la respuesta lista los errores por campo) |

### Base de datos

La tabla `eventos` tiene un trigger de auditoría que escribe en `auditoria_eventos`. Ese trigger guarda `ID_usuario` como NULL, así que la auditoría no dice quién hizo el cambio.

## 4. Ubicaciones actuales

### Ruta

| Método | Ruta | Permiso |
|---|---|---|
| PUT | `/ubicaciones/{tipo}/{id}` | `NAVEGACION.EDITAR` |
| GET | `/ubicaciones/prueba` | `NAVEGACION.VER` |

- `tipo`: `tripulacion`, `personaje`, `barco` u `organizacion`.
- Cuerpo: `{ "x": 0 a 1500, "y": 0 a 1000 }`.
- Respuesta correcta: 200 `{ "ok": true }`.
- 422 si `x` o `y` faltan, no son numéricos o salen del mapa, si el tipo no es válido o si la entidad con ese `id` no existe.

### Tabla `ubicacion_actual`

Guarda una fila por entidad (clave única `Tipo_entidad` + `ID_entidad`) con `ID_isla`, `Coordenada_X`, `Coordenada_Y` y `Fecha_actualizacion`.

Dos triggers, uno al insertar y otro al actualizar, copian cada movimiento a `historial_movimientos` con la observación "Ubicación inicial registrada" o "Ubicación actualizada".

Esta tabla ya está incluida en la base de datos oficial `marina_system_db`, junto con sus triggers. La migración `2026_10_19_000001_create_ubicacion_actual_table.php` crea la misma tabla (se usa en las pruebas) y el script `ubicacion_actual.sql` es el equivalente para importar a mano. Los triggers son solo de MySQL y no corren en las pruebas con SQLite.

## 5. Permisos por rol

Contraseña de todos los usuarios de ejemplo: `Passw0rd!` (se cargan con `php artisan db:seed`).

| Usuario | Rol | Alcance en Navegación y Eventos |
|---|---|---|
| `navegacion@marina.gov` | Especialista en Navegación | Ver, crear, editar y eliminar |
| `director@cipherpol.gov` | Cipher Pol | Solo ver (crear, editar y eliminar dan 403) |
| `almirante@marina.gov` | Almirante | Principalmente ver |
| `seguridad@marina.gov` | Experto en Seguridad | Solo usuarios y bitácora (sin Navegación) |
| `pendiente@gobierno.gov` | Cuenta pendiente | No puede iniciar sesión |

## 6. Cómo probarlo

1. Importar `marina_system_db` en phpMyAdmin, configurar `.env` y correr `php artisan migrate` y `php artisan db:seed`.
2. Arrancar con `php artisan serve` e iniciar sesión en `http://127.0.0.1:8000/login`.
3. Abrir `/eventos/prueba` y `/ubicaciones/prueba`, y usar los casos rápidos.
4. Automáticas: `php artisan test`. Resultado esperado: **47 pruebas correctas**.

Casos verificados a mano: listar, ver, crear, editar y borrar eventos; errores 403, 404 y 422; y la escritura de los triggers en `historial_movimientos`.

## 7. Pendientes conocidos

- El trigger de `eventos` no guarda `ID_usuario` en la auditoría.
- Las fechas se muestran en UTC porque `APP_TIMEZONE` no está en `America/Bogota`.
- `GET /api/ubicaciones` (en `routes/api.php`, con `auth`) probablemente responde 401 con la sesión web y hoy no lo usa nada.

## 8. Equipo

- **Daniela:** módulo de Navegación (eventos y ubicaciones actuales).
- **Karen Bayona:** login, permisos y página de pruebas de ubicaciones.
- **Dulce:** primera versión del controlador de eventos.
