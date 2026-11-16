<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReporteInteligencia extends Model
{
    protected $table = 'reporte_inteligencia';

    protected $primaryKey = 'ID_reporte';

    public $timestamps = false;

    protected $fillable = [
        'Titulo',
        'Contenido',
        'Fuente',
        'Confiabilidad',
        'Fecha_reporte',
        'ID_informante',
    ];

    protected function casts(): array
    {
        return [
            'Fecha_reporte' => 'datetime',
        ];
    }
}