<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Rol;

class RolSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'Nombre_rol' => 'Almirante de la Marina',
                'Descripcion' => 'Define lineamientos estratégicos.',
            ],
            [
                'Nombre_rol' => 'Director de Inteligencia Cipher Pol',
                'Descripcion' => 'Gestiona información confidencial.',
            ],
            [
                'Nombre_rol' => 'Analista de Recompensas',
                'Descripcion' => 'Determina niveles de amenaza.',
            ],
            [
                'Nombre_rol' => 'Especialista en Navegación de Grand Line',
                'Descripcion' => 'Modela rutas y condiciones marítimas.',
            ],
            [
                'Nombre_rol' => 'Desarrollador de Sistemas de Monitoreo',
                'Descripcion' => 'Implementa seguimiento en tiempo real.',
            ],
            [
                'Nombre_rol' => 'Administrador de Bases Navales',
                'Descripcion' => 'Coordina información regional.',
            ],
            [
                'Nombre_rol' => 'Experto en Seguridad de Información',
                'Descripcion' => 'Protege datos clasificados.',
            ],
        ];

        foreach ($roles as $rol) {
            Rol::updateOrCreate(
                ['Nombre_rol' => $rol['Nombre_rol']],
                $rol
            );
        }
    }
}