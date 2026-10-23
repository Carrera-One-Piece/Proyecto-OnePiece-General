<?php

namespace App\Http\Controllers;

use App\Http\Requests\UbicacionRequest;
use App\Models\Barco;
use App\Models\Organizacion;
use App\Models\Personaje;
use App\Models\Tripulacion;
use App\Models\UbicacionActual;

/**
 * Módulo Navegación (RF11-RF12): ubicación actual de tripulaciones,
 * piratas, barcos y organizaciones (incluida la Marina).
 *
 * Rutas protegidas en routes/web.php con 'permiso:NAVEGACION.VER'
 * (index) y 'permiso:NAVEGACION.EDITAR' (actualizar) — Cipher Pol
 * solo puede ver, el Especialista en Navegación puede editar.
 */
class UbicacionController extends Controller
{
    // tipo => [Modelo, columna del nombre a mostrar]
    private const MODELOS = [
        'tripulacion'  => [Tripulacion::class, 'Nombre'],
        'personaje'    => [Personaje::class, 'Nombre'],
        'barco'        => [Barco::class, 'Nombre'],
        'organizacion' => [Organizacion::class, 'Nombre'], // incluye la Marina
    ];

    /**
     * GET /api/ubicaciones — todas las ubicaciones actuales, en el
     * mismo formato que IslaController::index, para que el mapa las
     * pinte igual que las islas.
     */
    public function index()
    {
        return UbicacionActual::all()->map(fn ($u) => [
            'tipo'   => $u->Tipo_entidad,
            'id'     => $u->ID_entidad,
            'nombre' => $this->nombreDe($u->Tipo_entidad, $u->ID_entidad),
            'coords' => [(float) $u->Coordenada_X, (float) $u->Coordenada_Y],
        ]);
    }

    /**
     * PUT /ubicaciones/{tipo}/{id} — crea o actualiza la ubicación de
     * una entidad. Admite tanto coordenadas escritas a mano como un
     * clic en el mapa: en los dos casos el frontend termina mandando
     * x/y (si el usuario elige una isla, el frontend copia las
     * coordenadas de esa isla antes de llamar esta ruta).
     *
     * $request ya llega validado por UbicacionRequest (tipo, id, x, y).
     */
    public function actualizar(UbicacionRequest $request)
    {
        $datos = $request->validated();

        $ubicacion = UbicacionActual::updateOrCreate(
            [
                'Tipo_entidad' => $datos['tipo'],
                'ID_entidad'   => $datos['id'],
            ],
            [
                'Coordenada_X'         => $datos['x'],
                'Coordenada_Y'         => $datos['y'],
                'Fecha_actualizacion'  => now(),
            ]
        );

        return response()->json([
            'ok'    => true,
            'datos' => [
                'tipo'   => $ubicacion->Tipo_entidad,
                'id'     => $ubicacion->ID_entidad,
                'nombre' => $this->nombreDe($ubicacion->Tipo_entidad, $ubicacion->ID_entidad),
                'coords' => [(float) $ubicacion->Coordenada_X, (float) $ubicacion->Coordenada_Y],
            ],
        ]);
    }

    private function nombreDe(string $tipo, int $id): ?string
    {
        if (! isset(self::MODELOS[$tipo])) {
            return null;
        }

        [$modelo, $campoNombre] = self::MODELOS[$tipo];

        return $modelo::find($id)?->{$campoNombre};
    }
}
