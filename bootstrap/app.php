<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // El guardia de permisos: ->middleware('permiso:TRIPULACIONES.CREAR')
        $middleware->alias([
            'permiso' => \App\Http\Middleware\VerificarPermiso::class,
        ]);

        // Si alguien que ya inició sesión abre /login o /registro, va a su inicio.
        $middleware->redirectUsersTo('/inicio');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
