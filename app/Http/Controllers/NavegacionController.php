<?php

namespace App\Http\Controllers;

use App\Support\MotorPrediccion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use RuntimeException;

/**
 * Endpoints JSON del Módulo de Navegación (RF11-RF12):
 *
 *   GET  /navegacion/conexiones                    rutas entre islas (isla_adyacente)
 *   GET  /navegacion/recorridos/{tipo}/{id}        historial de movimientos de una entidad
 *   GET  /navegacion/predicciones                  predicciones guardadas
 *   POST /navegacion/predicciones                  genera una predicción nueva (motor de reglas)
 *
 * Los permisos están en routes/web.php (NAVEGACION.VER para consultar y
 * NAVEGACION.CREAR para generar predicciones).
 */
class NavegacionController extends Controller
{
    private const TIPOS = ['tripulacion', 'barco', 'personaje', 'organizacion'];

    /**
     * GET /navegacion/conexiones?isla={id}
     * Sin filtro: todas las rutas. Con ?isla: las que salen o llegan a esa isla.
     */
    public function conexiones(Request $request): JsonResponse
    {
        $request->validate(['isla' => ['nullable', 'integer']]);

        if (! Schema::hasTable('isla_adyacente')) {
            return response()->json(['data' => []]);
        }

        $consulta = DB::table('isla_adyacente as c')
            ->leftJoin('isla as o', 'c.ID_isla_origen', '=', 'o.ID_isla')
            ->leftJoin('isla as d', 'c.ID_isla_destino', '=', 'd.ID_isla')
            ->select(
                'c.ID_adyacencia',
                'c.ID_isla_origen', 'o.Nombre as Origen', 'o.Coordenada_X as OX', 'o.Coordenada_Y as OY',
                'c.ID_isla_destino', 'd.Nombre as Destino', 'd.Coordenada_X as DX', 'd.Coordenada_Y as DY',
                'c.Distancia', 'c.Tiempo_estimado', 'c.Observaciones'
            )
            ->orderBy('c.ID_adyacencia');

        if ($isla = $request->integer('isla')) {
            $consulta->where(fn ($q) => $q->where('c.ID_isla_origen', $isla)->orWhere('c.ID_isla_destino', $isla));
        }

        return response()->json([
            'data' => $consulta->get()->map(fn ($c) => [
                'id' => (int) $c->ID_adyacencia,
                'origen' => ['id' => (int) $c->ID_isla_origen, 'nombre' => $c->Origen, 'coords' => $this->coords($c->OX, $c->OY)],
                'destino' => ['id' => (int) $c->ID_isla_destino, 'nombre' => $c->Destino, 'coords' => $this->coords($c->DX, $c->DY)],
                'distancia' => $c->Distancia !== null ? (float) $c->Distancia : null,
                'tiempo_estimado' => $c->Tiempo_estimado !== null ? (float) $c->Tiempo_estimado : null,
                'observaciones' => $c->Observaciones,
            ]),
        ]);
    }

    /**
     * GET /navegacion/recorridos/{tipo}/{id}?desde=YYYY-MM-DD&hasta=YYYY-MM-DD
     *
     * Recorrido en orden de fecha, desde historial_movimientos. Para un barco
     * se suman también sus registros de movimiento_barco. Si una tripulación
     * no tiene historial propio, se usa el de su barco.
     */
    public function recorrido(Request $request, string $tipo, int $id): JsonResponse
    {
        $request->merge(['tipo' => $tipo]);
        $request->validate([
            'tipo' => ['required', Rule::in(self::TIPOS)],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
        ], [
            'tipo.in' => 'El tipo debe ser tripulacion, barco, personaje u organizacion.',
        ]);

        $puntos = $this->puntosDe($tipo, $id);
        $fuente = $tipo;

        if ($puntos->isEmpty() && $tipo === 'tripulacion' && Schema::hasTable('barcos')) {
            $idBarco = DB::table('barcos')->where('ID_tripulacion', $id)->orderBy('ID_barco')->value('ID_barco');
            if ($idBarco) {
                $puntos = $this->puntosDe('barco', (int) $idBarco);
                $fuente = 'barco';
            }
        }

        if ($desde = $request->date('desde')) {
            $puntos = $puntos->filter(fn ($p) => $p['fecha'] && $p['fecha'] >= $desde->startOfDay()->toDateTimeString());
        }
        if ($hasta = $request->date('hasta')) {
            $puntos = $puntos->filter(fn ($p) => $p['fecha'] && $p['fecha'] <= $hasta->endOfDay()->toDateTimeString());
        }

        $motor = new MotorPrediccion;
        $puntos = $puntos->values()->map(function ($p) use ($motor) {
            $p['isla'] = $motor->islaCercana($p['coords'][0], $p['coords'][1])?->Nombre;

            return $p;
        });

        return response()->json([
            'tipo' => $tipo,
            'id' => $id,
            'fuente' => $fuente,
            'total' => $puntos->count(),
            'distancia_total' => round($this->distanciaTotal($puntos), 2),
            'data' => $puntos,
        ]);
    }

