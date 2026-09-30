<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Consulta de la bitácora de accesos. Solo lectura: nadie edita ni borra líneas.
 */
class BitacoraController extends Controller
{
    public function listar(Request $request): View
    {
        $request->validate([
            'correo' => ['nullable', 'string', 'max:150'],
            'accion' => ['nullable', 'string', 'max:50'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
        ], [
            'hasta.after_or_equal' => 'La fecha "hasta" no puede ser anterior a "desde".',
        ]);

        $consulta = Bitacora::query()->orderByDesc('Fecha')->orderByDesc('ID_bitacora');

        if ($request->filled('correo')) {
            $consulta->where('Correo_intento', 'like', '%'.$request->correo.'%');
        }

        if ($request->filled('accion')) {
            $consulta->where('Accion', $request->accion);
        }

        if ($request->filled('desde')) {
            $consulta->whereDate('Fecha', '>=', $request->desde);
        }

        if ($request->filled('hasta')) {
            $consulta->whereDate('Fecha', '<=', $request->hasta);
        }

        return view('bitacora.index', [
            'registros' => $consulta->paginate(20)->withQueryString(),
            'acciones' => [
                Bitacora::LOGIN_OK, Bitacora::LOGIN_FALLIDO, Bitacora::LOGOUT, Bitacora::REGISTRO,
                Bitacora::ACCESO_DENEGADO, Bitacora::USUARIO_APROBADO, Bitacora::USUARIO_RECHAZADO,
                Bitacora::USUARIO_SUSPENDIDO, Bitacora::PERMISOS_CAMBIADOS,
            ],
        ]);
    }
}
