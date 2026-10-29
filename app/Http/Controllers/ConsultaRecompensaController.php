<?php

namespace App\Http\Controllers;

use App\Models\Personaje;
use App\Models\Recompensa;
use App\Models\Tripulacion;
use Illuminate\Http\Request;

/**
 * Rebanada A del módulo de Recompensas: buscar y consultar (solo lectura).
 *
 * Todo lo de aquí pide RECOMPENSAS.VER, así que también lo usa Cipher Pol.
 * Crear, aumentar, cancelar, editar y borrar están en RecompensaController
 * (rebanada B), aparte para que A y B no editen el mismo archivo.
 */
class ConsultaRecompensaController extends Controller
{
    // Filtro "Estado" del buscador. 'Sin recompensa' = no tiene ninguna Activa.
    public const FILTRO_SIN_RECOMPENSA = 'Sin recompensa';

    // GET /recompensas — lista de piratas, de la recompensa más alta a la más baja.
    public function listar(Request $request)
    {
        $filtros = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'tripulacion' => ['nullable', 'integer'],
            'estado' => ['nullable', 'string', 'max:50'],
        ]);

        $piratas = Personaje::query()
            ->with(['recompensas', 'tripulaciones', 'organizaciones'])
            ->when($filtros['q'] ?? null, function ($query, $texto) {
                $query->where(function ($query) use ($texto) {
                    $query->where('Nombre', 'like', "%{$texto}%")
                        ->orWhere('Alias', 'like', "%{$texto}%");
                });
            })
            ->when($filtros['tripulacion'] ?? null, function ($query, $idTripulacion) {
                $query->whereHas('tripulaciones', fn ($q) => $q->where('tripulaciones.ID_tripulacion', $idTripulacion));
            })
            ->when($filtros['estado'] ?? null, function ($query, $estado) {
                if ($estado === self::FILTRO_SIN_RECOMPENSA) {
                    $query->whereDoesntHave('recompensaActual');
                } else {
                    $query->whereHas('recompensas', fn ($q) => $q->where('Estado', $estado));
                }
            })
            // Ordena por el valor de la recompensa Activa (los que no tienen quedan al final).
            ->orderByDesc(
                Recompensa::select('Valor')
                    ->whereColumn('recompensas.ID_personaje', 'personajes.ID_personaje')
                    ->where('Estado', Recompensa::ESTADO_ACTIVA)
                    ->limit(1)
            )
            ->orderBy('Nombre')
            ->paginate(20)
            ->withQueryString();

        return view('recompensas.listar', [
            'piratas' => $piratas,
            'filtros' => $filtros,
            'tripulaciones' => Tripulacion::orderBy('Nombre')->get(['ID_tripulacion', 'Nombre']),
            'estados' => array_merge(
                [Recompensa::ESTADO_ACTIVA],
                array_keys(Recompensa::ESTADOS_CANCELADA),
                [self::FILTRO_SIN_RECOMPENSA]
            ),
        ]);
    }

    // GET /recompensas/pirata/{id} — la ficha "SE BUSCA" del pirata.
    public function pirata(int $id)
    {
        $personaje = Personaje::with([
            'recompensas' => fn ($q) => $q->orderByDesc('ID_recompensa'),
            'recompensas.evento',
            'tripulaciones',
            'organizaciones',
        ])->findOrFail($id);

        return view('recompensas.pirata', [
            'personaje' => $personaje,
            'recompensa' => $personaje->recompensas->firstWhere('Estado', Recompensa::ESTADO_ACTIVA),
        ]);
    }

    // GET /recompensas/tripulacion/{id} — la tripulación, sus miembros y el total.
    public function tripulacion(int $id)
    {
        $tripulacion = Tripulacion::findOrFail($id);

        // El capitán primero; después de la recompensa más alta a la más baja.
        $miembros = Personaje::with(['recompensaActual', 'tripulaciones'])
            ->whereHas('tripulaciones', fn ($q) => $q->where('tripulaciones.ID_tripulacion', $id))
            ->get()
            ->each(function (Personaje $miembro) use ($id) {
                $miembro->rol_en_tripulacion = $miembro->tripulaciones
                    ->firstWhere('ID_tripulacion', $id)?->pivot->Rol;
            })
            ->sortBy([
                fn ($a, $b) => self::esCapitan($b) <=> self::esCapitan($a),
                fn ($a, $b) => ($b->recompensaActual?->Valor ?? 0) <=> ($a->recompensaActual?->Valor ?? 0),
            ])
            ->values();

        // Se suma al mostrar (no se usa Bounty_general): así nunca queda desactualizado.
        $total = $miembros->sum(fn ($miembro) => (float) ($miembro->recompensaActual?->Valor ?? 0));

        return view('recompensas.tripulacion', compact('tripulacion', 'miembros', 'total'));
    }

    private static function esCapitan(Personaje $miembro): bool
    {
        return in_array($miembro->rol_en_tripulacion, ['Capitán', 'Capitana'], true);
    }
}
