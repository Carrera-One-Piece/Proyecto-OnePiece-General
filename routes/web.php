<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BitacoraController;
use App\Http\Controllers\MapaController;
use App\Http\Controllers\RolPermisoController;
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
});
