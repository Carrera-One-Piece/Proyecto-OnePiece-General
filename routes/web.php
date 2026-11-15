<?php

use App\Http\Controllers\ReporteEstrategicoController;
use App\Http\Controllers\InformeInteligenciaController;
use App\Http\Controllers\ReporteInteligenciaController;
use App\Http\Controllers\TripulacionController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BitacoraController;
use App\Http\Controllers\ConsultaRecompensaController;
use App\Http\Controllers\EventoController;
use App\Http\Controllers\MapaController;
use App\Http\Controllers\NavegacionController;
use App\Http\Controllers\RecompensaController;
use App\Http\Controllers\RolPermisoController;
use App\Http\Controllers\UbicacionController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


/*
|--------------------------------------------------------------------------
| Login y Registro (grupo Login y Registro)
|--------------------------------------------------------------------------
| 'guest'  = solo para quien NO ha iniciado sesión.
| 'auth'   = solo para quien SÍ inició sesión.
| 'permiso:MODULO.ACCION' = el guardia: además revisa el rol (ver
|           docs/login-y-permisos.md para usarlo en otros módulos).
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'mostrarLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/registro', [AuthController::class, 'mostrarRegistro'])->name('registro');
    Route::post('/registro', [AuthController::class, 'registrar']);
    Route::get('/pendiente', [AuthController::class, 'pendiente'])->name('pendiente');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::view('/inicio', 'inicio')->name('inicio');
    // Centro de Inteligencia Mundial: informes consolidados.
    Route::get('/inteligencia/informes', [InformeInteligenciaController::class, 'index'])
        ->middleware('permiso:INFORMES.VER')
        ->name('inteligencia.informes.index');

    Route::get('/inteligencia/informes/crear', [InformeInteligenciaController::class, 'create'])
        ->middleware('permiso:INFORMES.CREAR')
        ->name('inteligencia.informes.create');

    Route::post('/inteligencia/informes', [InformeInteligenciaController::class, 'store'])
        ->middleware('permiso:INFORMES.CREAR')
        ->name('inteligencia.informes.store');

    Route::get('/inteligencia/informes/{id}', [InformeInteligenciaController::class, 'show'])
        ->whereNumber('id')
        ->middleware('permiso:INFORMES.VER')
        ->name('inteligencia.informes.show');

            Route::get('/inteligencia/estrategico', [ReporteEstrategicoController::class, 'index'])
        ->middleware('permiso:INFORMES.VER')
        ->name('inteligencia.estrategico.index');

    Route::get('/usuarios', [UsuarioController::class, 'listar'])
        ->middleware('permiso:USUARIOS.VER')->name('usuarios.index');
    Route::get('/usuarios/pendientes', [UsuarioController::class, 'pendientes'])
        ->middleware('permiso:USUARIOS.VER')->name('usuarios.pendientes');
    Route::get('/usuarios/{id}/aprobar', [UsuarioController::class, 'aprobarForm'])
        ->whereNumber('id')->middleware('permiso:USUARIOS.EDITAR')->name('usuarios.aprobar.form');
    Route::post('/usuarios/{id}/aprobar', [UsuarioController::class, 'aprobar'])
        ->whereNumber('id')->middleware('permiso:USUARIOS.EDITAR')->name('usuarios.aprobar');
    Route::patch('/usuarios/{id}/rechazar', [UsuarioController::class, 'rechazar'])
        ->whereNumber('id')->middleware('permiso:USUARIOS.EDITAR')->name('usuarios.rechazar');
    Route::patch('/usuarios/{id}/suspender', [UsuarioController::class, 'suspender'])
        ->whereNumber('id')->middleware('permiso:USUARIOS.EDITAR')->name('usuarios.suspender');
    // Matriz de permisos por rol.
    Route::get('/roles/permisos', [RolPermisoController::class, 'listar'])
        ->middleware('permiso:USUARIOS.VER')->name('roles.permisos');
    Route::post('/roles/permisos', [RolPermisoController::class, 'update'])
        ->middleware('permiso:USUARIOS.EDITAR')->name('roles.permisos.update');

    // Bitácora de accesos (RNF08).
    Route::get('/bitacora', [BitacoraController::class, 'listar'])
        ->middleware('permiso:BITACORA.VER')->name('bitacora.index');

    // Recompensas (RF06-RF08). Consultar: rebanada A. Cambiar: rebanada B.
    Route::get('/recompensas', [ConsultaRecompensaController::class, 'listar'])
        ->middleware('permiso:RECOMPENSAS.VER')->name('recompensas.index');
    Route::get('/recompensas/pirata/{id}', [ConsultaRecompensaController::class, 'pirata'])
        ->whereNumber('id')->middleware('permiso:RECOMPENSAS.VER')->name('recompensas.pirata');
    Route::get('/recompensas/tripulacion/{id}', [ConsultaRecompensaController::class, 'tripulacion'])
        ->whereNumber('id')->middleware('permiso:RECOMPENSAS.VER')->name('recompensas.tripulacion');
    Route::get('/recompensas/crear', [RecompensaController::class, 'crear'])
        ->middleware('permiso:RECOMPENSAS.CREAR')->name('recompensas.crear');
    Route::post('/recompensas', [RecompensaController::class, 'guardar'])
        ->middleware('permiso:RECOMPENSAS.CREAR')->name('recompensas.guardar');
    Route::get('/recompensas/{id}/editar', [RecompensaController::class, 'editar'])
        ->whereNumber('id')->middleware('permiso:RECOMPENSAS.EDITAR')->name('recompensas.editar');
    Route::put('/recompensas/{id}', [RecompensaController::class, 'actualizar'])
        ->whereNumber('id')->middleware('permiso:RECOMPENSAS.EDITAR')->name('recompensas.actualizar');
    Route::post('/recompensas/{id}/aumentar', [RecompensaController::class, 'aumentar'])
        ->whereNumber('id')->middleware('permiso:RECOMPENSAS.EDITAR')->name('recompensas.aumentar');
    Route::post('/recompensas/{id}/cancelar', [RecompensaController::class, 'cancelar'])
        ->whereNumber('id')->middleware('permiso:RECOMPENSAS.EDITAR')->name('recompensas.cancelar');
    Route::delete('/recompensas/{id}', [RecompensaController::class, 'eliminar'])
        ->whereNumber('id')->middleware('permiso:RECOMPENSAS.ELIMINAR')->name('recompensas.eliminar');
    // Tripulaciones (RF01-RF05). Controlador: Sebastián.
    Route::get('/tripulaciones', [TripulacionController::class, 'index'])
        ->middleware('permiso:TRIPULACIONES.VER')->name('tripulaciones.index');
    Route::get('/tripulaciones/{id}', [TripulacionController::class, 'show'])
        ->whereNumber('id')->middleware('permiso:TRIPULACIONES.VER')->name('tripulaciones.show');
    Route::post('/tripulaciones', [TripulacionController::class, 'store'])
        ->middleware('permiso:TRIPULACIONES.CREAR')->name('tripulaciones.store');
    Route::put('/tripulaciones/{id}', [TripulacionController::class, 'update'])
        ->whereNumber('id')->middleware('permiso:TRIPULACIONES.EDITAR')->name('tripulaciones.update');
    Route::delete('/tripulaciones/{id}', [TripulacionController::class, 'destroy'])
        ->whereNumber('id')->middleware('permiso:TRIPULACIONES.EDITAR')->name('tripulaciones.destroy');
    // Módulo Navegación (RF10-RF12).
    // Mapa navegable: islas, tripulaciones, rutas y eventos. Quien tenga
    // NAVEGACION.EDITAR puede ubicar tripulaciones y barcos desde el mapa.
    Route::get('/mapa', [MapaController::class, 'index'])
        ->middleware('permiso:NAVEGACION.VER')->name('mapa');
    Route::get('/mapa/datos', [MapaController::class, 'datos'])
        ->middleware('permiso:NAVEGACION.VER')->name('mapa.datos');

    // Endpoints JSON de Navegación: rutas entre islas, recorridos y predicciones.
    Route::get('/navegacion/conexiones', [NavegacionController::class, 'conexiones'])
        ->middleware('permiso:NAVEGACION.VER')->name('navegacion.conexiones');
    Route::get('/navegacion/recorridos/{tipo}/{id}', [NavegacionController::class, 'recorrido'])
        ->whereNumber('id')->middleware('permiso:NAVEGACION.VER')->name('navegacion.recorrido');
    Route::get('/navegacion/predicciones', [NavegacionController::class, 'predicciones'])
        ->middleware('permiso:NAVEGACION.VER')->name('navegacion.predicciones');
    Route::post('/navegacion/predicciones', [NavegacionController::class, 'predecir'])
        ->middleware('permiso:NAVEGACION.CREAR')->name('navegacion.predecir');

    // Página de prueba de Karen: se deja tal cual, solo para validar a ojo.
    Route::view('/ubicaciones/prueba', 'ubicaciones.prueba')
        ->middleware('permiso:NAVEGACION.VER');

    // Reemplaza el cierre provisional de Karen por el controller real.
    Route::put('/ubicaciones/{tipo}/{id}', [UbicacionController::class, 'actualizar'])
        ->middleware('permiso:NAVEGACION.EDITAR')->name('ubicaciones.actualizar');

    // Módulo Eventos (RF13-RF15).
    // Página de prueba para verificar el CRUD a ojo (borrar cuando haya interfaz real).
    Route::view('/eventos/prueba', 'eventos.prueba')
        ->middleware('permiso:EVENTOS.VER')->name('eventos.prueba');
    Route::get('/eventos', [EventoController::class, 'index'])
        ->middleware('permiso:EVENTOS.VER')->name('eventos.index');
    Route::get('/eventos/{id}', [EventoController::class, 'show'])
        ->whereNumber('id')->middleware('permiso:EVENTOS.VER')->name('eventos.show');
    Route::post('/eventos', [EventoController::class, 'store'])
        ->middleware('permiso:EVENTOS.CREAR')->name('eventos.store');
    Route::put('/eventos/{id}', [EventoController::class, 'update'])
        ->whereNumber('id')->middleware('permiso:EVENTOS.EDITAR')->name('eventos.update');
    Route::delete('/eventos/{id}', [EventoController::class, 'destroy'])
        ->whereNumber('id')->middleware('permiso:EVENTOS.ELIMINAR')->name('eventos.destroy');

    // Módulo Inteligencia (RF16-RF20).
    // Centro de Inteligencia Mundial: reportes recibidos.
    Route::get('/inteligencia/reportes', [ReporteInteligenciaController::class, 'index'])
        ->middleware('permiso:INFORMES.VER')
        ->name('inteligencia.reportes.index');

    Route::get('/inteligencia/reportes/crear', [ReporteInteligenciaController::class, 'create'])
        ->middleware('permiso:INFORMES.CREAR')
        ->name('inteligencia.reportes.create');

    Route::post('/inteligencia/reportes', [ReporteInteligenciaController::class, 'store'])
        ->middleware('permiso:INFORMES.CREAR')
        ->name('inteligencia.reportes.store');
});
