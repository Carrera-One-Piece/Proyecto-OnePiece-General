<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Historial de cambios de una recompensa (tabla auditoria_recompensas).
 *
 * Las filas INSERT, UPDATE y DELETE las escriben los triggers de la base.
 * Laravel NO las escribe (si lo hiciera, cada cambio saldría dos veces).
 *
 * La única fila que escribe Laravel es CANCELAR, porque el trigger de
 * UPDATE solo mira si cambió el Valor, no el Estado. Cuando el CTO arregle
 * el trigger, se borra registrarCancelacion() (ver más abajo).
 */
class AuditoriaRecompensa extends Model
{
    protected $table = 'auditoria_recompensas';

    protected $primaryKey = 'ID_audit';

    // La fecha la pone la base sola (Fecha_Modificacion).
    public $timestamps = false;

    protected $fillable = [
        'ID_recompensa',
        'Operacion',
        'Valor_Anterior',
        'Valor_Nuevo',
        'Fecha_Modificacion',
    ];

    protected $casts = [
        'Fecha_Modificacion' => 'datetime',
    ];

    // Las operaciones vienen en inglés desde la base: así se muestran.
    public const ETIQUETAS = [
        'INSERT'   => 'Recompensa creada',
        'UPDATE'   => 'Aumento',
        'DELETE'   => 'Eliminada',
        'CANCELAR' => 'Cancelada',
    ];

    public function recompensa(): BelongsTo
    {
        return $this->belongsTo(
            Recompensa::class,
            'ID_recompensa',
            'ID_recompensa'
        );
    }

    // En la vista: {{ $fila->operacion_es }}
    public function getOperacionEsAttribute(): string
    {
        return self::ETIQUETAS[$this->Operacion] ?? $this->Operacion;
    }

    /**
     * Todo el historial de un pirata (de todas sus recompensas, también
     * las canceladas), de la más vieja a la más nueva.
     *
     * Uso: AuditoriaRecompensa::delPersonaje($id)->get()
     */
    public function scopeDelPersonaje(Builder $query, int $idPersonaje): Builder
    {
        return $query
            ->whereIn(
                'ID_recompensa',
                Recompensa::where('ID_personaje', $idPersonaje)
                    ->select('ID_recompensa')
            )
            ->with('recompensa')
            ->orderBy('Fecha_Modificacion')
            ->orderBy('ID_audit');
    }

    /**
     * TEMPORAL — hasta que el CTO haga que el trigger anote los cambios de
     * Estado. Cuando el trigger lo haga solo, se borra esta función y la
     * línea que la llama en RecompensaController@cancelar.
     *
     * La llama la rebanada B justo después de cambiar el Estado:
     *
     *     DB::transaction(function () use ($recompensa, $estado) {
     *         $recompensa->update(['Estado' => $estado]);
     *         AuditoriaRecompensa::registrarCancelacion($recompensa);
     *     });
     */
    public static function registrarCancelacion(Recompensa $recompensa): self
    {
        $fila = self::create([
            'ID_recompensa'      => $recompensa->ID_recompensa,
            'Operacion'          => 'CANCELAR',
            'Valor_Anterior'     => $recompensa->Valor,
            'Valor_Nuevo'        => null,
            'Fecha_Modificacion' => now(),
        ]);

        self::anotarEnBitacora(
            self::BITACORA_CANCELADA,
            $recompensa,
            'Estado: ' . $recompensa->Estado
        );

        return $fila;
    }

    /**
     * TEMPORAL — hasta que el CTO agregue ID_usuario y Motivo a
     * auditoria_recompensas. Mientras tanto, quién hizo cada cambio queda
     * en la bitácora del login, que sí sabe quién fue (RNF08).
     *
     * La rebanada B la llama una vez después de crear, aumentar, editar y
     * borrar. Ejemplo:
     *
     *     AuditoriaRecompensa::anotarEnBitacora(
     *         AuditoriaRecompensa::BITACORA_AUMENTADA, $recompensa, $request->Motivo
     *     );
     *
     * Acciones sugeridas (constantes de abajo): RECOMPENSA_CREADA,
     * RECOMPENSA_AUMENTADA, RECOMPENSA_EDITADA, RECOMPENSA_CANCELADA,
     * RECOMPENSA_ELIMINADA.
     */
    public static function anotarEnBitacora(
        string $accion,
        Recompensa $recompensa,
        ?string $detalle = null
    ): void {
        $usuario = auth()->user();

        // El nombre del pirata (si ya no existe, queda el número para no perder el dato).
        $pirata = $recompensa->personaje?->Nombre ?? 'Pirata #' . $recompensa->ID_personaje;

        $texto = $pirata
            . ' — Recompensa #' . $recompensa->ID_recompensa
            . ' — Valor: ' . number_format((float) $recompensa->Valor, 0, ',', '.');

        if ($detalle) {
            $texto .= ' — ' . $detalle;
        }

        // Bitacora::registrar($accion, $idUsuario, $correo, $detalle) del login.
        Bitacora::registrar($accion, $usuario?->ID_usuario, $usuario?->Correo, $texto);
    }

    public const BITACORA_CREADA    = 'RECOMPENSA_CREADA';
    public const BITACORA_AUMENTADA = 'RECOMPENSA_AUMENTADA';
    public const BITACORA_EDITADA   = 'RECOMPENSA_EDITADA';
    public const BITACORA_CANCELADA = 'RECOMPENSA_CANCELADA';
    public const BITACORA_ELIMINADA = 'RECOMPENSA_ELIMINADA';
}
