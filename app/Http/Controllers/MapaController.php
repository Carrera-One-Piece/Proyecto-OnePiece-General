<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * Mapa navegable de la Grand Line (RF10) y apoyo al Módulo de Navegación
 * (RF11-RF12).
 *
 *   GET /mapa        -> la página (permiso NAVEGACION.VER)
 *   GET /mapa/datos  -> todo lo que pinta el mapa, en JSON (NAVEGACION.VER)
 *
 * Solo lee tablas que ya existen en marina_system_db (no crea ni cambia
 * nada). Si a alguien le falta una tabla en su base local, esa capa sale
 * vacía en vez de romper la página.
 *
 * Sistema de coordenadas: el mismo de la tabla `isla` y del
 * UbicacionRequest: X de 0 a 1500 (izquierda a derecha) y Y de 0 a 1000
 * (de arriba hacia abajo, como en una imagen).
 */
class MapaController extends Controller
{
    public const ANCHO = 1500;
    public const ALTO = 1000;

    private ?Collection $ubicaciones = null;

    public function index(): View
    {
        return view('mapa', [
            'puedeEditar' => Gate::allows('NAVEGACION.EDITAR'),
            'puedePredecir' => Gate::allows('NAVEGACION.CREAR'),
        ]);
    }

    public function datos(): JsonResponse
    {
        $islas = $this->islas();

        $tripulaciones = $this->tripulaciones($islas);

        return response()->json([
            'limites' => ['ancho' => self::ANCHO, 'alto' => self::ALTO],
            'mares' => $this->mares(),
            'islas' => $islas->values(),
            'conexiones' => $this->conexiones(),
            'tripulaciones' => $tripulaciones->values(),
            'otros' => $this->otrasUbicaciones($islas),
            'rutas' => $this->rutas($tripulaciones, $islas),
            'puedeEditar' => Gate::allows('NAVEGACION.EDITAR'),
            'puedePredecir' => Gate::allows('NAVEGACION.CREAR'),
            'entidades' => $this->entidadesEditables(),
        ]);
    }

    // ------------------------------------------------------------- capas

    private function mares(): Collection
    {
        if (! Schema::hasTable('mares')) {
            return collect();
        }

        return DB::table('mares')
            ->orderBy('ID_mar')
            ->get(['ID_mar', 'Nombre'])
            ->map(fn ($m) => ['id' => (int) $m->ID_mar, 'nombre' => $m->Nombre]);
    }

    /** Islas con coordenadas, su mar y los eventos que han pasado en cada una. */
    private function islas(): Collection
    {
        if (! Schema::hasTable('isla')) {
            return collect();
        }

        $eventos = $this->eventosPorIsla();

        return DB::table('isla')
            ->whereNotNull('Coordenada_X')
            ->whereNotNull('Coordenada_Y')
            ->orderBy('ID_isla')
            ->get()
            ->map(fn ($i) => [
                'id' => (int) $i->ID_isla,
                'nombre' => $i->Nombre,
                'mar' => isset($i->ID_mar) ? (int) $i->ID_mar : null,
                'tipo' => $i->Tipo ?? null,
                'descripcion' => $i->Descripcion ?? null,
                'coords' => [(float) $i->Coordenada_X, (float) $i->Coordenada_Y],
                'eventos' => $eventos->get($i->ID_isla, collect())->values(),
            ])
            ->keyBy('id');
    }

    private function eventosPorIsla(): Collection
    {
        if (! Schema::hasTable('eventos')) {
            return collect();
        }

        $consulta = DB::table('eventos')->orderByDesc('Fecha_evento');

        if (Schema::hasTable('tipos_evento')) {
            $consulta->leftJoin('tipos_evento', 'eventos.ID_tipo_evento', '=', 'tipos_evento.ID_tipo_evento')
                ->select('eventos.*', 'tipos_evento.Nombre as Tipo_nombre');
        }

        return $consulta->get()
            ->map(fn ($e) => [
                'isla' => (int) $e->ID_isla,
                'nombre' => $e->Nombre,
                'tipo' => $e->Tipo_nombre ?? null,
                'nivel' => $e->Nivel_amenaza,
                'fecha' => $e->Fecha_evento,
                'descripcion' => $e->Descripcion,
            ])
            ->groupBy('isla');
    }

    /** Rutas marítimas entre islas (tabla isla_adyacente). */
    private function conexiones(): Collection
    {
        if (! Schema::hasTable('isla_adyacente')) {
            return collect();
        }

        return DB::table('isla_adyacente')
            ->get()
            ->map(fn ($c) => [
                'origen' => (int) $c->ID_isla_origen,
                'destino' => (int) $c->ID_isla_destino,
                'distancia' => $c->Distancia !== null ? (float) $c->Distancia : null,
                'tiempo' => $c->Tiempo_estimado !== null ? (float) $c->Tiempo_estimado : null,
                'observaciones' => $c->Observaciones,
            ]);
    }

