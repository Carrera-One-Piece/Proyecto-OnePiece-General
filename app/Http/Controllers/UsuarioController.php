<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Models\Rol;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UsuarioController extends Controller
{
    /**
     * Lista de usuarios con filtros por estado y rol.
     * (Se llama listar() como en el diagrama MVC.)
     */
    public function listar(Request $request)
    {
        $query = DB::table('usuarios')
            ->leftJoin(
                'roles',
                'usuarios.ID_rol',
                '=',
                'roles.ID_rol'
            )
            ->leftJoin(
                'organizaciones',
                'usuarios.ID_organizacion',
                '=',
                'organizaciones.ID_organizacion'
            )
            ->select(
                'usuarios.ID_usuario',
                'usuarios.Nombre',
                'usuarios.Correo',
                'usuarios.Estado',
                'usuarios.ID_rol',
                'usuarios.ID_organizacion',
                'roles.Nombre_rol',
                'organizaciones.Nombre as Nombre_organizacion'
            );

        // Filtro por estado
        if ($request->filled('estado')) {
            $query->where(
                'usuarios.Estado',
                $request->estado
            );
        }

        // Filtro por rol
        if ($request->filled('rol')) {
            $query->where(
                'usuarios.ID_rol',
                $request->rol
            );
        }

        $usuarios = $query
            ->orderBy('usuarios.Nombre')
            ->paginate(10)
            ->withQueryString();

        $roles = Rol::orderBy('Nombre_rol')->get();

        return view(
            'usuarios.index',
            compact('usuarios', 'roles')
        );
    }

    /**
     * Muestra los usuarios que están pendientes de aprobación.
     */
    public function pendientes()
    {
        $usuarios = DB::table('usuarios')
            ->leftJoin(
                'roles',
                'usuarios.ID_rol',
                '=',
                'roles.ID_rol'
            )
            ->leftJoin(
                'organizaciones',
                'usuarios.ID_organizacion',
                '=',
                'organizaciones.ID_organizacion'
            )
            ->where(
                'usuarios.Estado',
                'Pendiente'
            )
            ->select(
                'usuarios.ID_usuario',
                'usuarios.Nombre',
                'usuarios.Correo',
                'usuarios.Estado',
                'roles.Nombre_rol',
                'organizaciones.Nombre as Nombre_organizacion'
            )
            ->orderBy('usuarios.Nombre')
            ->get();

        return view(
            'usuarios.aprobar',
            [
                'usuario' => null,
                'usuarios' => $usuarios,
                'roles' => Rol::orderBy('Nombre_rol')->get()
            ]
        );
    }

    /**
     * Muestra el formulario para aprobar un usuario pendiente.
     */
    public function aprobarForm(int $id)
    {
        $usuario = DB::table('usuarios')
            ->leftJoin(
                'organizaciones',
                'usuarios.ID_organizacion',
                '=',
                'organizaciones.ID_organizacion'
            )
            ->where(
                'usuarios.ID_usuario',
                $id
            )
            ->where(
                'usuarios.Estado',
                'Pendiente'
            )
            ->select(
                'usuarios.ID_usuario',
                'usuarios.Nombre',
                'usuarios.Correo',
                'usuarios.Estado',
                'organizaciones.Nombre as Nombre_organizacion'
            )
            ->first();

        // Solo se puede abrir el formulario
        // cuando el usuario está pendiente.
        abort_if(!$usuario, 404);

        $roles = Rol::orderBy('Nombre_rol')->get();

        return view(
            'usuarios.aprobar',
            [
                'usuario' => $usuario,
                'usuarios' => collect(),
                'roles' => $roles
            ]
        );
    }

    /**
     * Aprueba un usuario pendiente y le asigna un rol.
     */
    public function aprobar(Request $request, int $id)
    {
        $validated = $request->validate([
            'ID_rol' => [
                'required',
                'integer',
                'exists:roles,ID_rol'
            ],
        ]);

        $actualizados = DB::table('usuarios')
            ->where(
                'ID_usuario',
                $id
            )
            ->where(
                'Estado',
                'Pendiente'
            )
            ->update([
                'ID_rol' => $validated['ID_rol'],
                'Estado' => 'Activo',
            ]);

        if ($actualizados === 0) {
            return redirect()
                ->route('usuarios.index')
                ->with(
                    'error',
                    'El usuario no existe o ya no está pendiente.'
                );
        }

        $this->anotarEnBitacora(Bitacora::USUARIO_APROBADO, $id, 'Rol asignado: '.Rol::find($validated['ID_rol'])?->Nombre_rol);

        return redirect()
            ->route('usuarios.index')
            ->with(
                'success',
                'Usuario aprobado correctamente.'
            );
    }

    /**
     * Rechaza un usuario cambiando su estado a Inactivo.
     * No elimina el registro de la base de datos.
     */
    public function rechazar(int $id)
    {
        $actualizados = DB::table('usuarios')
            ->where(
                'ID_usuario',
                $id
            )
            ->where(
                'Estado',
                'Pendiente'
            )
            ->update([
                'Estado' => 'Inactivo'
            ]);

        if ($actualizados === 0) {
            return redirect()
                ->route('usuarios.index')
                ->with(
                    'error',
                    'El usuario no existe o ya no está pendiente.'
                );
        }

        $this->anotarEnBitacora(Bitacora::USUARIO_RECHAZADO, $id);

        return redirect()
            ->route('usuarios.index')
            ->with(
                'success',
                'Usuario rechazado correctamente.'
            );
    }

    /**
     * Suspende un usuario cambiando su estado a Suspendido.
     * No elimina el registro de la base de datos.
     */
    public function suspender(int $id)
    {
        // Nadie puede suspenderse a sí mismo: el sistema podría quedar sin administrador.
        if ($id === (int) Auth::id()) {
            return redirect()
                ->route('usuarios.index')
                ->with(
                    'error',
                    'No puede suspender su propia cuenta.'
                );
        }

        $actualizados = DB::table('usuarios')
            ->where(
                'ID_usuario',
                $id
            )
            ->where(
                'Estado',
                'Activo'
            )
            ->update([
                'Estado' => 'Suspendido'
            ]);

        if ($actualizados === 0) {
            return redirect()
                ->route('usuarios.index')
                ->with(
                    'error',
                    'El usuario no existe o no está activo.'
                );
        }

        $this->anotarEnBitacora(Bitacora::USUARIO_SUSPENDIDO, $id);

        return redirect()
            ->route('usuarios.index')
            ->with(
                'success',
                'Usuario suspendido correctamente.'
            );
    }

    /**
     * Deja en la bitácora qué administrador hizo el cambio y a quién (RNF08).
     */
    private function anotarEnBitacora(string $accion, int $idAfectado, ?string $detalle = null): void
    {
        $admin = Auth::user();

        Bitacora::registrar(
            $accion,
            $admin?->ID_usuario,
            $admin?->Correo,
            trim("Usuario afectado: {$idAfectado}. ".($detalle ?? ''))
        );
    }
}