    /** GET /navegacion/predicciones?tripulacion={id} */
    public function predicciones(Request $request): JsonResponse
    {
        $request->validate(['tripulacion' => ['nullable', 'integer']]);

        if (! Schema::hasTable('movimientos_predichos')) {
            return response()->json(['data' => []]);
        }

        $consulta = DB::table('movimientos_predichos as p')
            ->leftJoin('tripulaciones as t', 'p.ID_tripulacion', '=', 't.ID_tripulacion')
            ->leftJoin('isla as o', 'p.ID_isla_origen', '=', 'o.ID_isla')
            ->leftJoin('isla as d', 'p.ID_isla_destino_predicha', '=', 'd.ID_isla')
            ->leftJoin('isla as r', 'p.ID_isla_resultado_real', '=', 'r.ID_isla')
            ->select(
                'p.*', 't.Nombre as Tripulacion',
                'o.Nombre as Origen', 'o.Coordenada_X as OX', 'o.Coordenada_Y as OY',
                'd.Nombre as Destino', 'd.Coordenada_X as DX', 'd.Coordenada_Y as DY',
                'r.Nombre as Real'
            )
            ->orderByDesc('p.Fecha_prediccion')
            ->orderByDesc('p.ID_prediccion');

        if ($t = $request->integer('tripulacion')) {
            $consulta->where('p.ID_tripulacion', $t);
        }

        return response()->json([
            'data' => $consulta->limit(200)->get()->map(fn ($p) => [
                'id' => (int) $p->ID_prediccion,
                'tripulacion' => ['id' => (int) $p->ID_tripulacion, 'nombre' => $p->Tripulacion],
                'origen' => $p->ID_isla_origen ? ['id' => (int) $p->ID_isla_origen, 'nombre' => $p->Origen, 'coords' => $this->coords($p->OX, $p->OY)] : null,
                'destino' => $p->ID_isla_destino_predicha ? ['id' => (int) $p->ID_isla_destino_predicha, 'nombre' => $p->Destino, 'coords' => $this->coords($p->DX, $p->DY)] : null,
                'puntaje' => $p->Puntaje_total !== null ? (float) $p->Puntaje_total : null,
                'factores' => $this->factores($p->Factores_aplicados),
                'fecha' => $p->Fecha_prediccion,
                'resultado_real' => $p->ID_isla_resultado_real ? ['id' => (int) $p->ID_isla_resultado_real, 'nombre' => $p->Real] : null,
                'acierto' => $p->Acierto === null ? null : (bool) $p->Acierto,
            ]),
        ]);
    }

    /** POST /navegacion/predicciones  { "tripulacion": 8 } */
    public function predecir(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'tripulacion' => ['required', 'integer', 'exists:tripulaciones,ID_tripulacion'],
        ], [
            'tripulacion.required' => 'Indica la tripulación.',
            'tripulacion.exists' => 'La tripulación no existe.',
        ]);

        try {
            $prediccion = (new MotorPrediccion)->predecir((int) $datos['tripulacion']);
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => ['tripulacion' => [$e->getMessage()]],
            ], 422);
        }

        return response()->json([
            'message' => "Predicción: {$prediccion['tripulacion']['nombre']} irá a {$prediccion['destino']['nombre']}.",
            'data' => $prediccion,
        ], 201);
    }

    // ------------------------------------------------------------ ayudas

    private function puntosDe(string $tipo, int $id): Collection
    {
        $puntos = collect();

        if (Schema::hasTable('historial_movimientos')) {
            $puntos = DB::table('historial_movimientos')
                ->whereRaw('LOWER(Tipo_entidad) = ?', [$tipo])
                ->where('ID_entidad', $id)
                ->whereNotNull('Coordenada_X')
                ->whereNotNull('Coordenada_Y')
                ->get()
                ->map(fn ($h) => [
                    'coords' => $this->coords($h->Coordenada_X, $h->Coordenada_Y),
                    'fecha' => $h->Fecha_registro,
                    'nota' => $h->Observaciones,
                    'origen_dato' => 'historial_movimientos',
                ]);
        }

        if ($tipo === 'barco' && Schema::hasTable('movimiento_barco')) {
            $puntos = $puntos->concat(
                DB::table('movimiento_barco')->where('ID_barco', $id)->get()->map(fn ($m) => [
                    'coords' => $this->coords($m->Coordenada_X, $m->Coordenada_Y),
                    'fecha' => $m->Fecha_registro,
                    'nota' => null,
                    'origen_dato' => 'movimiento_barco',
                ])
            );
        }

        // Mismo punto y misma fecha en las dos tablas = un solo movimiento.
        return $puntos
            ->unique(fn ($p) => $p['coords'][0] . ',' . $p['coords'][1] . '@' . $p['fecha'])
            ->sortBy(fn ($p) => (string) $p['fecha'])
            ->values();
    }

    private function distanciaTotal(Collection $puntos): float
    {
        $total = 0.0;

        for ($i = 1; $i < $puntos->count(); $i++) {
            [$x1, $y1] = $puntos[$i - 1]['coords'];
            [$x2, $y2] = $puntos[$i]['coords'];
            $total += hypot($x2 - $x1, $y2 - $y1);
        }

        return $total;
    }

    private function coords($x, $y): ?array
    {
        return $x === null || $y === null ? null : [(float) $x, (float) $y];
    }

    private function factores(?string $json): array|string|null
    {
        if ($json === null) {
            return null;
        }

        $decodificado = json_decode($json, true);

        // Las predicciones viejas del .sql guardan texto libre, no JSON.
        return is_array($decodificado) ? $decodificado : $json;
    }
}
