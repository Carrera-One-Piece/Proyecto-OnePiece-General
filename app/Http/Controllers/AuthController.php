<?php

namespace App\Http\Controllers;

use App\Models\Organizacion;
use App\Models\Usuario;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Rebanada A — Entrar y registrarse.
 *
 * Lo que Laravel ya hace solo: cifrar la contraseña, manejar la sesión,
 * cambiar el ID de sesión al entrar (contra la fijación de sesión) y la
 * protección CSRF de los formularios.
 */
class AuthController extends Controller
{
    // Después de 5 intentos fallidos con el mismo correo, hay que esperar.
    private const MAX_INTENTOS = 5;

    private const MENSAJE_GENERICO = 'Credenciales no reconocidas. Verifique su correo y su clave.';

    public function mostrarLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credenciales = $request->validate([
            'Correo' => ['required', 'email'],
            'Contrasena' => ['required', 'string'],
        ], [
            'Correo.required' => 'Escriba su correo.',
            'Correo.email' => 'El correo no tiene un formato válido.',
            'Contrasena.required' => 'Escriba su clave.',
        ]);

        $llave = Str::lower($credenciales['Correo']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($llave, self::MAX_INTENTOS)) {
            $segundos = RateLimiter::availableIn($llave);

            return $this->volverAlLogin("Demasiados intentos. Espere {$segundos} segundos e intente de nuevo.");
        }

        // Auth::attemptWhen revisa correo y contraseña, y además que el usuario esté Activo.
        // Laravel busca por 'Correo' y compara 'password' contra la columna Contrasena.
        $entro = Auth::attemptWhen(
            ['Correo' => $credenciales['Correo'], 'password' => $credenciales['Contrasena']],
            fn (Usuario $usuario) => $usuario->estaActivo()
        );

        if (! $entro) {
            RateLimiter::hit($llave);

            return $this->volverAlLogin($this->motivoDelRechazo($credenciales['Contrasena']));
        }

        RateLimiter::clear($llave);
        $request->session()->regenerate();

        return redirect()->intended(route('inicio'));
    }

    public function mostrarRegistro(): View
    {
        return view('auth.registro', [
            'organizaciones' => Organizacion::gubernamentales()->get(),
        ]);
    }

    public function registrar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'Nombre' => ['required', 'string', 'max:150'],
            'Correo' => ['required', 'email', 'max:150', 'unique:usuarios,Correo'],
            // Solo organizaciones del gobierno: nadie se registra como "Baroque Works".
            'ID_organizacion' => [
                'required',
                'integer',
                Rule::exists('organizaciones', 'ID_organizacion')->where('Tipo', Organizacion::TIPO_GOBIERNO),
            ],
            'Contrasena' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'Nombre.required' => 'El nombre es obligatorio.',
            'Correo.required' => 'El correo es obligatorio.',
            'Correo.email' => 'El correo no tiene un formato válido.',
            'Correo.unique' => 'Ya existe una cuenta registrada con ese correo.',
            'ID_organizacion.required' => 'Debe seleccionar una organización.',
            'ID_organizacion.exists' => 'Debe seleccionar una organización válida.',
            'Contrasena.required' => 'Debe escribir la clave y su confirmación.',
            'Contrasena.min' => 'La clave debe tener al menos 8 caracteres.',
            'Contrasena.confirmed' => 'Las claves no coinciden.',
        ]);

        $usuario = Usuario::create([
            'Nombre' => $datos['Nombre'],
            'Correo' => $datos['Correo'],
            'Contrasena' => Hash::make($datos['Contrasena']),
            'ID_organizacion' => $datos['ID_organizacion'],
            // Se escribe a propósito: en la tabla el valor por defecto es 'Activo',
            // y eso dejaría entrar a cualquiera sin aprobación.
            'Estado' => Usuario::ESTADO_PENDIENTE,
            'ID_rol' => null,
        ]);

        // Aviso para la bitácora (lo escucha AppServiceProvider).
        event(new Registered($usuario));

        return redirect()->route('pendiente');
    }

    public function pendiente(): View
    {
        return view('auth.pendiente');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('aviso', 'Sesión cerrada.');
    }

    /**
     * Si la contraseña era correcta pero la cuenta no está activa, se dice por qué.
     * Si no, se da el mensaje genérico para no revelar qué correos existen.
     */
    private function motivoDelRechazo(string $contrasena): string
    {
        $usuario = Auth::getLastAttempted();

        if (! $usuario || ! Hash::check($contrasena, $usuario->getAuthPassword())) {
            return self::MENSAJE_GENERICO;
        }

        return match ($usuario->Estado) {
            Usuario::ESTADO_PENDIENTE => 'Su solicitud aún está pendiente de aprobación.',
            Usuario::ESTADO_SUSPENDIDO => 'Su cuenta se encuentra suspendida. Contacte al administrador.',
            default => 'No es posible ingresar en este momento.',
        };
    }

    private function volverAlLogin(string $mensaje): RedirectResponse
    {
        return back()->withInput(['Correo' => request('Correo')])->withErrors(['Correo' => $mensaje]);
    }
}
