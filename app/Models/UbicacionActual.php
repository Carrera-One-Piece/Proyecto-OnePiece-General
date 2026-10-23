<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Última ubicación conocida de cada tripulación, personaje, barco u
 * organización (incluida la Marina). Una sola fila por entidad: se
 * sobrescribe cada vez que alguien la mueve (ver UbicacionController).
 *
 * El histórico completo de movimientos sigue viviendo en
 * historial_movimientos; esta tabla solo guarda el "ahora mismo" para
 * que el mapa no tenga que buscar en todo el histórico.
 */
class UbicacionActual extends Model
{
    protected $table = 'ubicacion_actual';

    protected $primaryKey = 'ID_ubicacion';

    public $timestamps = false;

    protected $fillable = [
        'Tipo_entidad',
        'ID_entidad',
        'ID_isla',
        'Coordenada_X',
        'Coordenada_Y',
        'Fecha_actualizacion',
    ];

    protected function casts(): array
    {
        return [
            'Fecha_actualizacion' => 'datetime',
        ];
    }
}
