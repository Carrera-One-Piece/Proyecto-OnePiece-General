<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Un pirata (o cualquier personaje) de la tabla `personajes`.
 *
 * Es un DATO del sistema (Capa 2): Luffy no inicia sesión, es una fila aquí.
 * La tabla es del grupo de Tripulaciones; el módulo de Recompensas solo la lee.
 */
class Personaje extends Model
{
    protected $table = 'personajes';

    protected $primaryKey = 'ID_personaje';

    public $timestamps = false;

    // Todas sus recompensas, también las canceladas.
    public function recompensas(): HasMany
    {
        return $this->hasMany(Recompensa::class, 'ID_personaje', 'ID_personaje');
    }

    // La recompensa vigente (una sola fila Activa por pirata), o null.
    public function recompensaActual(): HasOne
    {
        return $this->hasOne(Recompensa::class, 'ID_personaje', 'ID_personaje')
            ->where('Estado', Recompensa::ESTADO_ACTIVA);
    }

    // Por la tabla intermedia tripulacion_personaje (Rol = 'Capitán', etc.).
    public function tripulaciones(): BelongsToMany
    {
        return $this->belongsToMany(
            Tripulacion::class,
            'tripulacion_personaje',
            'ID_personaje',
            'ID_tripulacion'
        )->withPivot('Rol', 'Fecha_Ingreso');
    }

    // Para los que no tienen tripulación (Cross Guild, Marina...).
    public function organizaciones(): BelongsToMany
    {
        return $this->belongsToMany(
            Organizacion::class,
            'organizacion_miembro',
            'ID_personaje',
            'ID_organizacion'
        )->withPivot('Cargo', 'Estado');
    }
}
