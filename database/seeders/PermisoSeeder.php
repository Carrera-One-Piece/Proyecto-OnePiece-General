<?php

namespace Database\Seeders;

use App\Models\Permiso;
use App\Models\Rol;
use Illuminate\Database\Seeder;

/**
 * Llena `permisos` y `rol_permiso`.
 *
 * ⚠ PROPUESTA: sale de docs/matriz-permisos.md del grupo de Login y todavía
 * falta ponerla de acuerdo con el reparto por rol que propuso el CTO en el
 * análisis del IEEE. Cuando se acuerde, se cambia la lista MATRIZ de abajo.
 *
 * Cada permiso se guarda como MODULO.ACCION en Nombre_permiso, con punto.
 * Correr después de RolSeeder:  php artisan db:seed --class=PermisoSeeder
 */
class PermisoSeeder extends Seeder
{
    private const MODULOS = [
        'TRIPULACIONES' => 'tripulaciones',
        'RECOMPENSAS' => 'recompensas',
        'ORDENES_CAPTURA' => 'órdenes de captura',
        'NAVEGACION' => 'rutas y navegación',
        'EVENTOS' => 'eventos',
        'ALIANZAS' => 'alianzas',
        'AMENAZAS' => 'análisis de amenazas',
        'INFORMES' => 'informes de inteligencia',
        'DASHBOARD' => 'el dashboard',
        'USUARIOS' => 'usuarios, roles y permisos',
    ];

    private const ACCIONES = [
        'VER' => 'Ver',
        'CREAR' => 'Crear',
        'EDITAR' => 'Editar',
        'ELIMINAR' => 'Eliminar',
    ];

    private const CRUD = ['VER', 'CREAR', 'EDITAR', 'ELIMINAR'];

    // Quién puede hacer qué. Lo que no aparece, no se puede.
    private const MATRIZ = [
        'Almirante de la Marina' => [
            'TRIPULACIONES' => ['VER'], 'RECOMPENSAS' => ['VER'], 'ORDENES_CAPTURA' => ['VER'],
            'NAVEGACION' => ['VER'], 'EVENTOS' => ['VER'], 'ALIANZAS' => ['VER'], 'AMENAZAS' => ['VER'],
            'INFORMES' => ['VER', 'CREAR'], 'DASHBOARD' => self::CRUD, 'USUARIOS' => ['VER'],
        ],
        'Director de Inteligencia Cipher Pol' => [
            'TRIPULACIONES' => self::CRUD, 'ORDENES_CAPTURA' => self::CRUD, 'NAVEGACION' => ['VER'],
            'EVENTOS' => ['VER'], 'ALIANZAS' => self::CRUD, 'AMENAZAS' => ['VER'],
            'INFORMES' => self::CRUD, 'DASHBOARD' => ['VER'],
        ],
        'Analista de Recompensas' => [
            'TRIPULACIONES' => ['VER'], 'RECOMPENSAS' => self::CRUD, 'ALIANZAS' => ['VER'],
            'AMENAZAS' => self::CRUD, 'INFORMES' => ['VER'], 'DASHBOARD' => ['VER'],
        ],
        'Especialista en Navegación de Grand Line' => [
            'TRIPULACIONES' => ['VER'], 'NAVEGACION' => self::CRUD, 'EVENTOS' => self::CRUD,
            'DASHBOARD' => ['VER'],
        ],
        'Administrador de Bases Navales' => [
            'TRIPULACIONES' => ['VER'], 'ORDENES_CAPTURA' => ['VER'], 'NAVEGACION' => ['VER'],
            'EVENTOS' => ['VER'], 'INFORMES' => ['VER'], 'DASHBOARD' => ['VER'],
        ],
        'Experto en Seguridad de Información' => [
            'USUARIOS' => self::CRUD, 'BITACORA' => ['VER'],
        ],
        // Sin requerimientos en el IEEE: queda sin permisos hasta que el Product Owner decida.
        'Desarrollador de Sistemas de Monitoreo' => [],
    ];

    public function run(): void
    {
        foreach (self::MODULOS as $modulo => $descripcion) {
            foreach (self::ACCIONES as $accion => $verbo) {
                $this->crearPermiso("{$modulo}.{$accion}", "{$verbo} {$descripcion}");
            }
        }

        // La bitácora solo se consulta, nadie la edita.
        $this->crearPermiso('BITACORA.VER', 'Ver la bitácora de accesos');

        foreach (self::MATRIZ as $nombreRol => $permisosPorModulo) {
            $rol = Rol::where('Nombre_rol', $nombreRol)->first();

            if (! $rol) {
                $this->command?->warn("No existe el rol '{$nombreRol}'. Corra primero RolSeeder.");

                continue;
            }

            $nombres = [];
            foreach ($permisosPorModulo as $modulo => $acciones) {
                foreach ($acciones as $accion) {
                    $nombres[] = "{$modulo}.{$accion}";
                }
            }

            $ids = Permiso::whereIn('Nombre_permiso', $nombres)->pluck('ID_permiso');

            // Sin "detaching": no borra lo que alguien haya marcado a mano en la matriz.
            $rol->permisos()->syncWithoutDetaching($ids);
        }
    }

    private function crearPermiso(string $nombre, string $descripcion): void
    {
        Permiso::updateOrCreate(['Nombre_permiso' => $nombre], ['Descripcion' => $descripcion]);
    }
}
