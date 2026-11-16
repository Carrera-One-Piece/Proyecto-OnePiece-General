<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Motor de reglas que predice a qué isla se moverá una tripulación.
 *
 * Sigue el documento "Tablas_Motor_Reglas" del equipo:
 *   1. Se toma la isla donde está la tripulación (isla de origen).
 *   2. Las islas candidatas son las que se pueden alcanzar desde ahí
 *      según isla_adyacente (no se permiten saltos arbitrarios).
 *   3. A cada candidata se le aplican las reglas activas de
 *      reglas_ponderacion; cada regla suma o resta su Peso.
 *   4. Gana la candidata con más puntos y se guarda en
 *      movimientos_predichos, con el detalle de los factores aplicados,
 *      para poder compararla después con el movimiento real.
 *
 * Los pesos se calibran cambiando reglas_ponderacion, sin tocar código.
 * Una regla con Activo = 0 no se aplica.
 */
class MotorPrediccion
{
    /** Eventos que espantan a una tripulación (fuerte presencia de la Marina). */
    private const EVENTOS_NEGATIVOS = ['guerra', 'buster call', 'ejecución', 'ejecucion', 'invasión', 'invasion'];

    /** Un evento cuenta como "reciente" si pasó en los últimos N días. */
    private const DIAS_EVENTO_RECIENTE = 365;

    /** Recompensa mínima para considerarla "alta" (en berries). */
    private const RECOMPENSA_ALTA = 100_000_000;

    /** Distancia máxima (en unidades del mapa) para decir que algo está "en" una isla. */
    private const RADIO_ISLA = 25.0;

    private Collection $islas;

    private Collection $reglas;

    public function __construct()
    {
        $this->islas = Schema::hasTable('isla')
            ? DB::table('isla')->whereNotNull('Coordenada_X')->whereNotNull('Coordenada_Y')->get()->keyBy('ID_isla')
            : collect();

        $this->reglas = Schema::hasTable('reglas_ponderacion')
            ? DB::table('reglas_ponderacion')->where('Activo', 1)->get()->keyBy('Nombre_regla')
            : collect();
    }

    /**
     * Evalúa todas las islas candidatas para una tripulación, sin guardar nada.
     *
     * @return array{origen: array, candidatas: array}
     *
     * @throws RuntimeException si la tripulación no tiene ubicación o no hay islas a dónde ir.
     */
    public function evaluar(int $idTripulacion): array
    {
        $origen = $this->islaActual($idTripulacion);

        if (! $origen) {
            throw new RuntimeException('La tripulación no tiene una ubicación registrada cerca de ninguna isla.');
        }

        $candidatas = $this->islasAlcanzables($origen->ID_isla);

        if ($candidatas->isEmpty()) {
            throw new RuntimeException("No hay rutas registradas desde {$origen->Nombre} (tabla isla_adyacente).");
        }

        $contexto = $this->contexto($idTripulacion);

        $evaluadas = $candidatas
            ->map(fn ($ruta) => $this->puntuar($ruta, $origen, $contexto))
            ->sortByDesc('puntaje')
            ->values();

        return [
            'origen' => ['id' => (int) $origen->ID_isla, 'nombre' => $origen->Nombre],
            'candidatas' => $evaluadas->all(),
        ];
    }

    /**
     * Evalúa y guarda la predicción ganadora en movimientos_predichos.
     *
     * @return array{id: int|null, tripulacion: array, origen: array, destino: array, puntaje: float, candidatas: array}
     */
    public function predecir(int $idTripulacion): array
    {
        $resultado = $this->evaluar($idTripulacion);
        $ganadora = $resultado['candidatas'][0];

        $id = null;

        if (Schema::hasTable('movimientos_predichos')) {
            $id = DB::table('movimientos_predichos')->insertGetId([
                'ID_tripulacion' => $idTripulacion,
                'ID_isla_origen' => $resultado['origen']['id'],
                'ID_isla_destino_predicha' => $ganadora['isla']['id'],
                'Puntaje_total' => $ganadora['puntaje'],
                'Factores_aplicados' => json_encode($ganadora['factores'], JSON_UNESCAPED_UNICODE),
                'Fecha_prediccion' => now(),
            ], 'ID_prediccion');
        }

        return [
            'id' => $id,
            'tripulacion' => [
                'id' => $idTripulacion,
                'nombre' => DB::table('tripulaciones')->where('ID_tripulacion', $idTripulacion)->value('Nombre'),
            ],
            'origen' => $resultado['origen'],
            'destino' => $ganadora['isla'],
            'puntaje' => $ganadora['puntaje'],
            'candidatas' => $resultado['candidatas'],
        ];
    }

