<?php

namespace Database\Seeders;

use App\Models\Organizacion;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * SOLO PARA PROBAR en la base local. Clave de todos: Passw0rd!
 *
 * Hace falta al menos un Experto en Seguridad activo: si no, nadie puede
 * aprobar a los que se registran.
 */
class UsuarioDemoSeeder extends Seeder
{
    public function run(): void
    {
        $usuarios = [
            ['seguridad@marina.gov', 'Experto Tashigi Renn', 'Experto en Seguridad de Información', 'Marina', Usuario::ESTADO_ACTIVO],
            ['almirante@marina.gov', 'Almirante Daigo Renn', 'Almirante de la Marina', 'Marina', Usuario::ESTADO_ACTIVO],
            ['director@cipherpol.gov', 'Director Sayo Kurane', 'Director de Inteligencia Cipher Pol', 'Cipher Pol', Usuario::ESTADO_ACTIVO],
            ['pendiente@gobierno.gov', 'Consejero Orlan Vesk', null, 'Gobierno Afiliado', Usuario::ESTADO_PENDIENTE],
        ];

        foreach ($usuarios as [$correo, $nombre, $rol, $organizacion, $estado]) {
            Usuario::updateOrCreate(['Correo' => $correo], [
                'Nombre' => $nombre,
                'Contrasena' => Hash::make('Passw0rd!'),
                'ID_rol' => $rol ? Rol::where('Nombre_rol', $rol)->value('ID_rol') : null,
                'ID_organizacion' => Organizacion::where('Nombre', $organizacion)->value('ID_organizacion'),
                'Estado' => $estado,
            ]);
        }
    }
}
