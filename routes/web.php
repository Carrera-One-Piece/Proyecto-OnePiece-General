<?php

use App\Http\Controllers\TripulacionController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BitacoraController;
use App\Http\Controllers\ConsultaRecompensaController;
use App\Http\Controllers\EventoController;
use App\Http\Controllers\MapaController;
use App\Http\Controllers\RecompensaController;
use App\Http\Controllers\RolPermisoController;
use App\Http\Controllers\UbicacionController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/mapa', [MapaController::class, 'index']);

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

    // Administrar usuarios (RF27). Solo el Experto en Seguridad puede cambiar cosas.
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
});
    // Módulo Navegación (RF11-RF12).
    // Página de prueba de Karen: se deja tal cual, solo para validar a ojo.
    Route::view('/ubicaciones/prueba', 'ubicaciones.prueba')
        ->middleware('permiso:NAVEGACION.VER');

    // Reemplaza el cierre provisional de Karen por el controller real.
    Route::put('/ubicaciones/{tipo}/{id}', [UbicacionController::class, 'actualizar'])
        ->middleware('permiso:NAVEGACION.EDITAR');

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
});
