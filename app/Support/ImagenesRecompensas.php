<?php

namespace App\Support;

use App\Models\Organizacion;
use App\Models\Personaje;
use App\Models\Tripulacion;

/**
 * De dónde sale cada imagen del módulo de Recompensas.
 *
 * 1. Si la base trae la ruta (URL_imagen, URL_bandera, URL_insignia), se usa esa.
 * 2. Si viene vacía (hoy vienen TODAS vacías), se busca en las listas de abajo:
 *    son las imágenes del Drive copiadas a public/img/. En la base las
 *    tripulaciones están en inglés y los archivos en español: por eso se
 *    emparejan a mano.
 * 3. Si no hay ninguna, se muestra la imagen por defecto (sin ícono roto).
 *
 * TEMPORAL: cuando el grupo 4 llene las columnas URL_..., estas listas sobran.
 */
class ImagenesRecompensas
{
    public const POR_DEFECTO = 'img/sin-imagen.svg';

    private const TRIPULACIONES = [
        'Straw Hat Pirates' => 'img/tripulaciones/sombrero-de-paja.webp',
        'Beasts Pirates' => 'img/tripulaciones/bestias.webp',
        'Blackbeard Pirates' => 'img/tripulaciones/barbanegra.webp',
        'Big Mom Pirates' => 'img/tripulaciones/big-mom.webp',
        'Heart Pirates' => 'img/tripulaciones/corazon.webp',
        'Donquixote Pirates' => 'img/tripulaciones/donquixote.webp',
        'Kid Pirates' => 'img/tripulaciones/kid.webp',
    ];

    private const PERSONAJES = [
        'Edward Newgate' => 'img/piratas/barbablanca.jpeg',
    ];

    public static function personaje(?Personaje $personaje): string
    {
        return self::elegir($personaje?->URL_imagen, self::PERSONAJES[$personaje?->Nombre] ?? null);
    }

    public static function tripulacion(?Tripulacion $tripulacion): string
    {
        return self::elegir(
            $tripulacion?->URL_bandera ?: $tripulacion?->URL_imagen,
            self::TRIPULACIONES[$tripulacion?->Nombre] ?? null
        );
    }

    public static function organizacion(?Organizacion $organizacion): string
    {
        return self::elegir($organizacion?->URL_insignia, null);
    }

    // La imagen del grupo del pirata: su tripulación o, si no tiene, su organización.
    public static function grupo(Personaje $personaje): string
    {
        $tripulacion = $personaje->tripulaciones->first();

        if ($tripulacion) {
            return self::tripulacion($tripulacion);
        }

        return self::organizacion($personaje->organizaciones->first());
    }

    // Para los carteles: la foto del pirata y, si no tiene, la bandera de su grupo.
    public static function cartel(Personaje $personaje): string
    {
        $foto = self::personaje($personaje);

        return $foto === asset(self::POR_DEFECTO) ? self::grupo($personaje) : $foto;
    }

    private static function elegir(?string $deLaBase, ?string $temporal): string
    {
        $ruta = $deLaBase ?: $temporal ?: self::POR_DEFECTO;

        // Una URL completa (https://...) se deja tal cual; una ruta local pasa por asset().
        return str_starts_with($ruta, 'http') ? $ruta : asset($ruta);
    }
}
