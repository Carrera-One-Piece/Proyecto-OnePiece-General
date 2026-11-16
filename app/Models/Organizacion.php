<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * La tabla `organizaciones` es compartida: guarda las organizaciones del
 * gobierno (Marina, Cipher Pol, Gobierno Afiliado) y también las piratas
 * que carga el grupo 4 (Baroque Works, etc.).
 *
 * Para registrarse solo se pueden elegir las del gobierno. Se distinguen
 * porque su `Tipo` es 'Gobierno'.
 */
class Organizacion extends Model
{
    public const TIPO_GOBIERNO = 'Gobierno';

    protected $table = 'organizaciones';

    protected $primaryKey = 'ID_organizacion';

    public $timestamps = false;

    protected $fillable = [
        'Nombre',
        'Tipo',
        'Estado',
    ];

    // Colores y texto de cada organización en las pantallas de la rebanada A.
    private const PRESENTACION = [
        'Marina' => [
            'tema' => 'tema-marina',
            'descripcion' => 'Cuartel General de la Marina — Justicia Absoluta',
        ],
        'Cipher Pol' => [
            'tema' => 'tema-cp',
            'descripcion' => 'Agencia de inteligencia y operaciones encubiertas',
        ],
        'Gobierno Afiliado' => [
            'tema' => 'tema-gobierno',
            'descripcion' => 'Gobiernos de los reinos afiliados al Gobierno Mundial',
        ],
    ];

    public function scopeGubernamentales(Builder $query): Builder
    {
        return $query->where('Tipo', self::TIPO_GOBIERNO)->orderBy('Nombre');
    }

    public function tema(): string
    {
        return self::PRESENTACION[$this->Nombre]['tema'] ?? 'tema-marina';
    }

    public function descripcion(): string
    {
        return self::PRESENTACION[$this->Nombre]['descripcion'] ?? '';
    }
}
