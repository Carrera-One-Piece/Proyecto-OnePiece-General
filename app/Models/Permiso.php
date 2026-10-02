<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permiso extends Model
{
    protected $table = 'permisos';

    protected $primaryKey = 'ID_permiso';

    public $timestamps = false;

    protected $fillable = [
        'Nombre_permiso',
        'Descripcion',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Rol::class,
            'rol_permiso',
            'ID_permiso',
            'ID_rol'
        );
    }
}