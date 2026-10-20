<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tripulacion extends Model
{
    protected $table = 'tripulaciones';
    protected $primaryKey = 'ID_tripulacion';
    public $timestamps = false;

    // Valores de Estado en la BD ('Disuelta' = baja lógica del RF04)
    public const ESTADOS = ['Activa', 'Inactiva', 'Derrotada', 'Disuelta'];

    protected $fillable = [
        'Nombre', 'Estado', 'Bounty_general',
        'URL_bandera', 'URL_imagen',
    ];

    protected $casts = [
        'Bounty_general' => 'decimal:2',
    ];
}