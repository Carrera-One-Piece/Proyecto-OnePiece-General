# Login, registro y permisos

Grupo Login y Registro (Michael, Juan Pardo, Dylan). Cubre **RF27 / PB06**, **RNF01** (seguridad),
**RNF02** (control de acceso) y **RNF08** (trazabilidad).

- [1. Dejarlo corriendo](#1-dejarlo-corriendo)
- [2. Probarlo](#2-probarlo)
- [3. Para los demás grupos: cómo proteger tu módulo](#3-para-los-demás-grupos-cómo-proteger-tu-módulo)
- [4. Qué archivo hace qué](#4-qué-archivo-hace-qué)
- [5. Pendientes](#5-pendientes)

---

## 1. Dejarlo corriendo

Primero, lo de siempre (ver `Guia_repositorio_equipo.docx`): `composer install`, `npm install`.

**1. Importar el `.sql` nuevo** (el del 28/09 en Drive) en phpMyAdmin. Ese archivo crea solo una
base llamada **`marina_system_db`**, no `one_piece_db`.

⚠️ **El `.sql` del 28/09 tiene un error y se corta en la línea 66** (solo crea 5 de las 33 tablas).
Hay dos comentarios escritos `--Coordenadas...` (línea 72) y `--Puede cambiarce...` (línea 243):
MySQL exige un espacio después de `--`. Mientras el CTO lo corrige, abre el archivo, ponle el
espacio a esos dos (`-- Coordenadas...`, `-- Puede cambiarce...`), borra la base si quedó a medias
e importa otra vez. Deben quedar **33 tablas**.

**2. Crear el `.env`** desde el `.env.example` (ya viene apuntando a `marina_system_db`):

```
copy .env.example .env
php artisan key:generate
```

Si ya tenías un `.env`, revisa estas líneas:

```
DB_CONNECTION=mysql
DB_DATABASE=marina_system_db
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

`SESSION_DRIVER` y `CACHE_STORE` van en `file` porque el `.sql` del equipo no trae las tablas
`sessions` ni `cache` de Laravel. Con `database`, el login falla.

**3. Crear la tabla de la bitácora** (es la única que falta en el `.sql`; ya se le pidió al CTO):

```
php artisan migrate --path=database/migrations/2026_09_28_000001_create_bitacora_accesos_table.php
```

⚠️ Usen **ese comando exacto**, con `--path`. Un `php artisan migrate` a secas también crearía las
tablas de ejemplo de Laravel (`users`, `jobs`...) en la base del equipo.

**4. Cargar organizaciones, roles, permisos y usuarios de prueba:**

```
php artisan db:seed
```

**5. Correr** `php artisan serve` y abrir `http://127.0.0.1:8000/login`.

**Para el mapa** hace falta una **segunda terminal** con `npm install` (solo la primera vez) y
`npm run dev`, dejándola abierta. Sin eso, `/mapa` da el error *"Vite manifest not found"*. El login
y las demás pantallas no lo necesitan. El mapa sale vacío mientras la tabla `isla` no tenga datos.

**Si todo carga lento:** en `C:\xampp\php\php.ini` quita el `;` de `zend_extension=opcache`,
`opcache.enable=1` y `opcache.enable_cli=1` (este último pásalo de `0` a `1`), y reinicia
`php artisan serve`. Con eso las páginas pasan de ~0,17 s a ~0,04 s.

---

## 2. Probarlo

### Usuarios de prueba (clave de todos: `Passw0rd!`)

| Correo | Rol | Estado | Qué debería pasar |
|---|---|---|---|
| `seguridad@marina.gov` | Experto en Seguridad | Activo | Entra y ve usuarios, matriz y bitácora |
| `almirante@marina.gov` | Almirante | Activo | Ve la lista de usuarios, pero al aprobar sale 403 |
| `director@cipherpol.gov` | Director Cipher Pol | Activo | Si escribe `/usuarios` a mano, sale 403 |
| `pendiente@gobierno.gov` | (sin rol) | Pendiente | No lo deja entrar: "pendiente de aprobación" |

### Recorrido para la demo

1. Registrarse en `/registro` → sale la pantalla de "pendiente".
2. Intentar entrar con esa cuenta → "Su solicitud aún está pendiente de aprobación".
3. Entrar como `seguridad@marina.gov` → Administrar usuarios → Ver pendientes → Aprobar, con un rol.
4. Cerrar sesión y entrar con la cuenta aprobada → ahora sí entra.
5. Entrar como `director@cipherpol.gov` y escribir `/usuarios` en la barra → **403**.
6. Volver como `seguridad@marina.gov` → Bitácora → ahí está todo: el registro, los logins, el
   acceso denegado y la aprobación.

### Pruebas automáticas

```
php artisan test --filter=LoginYPermisosTest
```

Son 21 pruebas del login, el registro, el guardia, la administración y la bitácora. Usan una base
SQLite en memoria, así que no tocan tu MySQL.

---

## 3. Para los demás grupos: cómo proteger tu módulo

Resumen. La versión completa para pasarle a los otros grupos está en
[guia-para-otros-subgrupos.md](guia-para-otros-subgrupos.md), y quién tiene cada permiso en
[matriz-permisos.md](matriz-permisos.md).

**Regla:** esconder un botón no protege nada. La protección va en la **ruta**.

### Proteger una ruta

En `routes/web.php`, agrega `->middleware('permiso:MODULO.ACCION')`:

```php
Route::get('/tripulaciones', [TripulacionController::class, 'listar'])
    ->middleware('permiso:TRIPULACIONES.VER');

Route::post('/tripulaciones', [TripulacionController::class, 'guardar'])
    ->middleware('permiso:TRIPULACIONES.CREAR');
```

Si el usuario no inició sesión, lo manda al login. Si no tiene el permiso, le muestra "Acceso
denegado" (403) y lo anota en la bitácora. Tu controlador ni se ejecuta.

Para varias rutas del mismo permiso, en grupo:

```php
Route::middleware('permiso:TRIPULACIONES.VER')->group(function () {
    Route::get('/tripulaciones', [TripulacionController::class, 'listar']);
    Route::get('/tripulaciones/{id}', [TripulacionController::class, 'mostrar']);
});
```

### Ocultar botones (opcional, solo estético)

```blade
@can('TRIPULACIONES.EDITAR')
    <a href="...">Editar</a>
@endcan
```

### Saber quién está usando el sistema

```php
auth()->user()->ID_usuario
auth()->user()->Nombre
auth()->user()->rol->Nombre_rol
auth()->user()->organizacion->Nombre   // Marina, Cipher Pol o Gobierno Afiliado
```

Sirve para guardar quién hizo cada cambio (RNF08).

### Nombres de permisos que existen

**Módulos:** `TRIPULACIONES`, `RECOMPENSAS`, `ORDENES_CAPTURA`, `NAVEGACION`, `EVENTOS`,
`ALIANZAS`, `AMENAZAS`, `INFORMES`, `DASHBOARD`, `USUARIOS`

**Acciones:** `VER`, `CREAR`, `EDITAR`, `ELIMINAR`

Siempre con punto: `TRIPULACIONES.CREAR`. Si te falta uno, pídelo al grupo de Login: no lo
inventes, porque si no está en la tabla `permisos` nadie lo va a tener y todos verán 403.

### Cómo comprobar que quedó bien

Entra con un usuario que **no** tenga el permiso, escribe la dirección a mano en el navegador y
debe salir "Acceso denegado".

---

## 4. Qué archivo hace qué

| Archivo | Rebanada | Qué hace |
|---|---|---|
| `app/Models/Usuario.php` | A | El usuario. Le dice a Laravel que la tabla es `usuarios` y la clave está en `Contrasena` |
| `app/Http/Controllers/AuthController.php` | A | Login, registro, pendiente, cerrar sesión |
| `resources/views/layouts/auth.blade.php`, `auth/*`, `inicio.blade.php` | A | Pantallas (diseño de la rebanada A) |
| `public/css/tema.css` | A | Colores por organización |
| `app/Http/Middleware/VerificarPermiso.php` | B | El guardia `permiso:MODULO.ACCION` |
| `app/Providers/AppServiceProvider.php` | B | `@can` / `Gate`, y los avisos de login que van a la bitácora |
| `app/Models/Bitacora.php`, `BitacoraController.php`, `bitacora/index.blade.php` | B | Bitácora y su consulta |
| `resources/views/errors/403.blade.php` | B | Pantalla de acceso denegado |
| `database/migrations/2026_09_28_000001_...` | B | Tabla de la bitácora (temporal) |
| `database/seeders/PermisoSeeder.php` | B | Los 41 permisos y la matriz por rol |
| `app/Models/Rol.php`, `Permiso.php` | C | Roles y permisos |
| `app/Http/Controllers/UsuarioController.php`, `RolPermisoController.php` | C | Administrar usuarios y la matriz |
| `resources/views/usuarios/*`, `roles/asignarPermisos.blade.php` | C | Pantallas de administración |
| `database/seeders/RolSeeder.php`, `OrganizacionSeeder.php` | C | Los 7 roles y las 3 organizaciones |
| `database/seeders/UsuarioDemoSeeder.php` | — | Usuarios de prueba, solo para local |
| `tests/Feature/LoginYPermisosTest.php` | — | Pruebas automáticas |

---

## 5. Pendientes

- **La matriz de permisos es una propuesta.** Hay que cuadrarla con el reparto por rol del CTO
  (análisis del IEEE, 26/08) y cambiar `MATRIZ` en `PermisoSeeder.php`.
- **El `.sql` oficial tiene dos comentarios sin espacio** (líneas 72 y 243) que cortan la
  importación. Avisar al CTO.
- **La tabla `bitacora_accesos`** tiene que entrar al `.sql` oficial. Cuando entre, se borra la migración.
- **`Estado` en la tabla `usuarios` arranca en `'Activo'`.** El registro escribe `'Pendiente'`
  a propósito, pero lo más seguro es que el `.sql` arranque en `'Pendiente'`.
- **Diagrama MVC (grupo 1):** falta `AuthController`, `BitacoraController`, el modelo `Bitacora`,
  las vistas de registro, pendiente, 403 y bitácora, y las funciones de aprobar/rechazar/suspender.
- **`UsuarioController` usa `DB::table('usuarios')`**. Funciona, pero podría usar el modelo `Usuario`.
- **La ruta `/mapa` no está protegida.** Es del grupo de Dashboard: cuando quieran, le agregan
  `->middleware('permiso:NAVEGACION.VER')`.
