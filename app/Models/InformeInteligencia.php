<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class InformeInteligencia extends Model
{
    protected $table = 'informe_inteligencia';

    protected $primaryKey = 'ID_informe';

    public $timestamps = false;

    protected $fillable = [
        'Titulo',
        'Resumen_ejecutivo',
        'Autor',
        'Fecha_consolidacion',
    ];

    protected function casts(): array
    {
        return [
            'Fecha_consolidacion' => 'datetime',
        ];
    }

    public function reportes(): BelongsToMany
    {
        return $this->belongsToMany(
            ReporteInteligencia::class,
            'informe_reporte',
            'ID_informe',
            'ID_reporte'
        );
    }
}