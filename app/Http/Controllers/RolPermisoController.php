<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Models\Permiso;
use App\Models\Rol;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RolPermisoController extends Controller
{
    // Sin este permiso nadie podría volver a administrar usuarios ni la matriz.
    private const PERMISO_ADMINISTRAR = 'USUARIOS.EDITAR';

    /**
     * Matriz de permisos de un rol. (Se llama listar() como en el diagrama MVC.)
     */
    public function listar(Request $request)
    {
        $roles = Rol::orderBy('Nombre_rol')->get();

        $rolSeleccionado = null;
        $permisosAsignados = collect();

        if ($request->filled('rol')) {
            $rolSeleccionado = Rol::with('permisos')
                ->findOrFail($request->rol);

            $permisosAsignados = $rolSeleccionado
                ->permisos
                ->pluck('ID_permiso');
        }

        $permisos = Permiso::orderBy(
            'Nombre_permiso'
        )->get();

        

        $acciones = collect();

        foreach ($permisos as $permiso) {
            $partes = explode(
                '.',
                $permiso->Nombre_permiso,
                2
            );

            $accion = $partes[1] ?? 'GENERAL';

            $acciones->push($accion);
        }

        $acciones = $acciones
            ->unique()
            ->sort()
            ->values();

        $matriz = [];

        foreach ($permisos as $permiso) {
            $partes = explode(
                '.',
                $permiso->Nombre_permiso,
                2
            );

            $modulo = $partes[0];
            $accion = $partes[1] ?? 'GENERAL';

            $matriz[$modulo][$accion] = $permiso;
        }

        return view(
            'roles.asignarPermisos',
            compact(
                'roles',
                'rolSeleccionado',
                'permisosAsignados',
                'acciones',
                'matriz'
            )
        );
    }

    
    public function update(Request $request)
    {
        $validated = $request->validate([
            'ID_rol' => [
                'required',
                'integer',
                'exists:roles,ID_rol'
            ],

            'permisos' => [
                'nullable',
                'array'
            ],

            'permisos.*' => [
                'integer',
                'exists:permisos,ID_permiso'
            ],
        ]);

        $rol = Rol::findOrFail(
            $validated['ID_rol']
        );

        $permisos = $validated['permisos'] ?? [];

        // Nadie le quita a su propio rol el permiso de administrar: el sistema
        // quedaría sin nadie que pueda aprobar usuarios ni cambiar la matriz.
        $esSuRol = (int) Auth::user()?->ID_rol === (int) $rol->ID_rol;
        $idAdministrar = Permiso::where('Nombre_permiso', self::PERMISO_ADMINISTRAR)->value('ID_permiso');

        if ($esSuRol && $idAdministrar && ! in_array($idAdministrar, array_map('intval', $permisos), true)) {
            return redirect()
                ->route('roles.permisos', ['rol' => $rol->ID_rol])
                ->with(
                    'error',
                    'No puede quitarle a su propio rol el permiso '.self::PERMISO_ADMINISTRAR.'.'
                );
        }

        DB::transaction(function () use (
            $rol,
            $permisos
        ) {
            $rol->permisos()->sync($permisos);
        });

        Bitacora::registrar(
            Bitacora::PERMISOS_CAMBIADOS,
            Auth::user()?->ID_usuario,
            Auth::user()?->Correo,
            "Rol {$rol->Nombre_rol}: quedó con ".count($permisos).' permisos'
        );

        return redirect()
            ->route(
                'roles.permisos',
                ['rol' => $rol->ID_rol]
            )
            ->with(
                'success',
                'Permisos actualizados correctamente.'
            );
    }
}