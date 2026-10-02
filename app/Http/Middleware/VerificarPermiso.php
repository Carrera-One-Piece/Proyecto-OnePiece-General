<?php

namespace App\Http\Middleware;

use App\Models\Bitacora;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * El "guardia" (RNF02 — Control de acceso).
 *
 * Se pega a una ruta en routes/web.php:
 *
 *     Route::get('/tripulaciones/crear', [TripulacionController::class, 'mostrarFormCreacion'])
 *         ->middleware('permiso:TRIPULACIONES.CREAR');
 *
 * Si el usuario no tiene el permiso, el controlador ni se ejecuta: se muestra
 * la pantalla 403 y el intento queda en la bitácora. Así se protege también
 * a quien escribe la dirección a mano en el navegador.
 */
class VerificarPermiso
{
    public function handle(Request $request, Closure $next, string $permiso): Response
    {
        $usuario = $request->user();

        if (! $usuario) {
            return redirect()->guest(route('login'));
        }

        // Si lo suspendieron mientras tenía la sesión abierta, se le saca.
        if (! $usuario->estaActivo()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['Correo' => 'Su cuenta ya no está activa.']);
        }

        if (! $usuario->tienePermiso($permiso)) {
            Bitacora::registrar(
                Bitacora::ACCESO_DENEGADO,
                $usuario->ID_usuario,
                $usuario->Correo,
                "Sin permiso {$permiso} en /{$request->path()}"
            );

            abort(403, 'No tiene permiso para entrar a esta sección.');
        }

        return $next($request);
    }
}