    // ------------------------------------------------------- ubicación

    /**
     * Isla donde está la tripulación ahora. Se busca en el mismo orden que
     * el mapa: ubicacion_actual de la tripulación, la de su barco, el último
     * movimiento_barco y, por último, su historial_movimientos.
     */
    public function islaActual(int $idTripulacion): ?object
    {
        foreach ($this->posicionesCandidatas($idTripulacion) as $p) {
            if (! empty($p['isla']) && $this->islas->has($p['isla'])) {
                return $this->islas->get($p['isla']);
            }

            if ($isla = $this->islaCercana($p['x'], $p['y'])) {
                return $isla;
            }
        }

        return null;
    }

    private function posicionesCandidatas(int $idTripulacion): \Generator
    {
        $idBarco = Schema::hasTable('barcos')
            ? DB::table('barcos')->where('ID_tripulacion', $idTripulacion)->orderBy('ID_barco')->value('ID_barco')
            : null;

        if (Schema::hasTable('ubicacion_actual')) {
            $actual = fn (string $tipo, $id) => DB::table('ubicacion_actual')
                ->whereRaw('LOWER(Tipo_entidad) = ?', [$tipo])
                ->where('ID_entidad', $id)
                ->first();

            if ($u = $actual('tripulacion', $idTripulacion)) {
                yield ['x' => (float) $u->Coordenada_X, 'y' => (float) $u->Coordenada_Y, 'isla' => $u->ID_isla ?? null];
            }

            if ($idBarco && ($u = $actual('barco', $idBarco))) {
                yield ['x' => (float) $u->Coordenada_X, 'y' => (float) $u->Coordenada_Y, 'isla' => $u->ID_isla ?? null];
            }
        }

        if ($idBarco && Schema::hasTable('movimiento_barco')) {
            $m = DB::table('movimiento_barco')->where('ID_barco', $idBarco)
                ->orderByDesc('Fecha_registro')->orderByDesc('ID_movimiento_barco')->first();

            if ($m) {
                yield ['x' => (float) $m->Coordenada_X, 'y' => (float) $m->Coordenada_Y, 'isla' => null];
            }
        }

        if (Schema::hasTable('historial_movimientos')) {
            $h = DB::table('historial_movimientos')
                ->whereRaw('LOWER(Tipo_entidad) = ?', ['tripulacion'])
                ->where('ID_entidad', $idTripulacion)
                ->whereNotNull('Coordenada_X')
                ->orderByDesc('Fecha_registro')->orderByDesc('ID_movimiento')->first();

            if ($h) {
                yield ['x' => (float) $h->Coordenada_X, 'y' => (float) $h->Coordenada_Y, 'isla' => null];
            }
        }
    }

    public function islaCercana(float $x, float $y): ?object
    {
        $cercana = null;
        $mejor = self::RADIO_ISLA;

        foreach ($this->islas as $isla) {
            $d = hypot((float) $isla->Coordenada_X - $x, (float) $isla->Coordenada_Y - $y);
            if ($d <= $mejor) {
                $mejor = $d;
                $cercana = $isla;
            }
        }

        return $cercana;
    }

