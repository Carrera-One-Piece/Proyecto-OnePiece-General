<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IslaController;
use App\Http\Controllers\UbicacionController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/islas', [IslaController::class, 'index']);

// Módulo Navegación: ubicación actual de cada entidad, para que el
// mapa la pinte igual que las islas (mismo formato de respuesta que
// /api/islas: {id, nombre, coords: [x, y]}, más el 'tipo').
Route::get('/ubicaciones', [UbicacionController::class, 'index'])
    ->middleware(['auth', 'permiso:NAVEGACION.VER']);
