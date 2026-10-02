# Cómo proteger tu módulo

Para los subgrupos de Tripulaciones, Recompensas, Navegación e Inteligencia.
Es corto, son 2 minutos.

> Versión Laravel (29/09/2026). Reemplaza la guía vieja de PHP plano que usaba
> `Auth::requierePermiso(...)`: esa ya no existe.

## El problema

Si no haces nada, cualquiera puede entrar a tu módulo escribiendo la dirección en el navegador.
El documento IEEE pide que eso no pase (RNF02), y aplica a **todos** los requerimientos, no solo
a los nuestros.

## La solución: una línea en la ruta

En `routes/web.php`, agrega `->middleware('permiso:MODULO.ACCION')` a cada ruta:

```php
use App\Http\Controllers\TripulacionController;

Route::get('/tripulaciones', [TripulacionController::class, 'listar'])
    ->middleware('permiso:TRIPULACIONES.VER');

Route::get('/tripulaciones/crear', [TripulacionController::class, 'mostrarFormCreacion'])
    ->middleware('permiso:TRIPULACIONES.CREAR');

Route::post('/tripulaciones', [TripulacionController::class, 'guardar'])
    ->middleware('permiso:TRIPULACIONES.CREAR');
```

Qué hace esa línea, sola:

- Si el usuario **no inició sesión** → lo manda al login.
- Si su cuenta **ya no está activa** (la suspendieron) → le cierra la sesión.
- Si **no tiene el permiso** → le muestra "Acceso denegado" (403) y lo anota en la bitácora.
- Si todo está bien → deja pasar. Tu controlador ni se entera.

**No tienes que escribir nada dentro del controlador.**

### Varias rutas con el mismo permiso

```php
Route::middleware('permiso:TRIPULACIONES.VER')->group(function () {
    Route::get('/tripulaciones', [TripulacionController::class, 'listar']);
    Route::get('/tripulaciones/{id}', [TripulacionController::class, 'mostrar']);
});
```

## Qué nombres puedes usar

Siempre `MODULO.ACCION`, **con punto y en mayúsculas**.

**Módulos:** `TRIPULACIONES`, `RECOMPENSAS`, `ORDENES_CAPTURA`, `NAVEGACION`, `EVENTOS`,
`ALIANZAS`, `AMENAZAS`, `INFORMES`, `DASHBOARD`, `USUARIOS`

**Acciones:** `VER`, `CREAR`, `EDITAR`, `ELIMINAR`

Quién tiene cada uno está en [matriz-permisos.md](matriz-permisos.md).

**Si te falta un módulo o una acción, pídelo al grupo de Login. No lo inventes:** si el nombre no
está en la tabla `permisos`, nadie lo tiene, y a todos les va a salir "Acceso denegado".
Tampoco sirve `TRIPULACIONES-CREAR` con guion ni `tripulaciones.crear` en minúscula.

## Saber quién está usando el sistema

En un controlador:

```php
$usuario = auth()->user();

$usuario->ID_usuario                  // 1
$usuario->Nombre                      // 'Experto Tashigi Renn'
$usuario->rol->Nombre_rol             // 'Experto en Seguridad de Información'
$usuario->organizacion->Nombre        // 'Marina', 'Cipher Pol' o 'Gobierno Afiliado'
```

Te sirve para guardar quién hizo cada cambio (RNF08). Por ejemplo, en una tabla con columna
`Registrado_por`: `'Registrado_por' => auth()->user()->ID_usuario`.

## Ocultar botones (opcional)

En una vista Blade:

```blade
@can('TRIPULACIONES.EDITAR')
    <a href="...">Editar</a>
@endcan
```

Y en un controlador, si necesitas decidir algo sin cortar la página:

```php
if (Gate::allows('RECOMPENSAS.EDITAR')) {
    // ...
}
```

`@can` y `Gate::allows` solo responden sí o no. **No protegen nada.**

## Ojo con esto

**Esconder el botón no protege nada.**

Si solo escondes el botón pero no pones el `->middleware('permiso:...')` en la ruta, cualquiera
que escriba la dirección a mano entra igual. Eso es justo lo que se va a probar (criterio de
aceptación de PB06).

Regla fácil:
- `->middleware('permiso:...')` → **siempre**, en la ruta.
- `@can(...)` → opcional, en la vista, solo para que no salgan botones que no sirven.

## Cómo saber si quedó bien

Usa los usuarios de prueba (clave de todos: `Passw0rd!`):

| Correo | Sirve para probar |
|---|---|
| `director@cipherpol.gov` | Tiene TRIPULACIONES completo, pero no RECOMPENSAS ni USUARIOS |
| `almirante@marina.gov` | Casi todo en solo VER |
| `seguridad@marina.gov` | Solo USUARIOS y BITACORA: no ve ningún módulo de datos |

1. Entra con un usuario que **no** tenga el permiso.
2. Escribe la dirección de tu pantalla en la barra del navegador.
3. Debe salir **"Acceso denegado"**.

Si entras, te falta el `->middleware('permiso:...')` en esa ruta.

Para cargar esos usuarios en tu base local, mira la sección 1 de
[login-y-permisos.md](login-y-permisos.md).

---

¿Dudas o te falta algún módulo? Escríbenos antes de inventar nombres nuevos.