    /**
     * Islas a las que se puede navegar desde la de origen. Se usan las rutas
     * que salen de ella; si no sale ninguna (por ejemplo, la última isla de
     * la cadena), se permite volver por las rutas que llegan.
     */
    private function islasAlcanzables(int $idOrigen): Collection
    {
        if (! Schema::hasTable('isla_adyacente')) {
            return collect();
        }

        $salientes = DB::table('isla_adyacente')->where('ID_isla_origen', $idOrigen)->get()
            ->map(fn ($r) => (object) ['isla' => $r->ID_isla_destino, 'tiempo' => $r->Tiempo_estimado, 'distancia' => $r->Distancia ?? null]);

        if ($salientes->isEmpty()) {
            $salientes = DB::table('isla_adyacente')->where('ID_isla_destino', $idOrigen)->get()
                ->map(fn ($r) => (object) ['isla' => $r->ID_isla_origen, 'tiempo' => $r->Tiempo_estimado, 'distancia' => $r->Distancia ?? null]);
        }

        return $salientes
            ->filter(fn ($r) => $this->islas->has($r->isla))
            ->unique('isla')
            ->values();
    }

    // ---------------------------------------------------------- reglas

    /** Datos que se consultan una sola vez y sirven para todas las candidatas. */
    private function contexto(int $idTripulacion): array
    {
        // Relaciones de esta tripulación con las demás.
        $aliadas = collect();
        $enemigas = collect();

        if (Schema::hasTable('relacion_tripulaciones')) {
            DB::table('relacion_tripulaciones')
                ->where(fn ($q) => $q->where('ID_tripulacion_A', $idTripulacion)->orWhere('ID_tripulacion_B', $idTripulacion))
                ->get()
                ->each(function ($r) use ($idTripulacion, $aliadas, $enemigas) {
                    $otra = (int) ($r->ID_tripulacion_A == $idTripulacion ? $r->ID_tripulacion_B : $r->ID_tripulacion_A);
                    $tipo = mb_strtolower((string) $r->Tipo_relacion);

                    if (preg_match('/rival|enem|traici|ruptura/u', $tipo)) {
                        $enemigas->put($otra, $r->Tipo_relacion);
                    } elseif (preg_match('/alian|aliad|mentor/u', $tipo)) {
                        $aliadas->put($otra, $r->Tipo_relacion);
                    }
                });
        }

        // En qué isla está cada una de las otras tripulaciones.
        $islaDeTripulacion = collect();
        if (Schema::hasTable('tripulaciones')) {
            foreach (DB::table('tripulaciones')->where('ID_tripulacion', '!=', $idTripulacion)->get() as $t) {
                if ($isla = $this->islaActual((int) $t->ID_tripulacion)) {
                    $islaDeTripulacion->put((int) $t->ID_tripulacion, ['isla' => (int) $isla->ID_isla, 'nombre' => $t->Nombre]);
                }
            }
        }

        return [
            'aliadas' => $aliadas,
            'enemigas' => $enemigas,
            'islaDeTripulacion' => $islaDeTripulacion,
            'territorios' => $this->territorios(),
            'recompensas' => $this->recompensasPorIsla(),
            'eventos' => $this->eventosRecientesPorIsla(),
            'salidas' => Schema::hasTable('isla_adyacente')
                ? DB::table('isla_adyacente')->selectRaw('ID_isla_origen, COUNT(*) as total')
                    ->groupBy('ID_isla_origen')->pluck('total', 'ID_isla_origen')->all()
                : [],
            'peligrosidad' => Schema::hasTable('mares')
                ? DB::table('mares')->pluck('Peligrosidad', 'ID_mar')
                : collect(),
        ];
    }

