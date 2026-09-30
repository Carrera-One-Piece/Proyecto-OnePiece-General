<?php

namespace Database\Seeders;

use App\Models\Organizacion;
use Illuminate\Database\Seeder;

/**
 * Las 3 organizaciones del gobierno, las únicas que se pueden elegir al registrarse.
 *
 * Van con Tipo = 'Gobierno' para distinguirlas de las organizaciones piratas
 * que carga el grupo 4 en la misma tabla.
 */
class OrganizacionSeeder extends Seeder
{
    public function run(): void
    {
        $organizaciones = [
            'Marina',
            'Cipher Pol',
            'Gobierno Afiliado',
        ];

        foreach ($organizaciones as $nombre) {
            Organizacion::updateOrCreate(
                ['Nombre' => $nombre],
                ['Tipo' => Organizacion::TIPO_GOBIERNO, 'Estado' => 'Activo']
            );
        }
    }
}
