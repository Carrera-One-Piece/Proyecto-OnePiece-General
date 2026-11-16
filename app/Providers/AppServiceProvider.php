<?php

namespace App\Providers;

use App\Models\Bitacora;
use App\Models\Usuario;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->definirPermisos();
        $this->registrarEventosEnBitacora();
    }

    /**
     * Todo permiso con forma MODULO.ACCION (por ejemplo TRIPULACIONES.CREAR) se
     * responde mirando la tabla rol_permiso. Así funcionan:
     *   - en un controlador:  Gate::allows('TRIPULACIONES.CREAR')
     *   - en una vista:       @can('TRIPULACIONES.CREAR') ... @endcan
     */
    private function definirPermisos(): void
    {
        Gate::before(function (Usuario $usuario, string $permiso) {
            if (str_contains($permiso, '.')) {
                return $usuario->tienePermiso($permiso);
            }

            return null; // no es de los nuestros: que decida otra regla
        });
    }

    /**
     * Laravel avisa solo cuando alguien entra, falla, sale o se registra.
     * Aquí se escuchan esos avisos y se guardan en la bitácora, sin tocar
     * el código del login.
     */
    private function registrarEventosEnBitacora(): void
    {
        Event::listen(function (Login $evento) {
            Bitacora::registrar(Bitacora::LOGIN_OK, $evento->user->ID_usuario, $evento->user->Correo);
        });

        Event::listen(function (Failed $evento) {
            Bitacora::registrar(
                Bitacora::LOGIN_FALLIDO,
                $evento->user?->ID_usuario,
                $evento->credentials['Correo'] ?? null
            );
        });

        Event::listen(function (Logout $evento) {
            if ($evento->user) {
                Bitacora::registrar(Bitacora::LOGOUT, $evento->user->ID_usuario, $evento->user->Correo);
            }
        });

        Event::listen(function (Registered $evento) {
            Bitacora::registrar(
                Bitacora::REGISTRO,
                $evento->user->ID_usuario,
                $evento->user->Correo,
                'Solicitud de alistamiento (queda Pendiente)'
            );
        });
    }
}
