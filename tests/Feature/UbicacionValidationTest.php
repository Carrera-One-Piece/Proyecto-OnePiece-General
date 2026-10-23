<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UbicacionValidationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Aquí solo se prueba la validación; el login y los permisos se prueban aparte.
        $this->withoutMiddleware();

        // La BD de pruebas (SQLite en memoria) está vacía: creamos las tablas
        // mínimas que consulta la regla "exists" del UbicacionRequest.
        $tablas = [
            ['tripulaciones', 'ID_tripulacion'],
            ['personajes', 'ID_personaje'],
            ['barcos', 'ID_barco'],
            ['organizaciones', 'ID_organizacion'],
        ];

        foreach ($tablas as [$tabla, $pk]) {
            Schema::create($tabla, function (Blueprint $t) use ($pk) {
                $t->increments($pk);
                $t->string('Nombre')->nullable();
            });

            DB::table($tabla)->insert(['Nombre' => 'Prueba']); // queda con ID 1
        }

        // Tabla donde el controlador guarda la ubicación (la usan los tests que aceptan datos).
        $this->artisan('migrate', [
            '--path' => 'database/migrations/2026_10_19_000001_create_ubicacion_actual_table.php',
        ]);
    }

    public function test_acepta_una_ubicacion_valida(): void
    {
        $this->putJson('/ubicaciones/barco/1', ['x' => 500, 'y' => 400])
            ->assertOk()
            ->assertJson(['ok' => true]);
    }

    public function test_acepta_todos_los_tipos_de_entidad(): void
    {
        foreach (['tripulacion', 'personaje', 'barco', 'organizacion'] as $tipo) {
            $this->putJson("/ubicaciones/{$tipo}/1", ['x' => 100, 'y' => 100])
                ->assertOk();
        }
    }

    public function test_acepta_los_limites_del_mapa(): void
    {
        $this->putJson('/ubicaciones/barco/1', ['x' => 0, 'y' => 0])->assertOk();
        $this->putJson('/ubicaciones/barco/1', ['x' => 1500, 'y' => 1000])->assertOk();
    }

    public function test_rechaza_x_no_numerica(): void
    {
        $this->putJson('/ubicaciones/barco/1', ['x' => 'abc', 'y' => 400])
            ->assertStatus(422)
            ->assertJsonValidationErrors('x');
    }

    public function test_rechaza_y_no_numerica(): void
    {
        $this->putJson('/ubicaciones/barco/1', ['x' => 500, 'y' => 'abc'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('y');
    }

    public function test_rechaza_x_fuera_del_mapa(): void
    {
        $this->putJson('/ubicaciones/barco/1', ['x' => 99999, 'y' => 400])
            ->assertStatus(422)
            ->assertJsonValidationErrors('x');

        $this->putJson('/ubicaciones/barco/1', ['x' => -1, 'y' => 400])
            ->assertStatus(422)
            ->assertJsonValidationErrors('x');
    }

    public function test_rechaza_y_fuera_del_mapa(): void
    {
        $this->putJson('/ubicaciones/barco/1', ['x' => 500, 'y' => 1001])
            ->assertStatus(422)
            ->assertJsonValidationErrors('y');

        $this->putJson('/ubicaciones/barco/1', ['x' => 500, 'y' => -5])
            ->assertStatus(422)
            ->assertJsonValidationErrors('y');
    }

    public function test_rechaza_cuando_faltan_coordenadas(): void
    {
        $this->putJson('/ubicaciones/barco/1', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['x', 'y']);
    }

    public function test_rechaza_una_entidad_que_no_existe(): void
    {
        $this->putJson('/ubicaciones/barco/9999', ['x' => 500, 'y' => 400])
            ->assertStatus(422)
            ->assertJsonValidationErrors('id');
    }

    public function test_rechaza_un_tipo_de_entidad_invalido(): void
    {
        $this->putJson('/ubicaciones/pizza/1', ['x' => 500, 'y' => 400])
            ->assertStatus(422)
            ->assertJsonValidationErrors('tipo');
    }
}