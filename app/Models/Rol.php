<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rol extends Model
{
    protected $table = 'roles';

    protected $primaryKey = 'ID_rol';

    public $timestamps = false;

    protected $fillable = [
        'Nombre_rol',
        'Descripcion',
    ];

    public function permisos(): BelongsToMany
    {
        return $this->belongsToMany(
            Permiso::class,
            'rol_permiso',
            'ID_rol',
            'ID_permiso'
        );
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(
            Usuario::class,
            'ID_rol',
            'ID_rol'
        );
    }
}