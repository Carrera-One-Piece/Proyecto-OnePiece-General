<?php

namespace App\Http\Controllers;

use App\Models\AuditoriaRecompensa;
use App\Models\Evento;
use App\Models\Personaje;
use App\Models\Recompensa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Rebanada B del módulo de Recompensas: crear, aumentar, cancelar, editar y borrar.
 *
 * Cada ruta pide su permiso (RECOMPENSAS.CREAR / EDITAR / ELIMINAR) en
 * routes/web.php. El historial lo escribe la base con triggers; aquí solo se
 * anota en la bitácora quién hizo cada cambio (rebanada C).
 */
class RecompensaController extends Controller
{
    // El proyecto no trae traducciones de Laravel: sin esto los errores salen
    // en inglés ("The i d personaje field is required.").
    private const MENSAJES = [
        'required' => 'Falta :attribute.',
        'numeric' => ':Attribute tiene que ser un número (sin comas).',
        'integer' => ':Attribute no es válido.',
        'min' => ':Attribute tiene que ser de al menos :min.',
        'max' => ':Attribute es demasiado grande.',
        'string' => ':Attribute no es válido.',
        'exists' => ':Attribute no está registrado.',
        'in' => ':Attribute no es válido.',
        'Motivo.max' => 'El motivo es demasiado largo (máximo :max letras).',
    ];

    private const CAMPOS = [
        'ID_personaje' => 'el pirata',
        'Valor' => 'el valor',
        'ID_evento' => 'el evento',
        'Motivo' => 'el motivo',
        'Estado' => 'el motivo de la cancelación',
    ];

    // GET /recompensas/crear
    public function crear(Request $request)
    {
        return view('recompensas.crear', [
            'personajes' => Personaje::orderBy('Nombre')->get(['ID_personaje', 'Nombre', 'Alias']),
            'eventos' => Evento::orderBy('Nombre')->get(['ID_evento', 'Nombre']),
            'elegido' => $request->integer('personaje') ?: null,
            // Los que ya tienen una Activa salen marcados y bloqueados en la lista.
            'conActiva' => Recompensa::where('Estado', Recompensa::ESTADO_ACTIVA)->pluck('ID_personaje')->all(),
        ]);
    }

    // POST /recompensas
    public function guardar(Request $request)
    {
        $this->sinPuntos($request);

        $datos = $request->validate([
            'ID_personaje' => ['required', 'integer', 'exists:personajes,ID_personaje'],
            'Valor' => ['required', 'numeric', 'min:1', 'max:9999999999999'],
            'ID_evento' => ['nullable', 'integer', 'exists:eventos,ID_evento'],
            'Motivo' => ['required', 'string', 'max:1000'],
        ], self::MENSAJES + [
            'ID_personaje.exists' => 'Ese pirata no está registrado.',
        ], self::CAMPOS);

        // Regla del módulo: una sola recompensa Activa por pirata.
        $yaTiene = Recompensa::where('ID_personaje', $datos['ID_personaje'])
            ->where('Estado', Recompensa::ESTADO_ACTIVA)
            ->exists();

        if ($yaTiene) {
            return back()->withInput()->withErrors([
                'ID_personaje' => 'Este pirata ya tiene una recompensa activa. Auméntela en su ficha.',
            ]);
        }

        $recompensa = Recompensa::create($datos + [
            'Fecha' => now()->toDateString(),
            'Estado' => Recompensa::ESTADO_ACTIVA,
        ]);

        AuditoriaRecompensa::anotarEnBitacora(AuditoriaRecompensa::BITACORA_CREADA, $recompensa, $datos['Motivo']);

        return redirect()->route('recompensas.pirata', $recompensa->ID_personaje)
            ->with('aviso', 'Recompensa emitida.');
    }

    // GET /recompensas/{id}/editar — solo para corregir el motivo o el evento.
    public function editar(int $id)
    {
        return view('recompensas.editar', [
            'recompensa' => Recompensa::with('personaje')->findOrFail($id),
            'eventos' => Evento::orderBy('Nombre')->get(['ID_evento', 'Nombre']),
        ]);
    }

