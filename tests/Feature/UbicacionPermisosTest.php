<?php

namespace Tests\Feature;

use App\Models\Organizacion;
use App\Models\Rol;
use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Permisos de las rutas de ubicación:
 *  - Especialista en Navegación: ve y edita (NAVEGACION.VER / NAVEGACION.EDITAR)
 *  - Cipher Pol y Almirante: solo ven
 *  - Sin sesión o sin el permiso: no entran
 *
 * Correr con:  php artisan test --filter=UbicacionPermisosTest
 */
class UbicacionPermisosTest extends TestCase
{
    use RefreshDatabase;

    private const URL_EDITAR = '/ubicaciones/barco/1';
    private const DATOS_VALIDOS = ['x' => 500, 'y' => 400];

    protected function setUp(): void
    {
        parent::setUp();

        $this->crearTablasDelSql();
        $this->seed(DatabaseSeeder::class);

        // Tabla que consulta la regla "exists" del UbicacionRequest (queda un barco con ID 1).
        Schema::create('barcos', function (Blueprint $t) {
            $t->increments('ID_barco');
            $t->string('Nombre')->nullable();
        });
        DB::table('barcos')->insert(['Nombre' => 'Barco de prueba']);
    }

    public function test_el_especialista_ve_la_pagina_y_edita(): void
    {
        $especialista = $this->especialista();

        $this->actingAs($especialista)->get('/ubicaciones/prueba')->assertOk();

        $this->actingAs($especialista)
            ->putJson(self::URL_EDITAR, self::DATOS_VALIDOS)
            ->assertOk()
            ->assertJson(['ok' => true]);
    }

    public function test_el_especialista_recibe_422_con_datos_invalidos(): void
    {
        $this->actingAs($this->especialista())
            ->putJson(self::URL_EDITAR, ['x' => 'abc', 'y' => 400])
            ->assertStatus(422)
            ->assertJsonValidationErrors('x');
    }

    public function test_cipher_pol_puede_ver_pero_no_editar(): void
    {
        $director = $this->usuario('director@cipherpol.gov');

        $this->actingAs($director)->get('/ubicaciones/prueba')->assertOk();

        $this->actingAs($director)
            ->putJson(self::URL_EDITAR, self::DATOS_VALIDOS)
            ->assertForbidden();
    }

    public function test_el_almirante_puede_ver_pero_no_editar(): void
    {
        $almirante = $this->usuario('almirante@marina.gov');

        $this->actingAs($almirante)->get('/ubicaciones/prueba')->assertOk();

        $this->actingAs($almirante)
            ->putJson(self::URL_EDITAR, self::DATOS_VALIDOS)
            ->assertForbidden();
    }

    public function test_un_rol_sin_permiso_de_navegacion_no_entra(): void
    {
        $experto = $this->usuario('seguridad@marina.gov');

        $this->actingAs($experto)->get('/ubicaciones/prueba')->assertForbidden();

        $this->actingAs($experto)
            ->putJson(self::URL_EDITAR, self::DATOS_VALIDOS)
            ->assertForbidden();
    }

    public function test_sin_sesion_no_se_puede_ver_ni_editar(): void
    {
        $this->get('/ubicaciones/prueba')->assertRedirect('/login');
        $this->put(self::URL_EDITAR, self::DATOS_VALIDOS)->assertRedirect('/login');
        $this->putJson(self::URL_EDITAR, self::DATOS_VALIDOS)->assertUnauthorized();
    }

    // ---------------------------------------------------------------- ayudas

    private function usuario(string $correo): Usuario
    {
        return Usuario::where('Correo', $correo)->firstOrFail();
    }

    /** Los usuarios demo no traen un Especialista en Navegación: se crea aquí. */
    private function especialista(): Usuario
    {
        return Usuario::updateOrCreate(['Correo' => 'navegacion.test@marina.gov'], [
            'Nombre' => 'Especialista de Prueba',
            'Contrasena' => Hash::make('Passw0rd!'),
            'ID_rol' => Rol::where('Nombre_rol', 'Especialista en Navegación de Grand Line')->value('ID_rol'),
            'ID_organizacion' => Organizacion::where('Nombre', 'Marina')->value('ID_organizacion'),
            'Estado' => Usuario::ESTADO_ACTIVO,
        ]);
    }

    /** Mismas tablas que crea LoginYPermisosTest (salen del .sql, no de migraciones). */
    private function crearTablasDelSql(): void
    {
        Schema::create('organizaciones', function (Blueprint $t) {
            $t->increments('ID_organizacion');
            $t->string('Nombre', 150);
            $t->integer('ID_lider')->nullable();
            $t->string('Tipo', 80)->nullable();
            $t->string('Estado', 50)->nullable();
            $t->string('URL_insignia', 500)->nullable();
        });

        Schema::create('roles', function (Blueprint $t) {
            $t->increments('ID_rol');
            $t->string('Nombre_rol', 50)->unique();
            $t->string('Descripcion', 255)->nullable();
        });

        Schema::create('permisos', function (Blueprint $t) {
            $t->increments('ID_permiso');
            $t->string('Nombre_permiso', 50)->unique();
            $t->string('Descripcion', 255)->nullable();
        });

        Schema::create('rol_permiso', function (Blueprint $t) {
            $t->integer('ID_rol');
            $t->integer('ID_permiso');
            $t->primary(['ID_rol', 'ID_permiso']);
        });

        Schema::create('usuarios', function (Blueprint $t) {
            $t->increments('ID_usuario');
            $t->string('Nombre', 150);
            $t->string('Correo', 150)->unique();
            $t->string('Contrasena', 255);
            $t->integer('ID_rol')->nullable();
            $t->string('Estado', 50)->default('Activo');
            $t->integer('ID_organizacion')->nullable();
        });
    }
}