<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * php artisan db:seed
     *
     * Antes aquí se creaba un usuario en la tabla `users` de Laravel, pero esa
     * tabla no está en el .sql del equipo. Los usuarios del sistema viven en
     * `usuarios`.
     */
    public function run(): void
    {
        // Login y control de acceso (grupo Login y Registro). El orden importa.
        $this->call([
            OrganizacionSeeder::class,
            RolSeeder::class,
            PermisoSeeder::class,
            UsuarioDemoSeeder::class, // solo para probar en local
        ]);
    }
}
