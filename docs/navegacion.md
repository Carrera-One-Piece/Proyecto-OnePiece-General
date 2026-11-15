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

## 9. Mapa navegable y endpoints de recorridos y predicciones (15/11)

### Mapa (`/mapa`, permiso `NAVEGACION.VER`)

- Carta náutica dibujada en SVG (Red Line, Grand Line, Calm Belt y mares), con las islas de la tabla `isla` coloreadas por mar.
- Muestra dónde está cada tripulación. Primero busca en `ubicacion_actual`, después la ubicación de su barco y, si no hay ninguna, el último `movimiento_barco`.
- Capas que se prenden y apagan: islas, tripulaciones, otras ubicaciones, eventos, rutas entre islas y recorridos.
- Tiene buscador, botón **Expandir** (pantalla completa), coordenadas del puntero y leyenda.
- Al hacer clic en una tripulación se ven su recorrido, la predicción de su próximo movimiento y las predicciones anteriores.
- Pestaña **Ubicar**, solo con `NAVEGACION.EDITAR`: se elige la tripulación o el barco y luego una isla o un clic en el mapa. Usa el `PUT /ubicaciones/{tipo}/{id}` que ya existía, que ahora también guarda `ID_isla`.
- No necesita `npm run dev`: Leaflet viene del CDN y el script está en `public/js/mapa-navegacion.js`.

### Endpoints JSON

| Método | Ruta | Permiso | Qué devuelve |
|---|---|---|---|
| GET | `/mapa/datos` | `NAVEGACION.VER` | Todo lo que pinta el mapa |
| GET | `/navegacion/conexiones?isla={id}` | `NAVEGACION.VER` | Rutas de `isla_adyacente`, con nombres, distancia y días. Con `?isla` muestra solo las de esa isla |
| GET | `/navegacion/recorridos/{tipo}/{id}?desde=&hasta=` | `NAVEGACION.VER` | Recorrido en orden de fecha (`historial_movimientos`, más `movimiento_barco` si es un barco). Si una tripulación no tiene historial, devuelve el de su barco |
| GET | `/navegacion/predicciones?tripulacion={id}` | `NAVEGACION.VER` | Predicciones guardadas en `movimientos_predichos` |
| POST | `/navegacion/predicciones` `{ "tripulacion": 8 }` | `NAVEGACION.CREAR` | Genera una predicción nueva, la guarda y responde 201. Si la tripulación no tiene ubicación o no hay rutas, responde 422 |

### Motor de predicción (`app/Support/MotorPrediccion.php`)

Sigue el documento *Tablas_Motor_Reglas*:

1. La isla de origen es la isla donde está la tripulación.
2. Las candidatas son las islas alcanzables según `isla_adyacente`. Si no sale ninguna ruta, se permite volver por las que llegan.
3. A cada candidata se le suman los pesos de las reglas activas de `reglas_ponderacion`:
   - **Presencia_Enemiga**: hay una tripulación rival o enemiga en la isla (según `relacion_tripulaciones`), o la isla es territorio del gobierno o de la Marina.
   - **Objetivo_Estrategico**: la isla está en `territorios`, abre la entrada a otro mar o es un cruce con 2 o más rutas.
   - **Recompensa_Disponible**: hay piratas con recompensa activa de 100 M o más en la isla.
   - **Alianza_Presente**: hay una tripulación aliada en la isla.
   - **Evento_Reciente**: hay un evento del último año en la isla. Resta si es Guerra, Buster Call, Ejecución o Invasión, y suma en los demás casos.
   - **Distancia_Navegacion**: se multiplica por los días de viaje (`Tiempo_estimado`).
   - **Peligrosidad_Mar**: se multiplica por la `Peligrosidad` del mar.
4. Gana la candidata con más puntos. Se guarda en `movimientos_predichos` con el detalle de los factores en JSON.

Los pesos se calibran en `reglas_ponderacion` sin tocar código. Una regla con `Activo = 0` no se aplica.

### Rol Marina

Es un rol nuevo de solo consulta, pedido por el CTO: `TRIPULACIONES`, `RECOMPENSAS`, `NAVEGACION`, `EVENTOS` y `DASHBOARD`, todos en `VER`. Se carga con `php artisan db:seed` y el usuario demo es `marina@marina.gov`.

### Pruebas

`tests/Feature/MapaNavegacionTest.php` tiene 14 pruebas: permisos del mapa, posición, conexiones, recorridos, predicción, reglas inactivas y ubicar con isla.