    // PUT /recompensas/{id} — el Valor no se cambia aquí (eso es "aumentar").
    public function actualizar(Request $request, int $id)
    {
        $recompensa = Recompensa::findOrFail($id);

        $datos = $request->validate([
            'ID_evento' => ['nullable', 'integer', 'exists:eventos,ID_evento'],
            'Motivo' => ['required', 'string', 'max:1000'],
        ], self::MENSAJES, self::CAMPOS);

        $recompensa->update($datos);

        AuditoriaRecompensa::anotarEnBitacora(AuditoriaRecompensa::BITACORA_EDITADA, $recompensa, 'Motivo/evento corregidos');

        return redirect()->route('recompensas.pirata', $recompensa->ID_personaje)
            ->with('aviso', 'Recompensa corregida.');
    }

    // POST /recompensas/{id}/aumentar — pide el valor NUEVO, que tiene que ser mayor.
    public function aumentar(Request $request, int $id)
    {
        $recompensa = Recompensa::findOrFail($id);

        if (! $recompensa->estaActiva()) {
            return back()->withErrors(['Valor' => 'Solo se puede aumentar una recompensa activa.']);
        }

        $this->sinPuntos($request);

        $datos = $request->validate([
            'Valor' => [
                'required', 'numeric', 'max:9999999999999',
                function ($campo, $valor, $fallar) use ($recompensa) {
                    if ((float) $valor <= (float) $recompensa->Valor) {
                        $fallar('El valor nuevo tiene que ser mayor que el actual (฿ '
                            .number_format((float) $recompensa->Valor, 0, ',', '.').').');
                    }
                },
            ],
            'Motivo' => ['required', 'string', 'max:255'],
        ], self::MENSAJES, self::CAMPOS);

        // El trigger de la base guarda el valor anterior y el nuevo en el historial.
        $recompensa->update(['Valor' => $datos['Valor']]);

        AuditoriaRecompensa::anotarEnBitacora(AuditoriaRecompensa::BITACORA_AUMENTADA, $recompensa, $datos['Motivo']);

        return redirect()->route('recompensas.pirata', $recompensa->ID_personaje)
            ->with('aviso', 'Recompensa aumentada.');
    }

    // POST /recompensas/{id}/cancelar — capturado o muerto. Nunca borra.
    public function cancelar(Request $request, int $id)
    {
        $recompensa = Recompensa::findOrFail($id);

        if (! $recompensa->estaActiva()) {
            return back()->withErrors(['Estado' => 'Esta recompensa ya estaba cancelada.']);
        }

        $datos = $request->validate([
            'Estado' => ['required', Rule::in(array_keys(Recompensa::ESTADOS_CANCELADA))],
        ], self::MENSAJES, self::CAMPOS);

        // registrarCancelacion() escribe la fila CANCELAR y la anota en la bitácora.
        DB::transaction(function () use ($recompensa, $datos) {
            $recompensa->update(['Estado' => $datos['Estado']]);
            AuditoriaRecompensa::registrarCancelacion($recompensa);
        });

        return redirect()->route('recompensas.pirata', $recompensa->ID_personaje)
            ->with('aviso', 'Recompensa cancelada ('.Recompensa::ESTADOS_CANCELADA[$datos['Estado']].').');
    }

    // DELETE /recompensas/{id} — solo para recompensas creadas por error.
    public function eliminar(int $id)
    {
        $recompensa = Recompensa::findOrFail($id);

        AuditoriaRecompensa::anotarEnBitacora(AuditoriaRecompensa::BITACORA_ELIMINADA, $recompensa);

        $recompensa->delete();

        return redirect()->route('recompensas.pirata', $recompensa->ID_personaje)
            ->with('aviso', 'Recompensa eliminada.');
    }

    // El formulario muestra "10.000" (public/js/berries.js); para guardar hace falta "10000".
    private function sinPuntos(Request $request): void
    {
        if (is_string($request->input('Valor'))) {
            $request->merge(['Valor' => str_replace(['.', ' '], '', $request->input('Valor'))]);
        }
    }
}
