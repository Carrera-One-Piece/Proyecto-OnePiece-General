<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Evento (RF13-RF15). Estructura según el .sql del equipo:
 * eventos(ID_evento, Nombre, ID_tipo_evento, ID_isla, Fecha_evento,
 * Descripcion, Nivel_amenaza).
 *
 * Los triggers de MySQL (trg_eventos_after_insert / after_update) ya
 * dejan el registro en auditoria_eventos, no hay que hacerlo a mano.
 */
class Evento extends Model
{
    protected $table = 'eventos';

    protected $primaryKey = 'ID_evento';

    public $timestamps = false;

    protected $fillable = [
        'Nombre',
        'ID_tipo_evento',
        'ID_isla',
        'Fecha_evento',
        'Descripcion',
        'Nivel_amenaza',
    ];

    protected function casts(): array
    {
        return [
            'Fecha_evento' => 'datetime',
        ];
    }
}