    private function puntuar(object $ruta, object $origen, array $c): array
    {
        $isla = $this->islas->get($ruta->isla);
        $idIsla = (int) $isla->ID_isla;
        $factores = [];

        $aplicar = function (string $regla, float $multiplicador, string $detalle) use (&$factores) {
            if (! $this->reglas->has($regla) || $multiplicador == 0.0) {
                return;
            }

            $peso = (float) $this->reglas->get($regla)->Peso;
            $factores[] = [
                'regla' => $regla,
                'peso' => $peso,
                'multiplicador' => round($multiplicador, 2),
                'puntos' => round($peso * $multiplicador, 2),
                'detalle' => $detalle,
            ];
        };

        // Tripulaciones que están en la isla candidata.
        $presentes = $c['islaDeTripulacion']->filter(fn ($t) => $t['isla'] === $idIsla);

        // 1. Presencia_Enemiga: tripulación rival/enemiga o territorio del gobierno.
        $enemigos = $presentes->filter(fn ($t, $id) => $c['enemigas']->has($id))->pluck('nombre');
        $territorio = $c['territorios']->get($idIsla);
        $gobierno = $territorio && $territorio['gubernamental'];

        if ($enemigos->isNotEmpty() || $gobierno) {
            $motivos = [];
            if ($enemigos->isNotEmpty()) {
                $motivos[] = 'tripulación enemiga o rival: ' . $enemigos->implode(', ');
            }
            if ($gobierno) {
                $motivos[] = "territorio de {$territorio['organizacion']}";
            }
            $aplicar('Presencia_Enemiga', 1, ucfirst(implode('; ', $motivos)) . '.');
        }

        // 2. Objetivo_Estrategico: territorio estratégico, entrada a un mar nuevo
        //    (ruta hacia la Grand Line / el New World) o isla de paso con varias rutas.
        $motivos = [];
        if ($territorio) {
            $motivos[] = "territorio estratégico (nivel {$territorio['nivel']})";
        }
        if ((int) $isla->ID_mar !== (int) $origen->ID_mar) {
            $motivos[] = 'abre la entrada a otro mar';
        }
        if (($c['salidas'][$idIsla] ?? 0) >= 2) {
            $motivos[] = 'es un cruce con varias rutas';
        }
        if ($motivos) {
            $aplicar('Objetivo_Estrategico', 1, ucfirst(implode(', ', $motivos)) . '.');
        }

        // 3. Recompensa_Disponible: piratas con recompensa alta activa en la isla.
        if ($r = $c['recompensas']->get($idIsla)) {
            $aplicar('Recompensa_Disponible', 1, 'Recompensas altas activas en la isla: ' . implode(', ', $r) . '.');
        }

        // 4. Alianza_Presente.
        $aliados = $presentes->filter(fn ($t, $id) => $c['aliadas']->has($id))->pluck('nombre');
        if ($aliados->isNotEmpty()) {
            $aplicar('Alianza_Presente', 1, 'Tripulación aliada en la isla: ' . $aliados->implode(', ') . '.');
        }

        // 5. Evento_Reciente: suma si el evento atrae (alianza, batalla...), resta si es de la Marina (guerra, Buster Call...).
        foreach ($c['eventos']->get($idIsla, []) as $evento) {
            $negativo = in_array(mb_strtolower((string) $evento['tipo']), self::EVENTOS_NEGATIVOS, true);
            $aplicar(
                'Evento_Reciente',
                $negativo ? -1 : 1,
                "Evento reciente: {$evento['nombre']}" . ($evento['tipo'] ? " ({$evento['tipo']})" : '') . '.'
            );
        }

        // 6. Distancia_Navegacion: proporcional a los días de viaje.
        if ($ruta->tiempo !== null && (float) $ruta->tiempo > 0) {
            $dias = (float) $ruta->tiempo;
            $aplicar('Distancia_Navegacion', $dias, "{$dias} día(s) estimados de navegación desde {$origen->Nombre}.");
        }

        // 7. Peligrosidad_Mar: proporcional a la peligrosidad del mar de la isla.
        $peligro = (float) ($c['peligrosidad']->get($isla->ID_mar) ?? 0);
        if ($peligro > 0) {
            $aplicar('Peligrosidad_Mar', $peligro, "Peligrosidad del mar: {$peligro}.");
        }

        return [
            'isla' => [
                'id' => $idIsla,
                'nombre' => $isla->Nombre,
                'coords' => [(float) $isla->Coordenada_X, (float) $isla->Coordenada_Y],
            ],
            'puntaje' => round(array_sum(array_column($factores, 'puntos')), 2),
            'dias' => $ruta->tiempo !== null ? (float) $ruta->tiempo : null,
            'factores' => $factores,
        ];
    }