    /**
     * Cada tripulación con su posición actual. Se busca en este orden:
     *   1. ubicacion_actual de la tripulación (lo que guarda Navegación),
     *   2. ubicacion_actual de su barco,
     *   3. el último registro de movimiento_barco de su barco.
     * Si no hay nada, la tripulación sale en la lista "sin ubicación".
     */
    private function tripulaciones(Collection $islas): Collection
    {
        if (! Schema::hasTable('tripulaciones')) {
            return collect();
        }

        $actuales = $this->ubicacionesActuales();

        $barcos = Schema::hasTable('barcos')
            ? DB::table('barcos')->whereNotNull('ID_tripulacion')->orderBy('ID_barco')->get()->groupBy('ID_tripulacion')
            : collect();

        $ultimoMovimiento = Schema::hasTable('movimiento_barco')
            ? DB::table('movimiento_barco')
                ->orderBy('Fecha_registro')
                ->orderBy('ID_movimiento_barco')
                ->get()
                ->keyBy('ID_barco') // al ordenar ascendente, queda el último de cada barco
            : collect();

        return DB::table('tripulaciones')
            ->orderBy('Nombre')
            ->get()
            ->map(function ($t) use ($actuales, $barcos, $ultimoMovimiento, $islas) {
                $barco = $barcos->get($t->ID_tripulacion)?->first();
                $posicion = null;

                if ($u = $actuales->get('tripulacion:' . $t->ID_tripulacion)) {
                    $posicion = $this->posicion($u->Coordenada_X, $u->Coordenada_Y, $u->Fecha_actualizacion, 'Ubicación registrada', $u->ID_isla ?? null);
                } elseif ($barco && ($u = $actuales->get('barco:' . $barco->ID_barco))) {
                    $posicion = $this->posicion($u->Coordenada_X, $u->Coordenada_Y, $u->Fecha_actualizacion, 'Ubicación del barco', $u->ID_isla ?? null);
                } elseif ($barco && ($m = $ultimoMovimiento->get($barco->ID_barco))) {
                    $posicion = $this->posicion($m->Coordenada_X, $m->Coordenada_Y, $m->Fecha_registro, 'Último movimiento del barco');
                }

                if ($posicion) {
                    $posicion['isla'] = $this->islaDe($posicion, $islas);
                }

                return [
                    'id' => (int) $t->ID_tripulacion,
                    'nombre' => $t->Nombre,
                    'estado' => $t->Estado,
                    'bounty' => $t->Bounty_general !== null ? (float) $t->Bounty_general : null,
                    'bandera' => $t->URL_bandera ?? null,
                    'barco' => $barco ? ['id' => (int) $barco->ID_barco, 'nombre' => $barco->Nombre] : null,
                    'posicion' => $posicion,
                ];
            });
    }

    /** Personajes y organizaciones (por ejemplo la Marina) que tengan ubicación. */
    private function otrasUbicaciones(Collection $islas): Collection
    {
        $tablas = [
            'personaje' => ['personajes', 'ID_personaje'],
            'organizacion' => ['organizaciones', 'ID_organizacion'],
        ];

        return $this->ubicacionesActuales()
            ->filter(fn ($u) => isset($tablas[strtolower($u->Tipo_entidad)]))
            ->map(function ($u) use ($tablas, $islas) {
                $tipo = strtolower($u->Tipo_entidad);
                [$tabla, $pk] = $tablas[$tipo];

                $nombre = Schema::hasTable($tabla)
                    ? DB::table($tabla)->where($pk, $u->ID_entidad)->value('Nombre')
                    : null;

                $posicion = $this->posicion($u->Coordenada_X, $u->Coordenada_Y, $u->Fecha_actualizacion, 'Ubicación registrada', $u->ID_isla ?? null);
                $posicion['isla'] = $this->islaDe($posicion, $islas);

                return [
                    'tipo' => $tipo,
                    'id' => (int) $u->ID_entidad,
                    'nombre' => $nombre ?? ucfirst($tipo) . ' #' . $u->ID_entidad,
                    'posicion' => $posicion,
                ];
            })
            ->values();
    }

