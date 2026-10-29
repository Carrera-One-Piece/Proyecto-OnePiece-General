<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * La recompensa de un pirata (tabla `recompensas`).
 *
 * Regla del módulo: cada pirata tiene UNA sola fila Activa. Aumentar cambia el
 * Valor de esa misma fila; el valor viejo lo guarda el trigger en
 * auditoria_recompensas. Cancelar cambia el Estado, nunca borra.
 */
class Recompensa extends Model
{
    public const ESTADO_ACTIVA = 'Activa';
    public const ESTADO_CAPTURADO = 'Cancelada - Capturado';
    public const ESTADO_MUERTO = 'Cancelada - Muerto';

    // Los dos motivos para cancelar (lo que se muestra en el formulario).
    public const ESTADOS_CANCELADA = [
        self::ESTADO_CAPTURADO => 'Capturado',
        self::ESTADO_MUERTO => 'Muerto',
    ];

    protected $table = 'recompensas';

    protected $primaryKey = 'ID_recompensa';

    public $timestamps = false;

    protected $fillable = ['ID_personaje', 'Valor', 'ID_evento', 'Motivo', 'Fecha', 'Estado'];

    public function personaje(): BelongsTo
    {
        return $this->belongsTo(Personaje::class, 'ID_personaje', 'ID_personaje');
    }

    // El evento que la causó, o null.
    public function evento(): BelongsTo
    {
        return $this->belongsTo(Evento::class, 'ID_evento', 'ID_evento');
    }

    // Filas de auditoria_recompensas, de la más vieja a la más nueva (rebanada C).
    public function historial(): HasMany
    {
        return $this->hasMany(AuditoriaRecompensa::class, 'ID_recompensa', 'ID_recompensa')
            ->orderBy('Fecha_Modificacion')->orderBy('ID_audit');
    }

    public function estaActiva(): bool
    {
        return $this->Estado === self::ESTADO_ACTIVA;
    }

    // Clase CSS de la etiqueta del estado (public/css/recompensas.css).
    public function claseEstado(): string
    {
        return match ($this->Estado) {
            self::ESTADO_ACTIVA => 'estado--activa',
            self::ESTADO_CAPTURADO => 'estado--capturado',
            self::ESTADO_MUERTO => 'estado--muerto',
            default => '',
        };
    }

    // ฿ 3.000.000.000 (con puntos de miles, como el historial).
    public static function berries($valor): string
    {
        return '฿ '.number_format((float) $valor, 0, ',', '.');
    }
}