    /** Islas que alguna organización controla (tabla territorios). */
    private function territorios(): Collection
    {
        if (! Schema::hasTable('territorios')) {
            return collect();
        }

        $consulta = DB::table('territorios');

        if (Schema::hasTable('organizaciones')) {
            $consulta->leftJoin('organizaciones', 'territorios.ID_organizacion', '=', 'organizaciones.ID_organizacion')
                ->select('territorios.*', 'organizaciones.Nombre as Org_nombre', 'organizaciones.Tipo as Org_tipo');
        }

        return $consulta->get()->mapWithKeys(fn ($t) => [
            (int) $t->ID_isla => [
                'nivel' => $t->Nivel_estrategico,
                'organizacion' => $t->Org_nombre ?? 'una organización',
                'gubernamental' => (bool) preg_match('/gubern|gobierno|marina/i', ($t->Org_tipo ?? '') . ' ' . ($t->Org_nombre ?? '')),
            ],
        ]);
    }

    /** Por isla, los piratas que están ahí con una recompensa alta activa. */
    private function recompensasPorIsla(): Collection
    {
        if (! Schema::hasTable('recompensas') || ! Schema::hasTable('personajes')) {
            return collect();
        }

        $altas = DB::table('recompensas')
            ->join('personajes', 'recompensas.ID_personaje', '=', 'personajes.ID_personaje')
            ->where(fn ($q) => $q->whereNull('recompensas.Estado')->orWhere('recompensas.Estado', 'Activa'))
            ->where('recompensas.Valor', '>=', self::RECOMPENSA_ALTA)
            ->select('personajes.ID_personaje', 'personajes.Nombre', 'personajes.Coordenada_X', 'personajes.Coordenada_Y')
            ->distinct()
            ->get();

        $ubicaciones = Schema::hasTable('ubicacion_actual')
            ? DB::table('ubicacion_actual')->whereRaw('LOWER(Tipo_entidad) = ?', ['personaje'])->get()->keyBy('ID_entidad')
            : collect();

        $porIsla = collect();

        foreach ($altas as $p) {
            $u = $ubicaciones->get($p->ID_personaje);
            $x = $u->Coordenada_X ?? $p->Coordenada_X;
            $y = $u->Coordenada_Y ?? $p->Coordenada_Y;

            if ($x === null || $y === null) {
                continue;
            }

            $idIsla = $u->ID_isla ?? null;
            $idIsla = $idIsla ?: $this->islaCercana((float) $x, (float) $y)?->ID_isla;

            if ($idIsla) {
                $porIsla->put((int) $idIsla, [...$porIsla->get((int) $idIsla, []), $p->Nombre]);
            }
        }

        return $porIsla;
    }

    private function eventosRecientesPorIsla(): Collection
    {
        if (! Schema::hasTable('eventos')) {
            return collect();
        }

        $consulta = DB::table('eventos')
            ->where(fn ($q) => $q->whereNull('Fecha_evento')
                ->orWhere('Fecha_evento', '>=', now()->subDays(self::DIAS_EVENTO_RECIENTE)));

        if (Schema::hasTable('tipos_evento')) {
            $consulta->leftJoin('tipos_evento', 'eventos.ID_tipo_evento', '=', 'tipos_evento.ID_tipo_evento')
                ->select('eventos.*', 'tipos_evento.Nombre as Tipo_nombre');
        }

        return $consulta->get()
            ->groupBy('ID_isla')
            ->map(fn ($eventos) => $eventos->map(fn ($e) => [
                'nombre' => $e->Nombre,
                'tipo' => $e->Tipo_nombre ?? null,
            ])->all());
    }
}