    /**
     * Recorrido de cada tripulación, en orden de fecha. Sale de
     * historial_movimientos (que también llenan los triggers de
     * ubicacion_actual) y, si una tripulación no tiene historial, de
     * movimiento_barco de su barco.
     */
    private function rutas(Collection $tripulaciones, Collection $islas): Collection
    {
        $historial = Schema::hasTable('historial_movimientos')
            ? DB::table('historial_movimientos')
                ->whereNotNull('Coordenada_X')
                ->whereNotNull('Coordenada_Y')
                ->orderBy('Fecha_registro')
                ->orderBy('ID_movimiento')
                ->get()
            : collect();

        $movimientos = Schema::hasTable('movimiento_barco')
            ? DB::table('movimiento_barco')->orderBy('Fecha_registro')->orderBy('ID_movimiento_barco')->get()->groupBy('ID_barco')
            : collect();

        $porTripulacion = $historial
            ->filter(fn ($h) => strtolower($h->Tipo_entidad) === 'tripulacion')
            ->groupBy('ID_entidad');

        $porBarco = $historial
            ->filter(fn ($h) => strtolower($h->Tipo_entidad) === 'barco')
            ->groupBy('ID_entidad');

        return $tripulaciones
            ->map(function ($t) use ($porTripulacion, $porBarco, $movimientos, $islas) {
                $puntos = $porTripulacion->get($t['id']);

                if (! $puntos && $t['barco']) {
                    $puntos = $porBarco->get($t['barco']['id'])
                        ?? $movimientos->get($t['barco']['id'])?->map(fn ($m) => (object) [
                            'Coordenada_X' => $m->Coordenada_X,
                            'Coordenada_Y' => $m->Coordenada_Y,
                            'Fecha_registro' => $m->Fecha_registro,
                            'Observaciones' => null,
                        ]);
                }

                if (! $puntos || $puntos->count() < 2) {
                    return null;
                }

                return [
                    'tripulacion' => $t['id'],
                    'nombre' => $t['nombre'],
                    'puntos' => $puntos->map(function ($p) use ($islas) {
                        $punto = [
                            'coords' => [(float) $p->Coordenada_X, (float) $p->Coordenada_Y],
                            'fecha' => $p->Fecha_registro,
                            'nota' => $p->Observaciones ?? null,
                        ];
                        $punto['isla'] = $this->islaDe($punto, $islas);

                        return $punto;
                    })->values(),
                ];
            })
            ->filter()
            ->values();
    }

    /** Lo que se puede ubicar desde el mapa (solo se manda a quien puede editar). */
    private function entidadesEditables(): array
    {
        if (! Gate::allows('NAVEGACION.EDITAR')) {
            return [];
        }

        $lista = fn (string $tabla, string $pk) => Schema::hasTable($tabla)
            ? DB::table($tabla)->orderBy('Nombre')->get([$pk, 'Nombre'])
                ->map(fn ($f) => ['id' => (int) $f->{$pk}, 'nombre' => $f->Nombre])
            : collect();

        return [
            'tripulacion' => $lista('tripulaciones', 'ID_tripulacion'),
            'barco' => $lista('barcos', 'ID_barco'),
        ];
    }

    // ------------------------------------------------------------ ayudas

    /** ubicacion_actual indexada por "tipo:id" (el tipo en minúscula). */
    private function ubicacionesActuales(): Collection
    {
        if ($this->ubicaciones !== null) {
            return $this->ubicaciones;
        }

        if (! Schema::hasTable('ubicacion_actual')) {
            return $this->ubicaciones = collect();
        }

        return $this->ubicaciones = DB::table('ubicacion_actual')
            ->get()
            ->keyBy(fn ($u) => strtolower($u->Tipo_entidad) . ':' . $u->ID_entidad);
    }

    private function posicion($x, $y, $fecha, string $fuente, $idIsla = null): array
    {
        return [
            'coords' => [(float) $x, (float) $y],
            'fecha' => $fecha,
            'fuente' => $fuente,
            'id_isla' => $idIsla !== null ? (int) $idIsla : null,
        ];
    }

    /**
     * Nombre de la isla donde está un punto: la que diga ID_isla o, si no,
     * la isla más cercana a menos de 25 unidades.
     */
    private function islaDe(array $punto, Collection $islas): ?string
    {
        if (! empty($punto['id_isla']) && $islas->has($punto['id_isla'])) {
            return $islas->get($punto['id_isla'])['nombre'];
        }

        [$x, $y] = $punto['coords'];
        $cercana = null;
        $mejor = 25.0;

        foreach ($islas as $isla) {
            $d = hypot($isla['coords'][0] - $x, $isla['coords'][1] - $y);
            if ($d <= $mejor) {
                $mejor = $d;
                $cercana = $isla['nombre'];
            }
        }

        return $cercana;
    }
}
