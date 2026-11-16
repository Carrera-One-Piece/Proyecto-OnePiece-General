<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EventoValidationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Aquí solo se prueba validación y CRUD; los permisos se prueban aparte.
        $this->withoutMiddleware();

        // BD de pruebas (SQLite en memoria) vacía: creamos las tablas mínimas
        // con los mismos nombres que el .sql del equipo.
        Schema::create('tipos_evento', function (Blueprint $t) {
            $t->increments('ID_tipo_evento');
            $t->string('Nombre');
        });
        Schema::create('isla', function (Blueprint $t) {
            $t->increments('ID_isla');
            $t->string('Nombre');
        });
        Schema::create('eventos', function (Blueprint $t) {
            $t->increments('ID_evento');
            $t->string('Nombre', 150);
            $t->integer('ID_tipo_evento');
            $t->integer('ID_isla');
            $t->dateTime('Fecha_evento')->nullable();
            $t->text('Descripcion')->nullable();
            $t->string('Nivel_amenaza', 50)->nullable();
        });

        DB::table('tipos_evento')->insert(['Nombre' => 'Batalla']); // ID 1
        DB::table('isla')->insert(['Nombre' => 'Isla de prueba']);   // ID 1
    }

    private function datosValidos(array $cambios = []): array
    {
        return array_merge([
            'Nombre'         => 'Batalla de prueba',
            'ID_tipo_evento' => 1,
            'ID_isla'        => 1,
            'Nivel_amenaza'  => 'Alta',
        ], $cambios);
    }

    public function test_crea_un_evento_valido(): void
    {
        $this->postJson('/eventos', $this->datosValidos())
            ->assertStatus(201)
            ->assertJsonPath('data.Nombre', 'Batalla de prueba');

        $this->assertDatabaseHas('eventos', ['Nombre' => 'Batalla de prueba']);
    }

    public function test_lista_y_muestra_eventos(): void
    {
        $this->postJson('/eventos', $this->datosValidos());

        $this->getJson('/eventos')->assertOk()->assertJsonCount(1);
        $this->getJson('/eventos/1')->assertOk()->assertJsonPath('Nombre', 'Batalla de prueba');
    }

    public function test_actualiza_un_evento(): void
    {
        $this->postJson('/eventos', $this->datosValidos());

        $this->putJson('/eventos/1', ['Nombre' => 'Nombre nuevo'])
            ->assertOk()
            ->assertJsonPath('data.Nombre', 'Nombre nuevo');
    }

    public function test_elimina_un_evento(): void
    {
        $this->postJson('/eventos', $this->datosValidos());

        $this->deleteJson('/eventos/1')->assertOk();
        $this->assertDatabaseMissing('eventos', ['ID_evento' => 1]);
    }

    public function test_rechaza_sin_nombre(): void
    {
        $this->postJson('/eventos', $this->datosValidos(['Nombre' => '']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('Nombre');
    }

    public function test_rechaza_un_tipo_de_evento_que_no_existe(): void
    {
        $this->postJson('/eventos', $this->datosValidos(['ID_tipo_evento' => 999]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('ID_tipo_evento');
    }

    public function test_rechaza_una_isla_que_no_existe(): void
    {
        $this->postJson('/eventos', $this->datosValidos(['ID_isla' => 999]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('ID_isla');
    }

    public function test_rechaza_un_evento_inexistente(): void
    {
        $this->getJson('/eventos/9999')->assertStatus(404);
    }
}