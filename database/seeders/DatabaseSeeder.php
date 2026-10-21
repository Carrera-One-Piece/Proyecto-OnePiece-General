<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    
    public function run(): void
    {
        
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        
        $permisosEventos = [
            'EVENTOS.VER',
            'EVENTOS.CREAR',
            'EVENTOS.EDITAR',
            'EVENTOS.ELIMINAR'
        ];

        foreach ($permisosEventos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso]);
        }

        $rolCipherPol = Role::firstOrCreate(['name' => 'Cipher Pol']);
        $rolCipherPol->givePermissionTo($permisosEventos);
    }
}
