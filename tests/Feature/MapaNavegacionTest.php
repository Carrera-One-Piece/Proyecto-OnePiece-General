<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Mapa navegable y endpoints de Navegación:
 *   /mapa, /mapa/datos, /navegacion/conexiones, /navegacion/recorridos,
 *   /navegacion/predicciones (consultar y generar) y el rol Marina.
 *
 * Correr con:  php artisan test --filter=MapaNavegacionTest
 */
class MapaNavegacionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->crearTablasDeLogin();
        $this->seed(DatabaseSeeder::class);
        $this->crearTablasDeNavegacion();
        $this->cargarMundoDePrueba();
    }

    // ------------------------------------------------------------ mapa

    public function test_el_rol_marina_ve_el_mapa_pero_no_edita_ni_predice(): void
    {
        $marina = $this->usuario('marina@marina.gov');

        $this->actingAs($marina)->get('/mapa')
            ->assertOk()
            ->assertSee('Carta de navegación')
            ->assertDontSee('Guardar ubicación');

        $this->actingAs($marina)->getJson('/mapa/datos')
            ->assertOk()
            ->assertJsonCount(4, 'islas')
            ->assertJsonPath('puedeEditar', false)
            ->assertJsonPath('puedePredecir', false)
            ->assertJsonPath('entidades', []);
    }

    public function test_el_rol_marina_solo_tiene_permisos_de_ver(): void
    {
        $permisos = Rol::where('Nombre_rol', 'Marina')->firstOrFail()
            ->permisos()->pluck('Nombre_permiso')->sort()->values()->all();

        $this->assertSame(
            ['DASHBOARD.VER', 'EVENTOS.VER', 'NAVEGACION.VER', 'RECOMPENSAS.VER', 'TRIPULACIONES.VER'],
            $permisos
        );
    }

    public function test_el_especialista_puede_editar_y_predecir(): void
    {
        $this->actingAs($this->especialista())->get('/mapa')
            ->assertOk()
            ->assertSee('Guardar ubicación');

        $this->actingAs($this->especialista())->getJson('/mapa/datos')
            ->assertOk()
            ->assertJsonPath('puedeEditar', true)
            ->assertJsonPath('puedePredecir', true)
            ->assertJsonCount(3, 'entidades.tripulacion');
    }

    public function test_sin_permiso_de_navegacion_no_se_ve_el_mapa(): void
    {
        $experto = $this->usuario('seguridad@marina.gov');

        $this->actingAs($experto)->get('/mapa')->assertForbidden();
        $this->actingAs($experto)->getJson('/mapa/datos')->assertForbidden();
        $this->actingAs($experto)->getJson('/navegacion/conexiones')->assertForbidden();
    }

    public function test_sin_sesion_el_mapa_manda_al_login(): void
    {
        $this->get('/mapa')->assertRedirect('/login');
        $this->getJson('/mapa/datos')->assertUnauthorized();
    }

    public function test_la_posicion_sale_de_ubicacion_actual_y_si_no_del_barco(): void
    {
        // Sin ubicacion_actual propia, la tripulación 2 se ubica por su barco.
        DB::table('ubicacion_actual')->where('ID_entidad', 2)->delete();

        $datos = $this->actingAs($this->usuario('marina@marina.gov'))->getJson('/mapa/datos')->json();
        $porId = collect($datos['tripulaciones'])->keyBy('id');

        // 1: tiene ubicacion_actual en la isla B
        $this->assertEquals([200, 100], $porId[1]['posicion']['coords']);
        $this->assertSame('Isla B', $porId[1]['posicion']['isla']);
        $this->assertSame('Ubicación registrada', $porId[1]['posicion']['fuente']);

        // 2: sin ubicacion_actual, se usa el último movimiento de su barco (isla A)
        $this->assertEquals([100, 100], $porId[2]['posicion']['coords']);
        $this->assertSame('Isla A', $porId[2]['posicion']['isla']);
        $this->assertSame('Último movimiento del barco', $porId[2]['posicion']['fuente']);

        // 3: no tiene nada
        $this->assertNull($porId[3]['posicion']);
    }

    // ------------------------------------------------------ conexiones

    public function test_conexiones_devuelve_las_rutas_y_filtra_por_isla(): void
    {
        $marina = $this->usuario('marina@marina.gov');

        $this->actingAs($marina)->getJson('/navegacion/conexiones')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.origen.nombre', 'Isla A')
            ->assertJsonPath('data.0.destino.nombre', 'Isla B')
            ->assertJsonPath('data.0.tiempo_estimado', 2);

        $this->actingAs($marina)->getJson('/navegacion/conexiones?isla=4')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.destino.nombre', 'Isla D');
    }

    // ------------------------------------------------------- recorridos

    public function test_el_recorrido_sale_en_orden_de_fecha(): void
    {
        $this->actingAs($this->usuario('marina@marina.gov'))
            ->getJson('/navegacion/recorridos/tripulacion/1')
            ->assertOk()
            ->assertJsonPath('fuente', 'tripulacion')
            ->assertJsonPath('total', 2)
            ->assertJsonPath('data.0.isla', 'Isla A')
            ->assertJsonPath('data.1.isla', 'Isla B')
            ->assertJsonPath('distancia_total', 100);
    }

    public function test_sin_historial_propio_se_usa_el_recorrido_del_barco(): void
    {
        $this->actingAs($this->usuario('marina@marina.gov'))
            ->getJson('/navegacion/recorridos/tripulacion/2')
            ->assertOk()
            ->assertJsonPath('fuente', 'barco')
            ->assertJsonPath('total', 2)
            ->assertJsonPath('data.1.isla', 'Isla A');
    }

    public function test_el_recorrido_filtra_por_fechas_y_valida_el_tipo(): void
    {
        $marina = $this->usuario('marina@marina.gov');

        $this->actingAs($marina)
            ->getJson('/navegacion/recorridos/tripulacion/1?desde=2024-02-01')
            ->assertOk()
            ->assertJsonPath('total', 1);

        $this->actingAs($marina)
            ->getJson('/navegacion/recorridos/dragon/1')
            ->assertStatus(422)
            ->assertJsonValidationErrors('tipo');
    }

    // ------------------------------------------------------ predicciones

    public function test_la_prediccion_elige_la_isla_con_mas_puntos_y_se_guarda(): void
    {
        // Tripulación 1 está en B. Desde B puede ir a C (aliada ahí, +20)
        // o a D (enemiga ahí, -25). Las distancias y el mar son iguales.
        $this->ubicarTripulacionTresEnD();

        $respuesta = $this->actingAs($this->especialista())
            ->postJson('/navegacion/predicciones', ['tripulacion' => 1])
            ->assertCreated()
            ->assertJsonPath('data.origen.nombre', 'Isla B')
            ->assertJsonPath('data.destino.nombre', 'Isla C')
            ->assertJsonCount(2, 'data.candidatas');

        $reglasC = collect($respuesta->json('data.candidatas.0.factores'))->pluck('regla');
        $reglasD = collect($respuesta->json('data.candidatas.1.factores'))->pluck('regla');

        $this->assertContains('Alianza_Presente', $reglasC);
        $this->assertContains('Presencia_Enemiga', $reglasD);

        $guardada = DB::table('movimientos_predichos')->first();
        $this->assertSame(1, (int) $guardada->ID_tripulacion);
        $this->assertSame(2, (int) $guardada->ID_isla_origen);
        $this->assertSame(3, (int) $guardada->ID_isla_destino_predicha);
        $this->assertIsArray(json_decode($guardada->Factores_aplicados, true));

        $this->actingAs($this->usuario('marina@marina.gov'))
            ->getJson('/navegacion/predicciones?tripulacion=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.destino.nombre', 'Isla C');
    }

    public function test_una_regla_inactiva_no_se_aplica(): void
    {
        $this->ubicarTripulacionTresEnD();
        DB::table('reglas_ponderacion')->where('Nombre_regla', 'Alianza_Presente')->update(['Activo' => 0]);

        $candidatas = $this->actingAs($this->especialista())
            ->postJson('/navegacion/predicciones', ['tripulacion' => 1])
            ->assertCreated()
            ->json('data.candidatas');

        $this->assertNotContains('Alianza_Presente', collect($candidatas)->flatMap(fn ($c) => collect($c['factores'])->pluck('regla')));
    }

    public function test_no_se_puede_predecir_sin_ubicacion_ni_sin_permiso(): void
    {
        $this->actingAs($this->especialista())
            ->postJson('/navegacion/predicciones', ['tripulacion' => 3])
            ->assertStatus(422)
            ->assertJsonValidationErrors('tripulacion');

        $this->actingAs($this->usuario('marina@marina.gov'))
            ->postJson('/navegacion/predicciones', ['tripulacion' => 1])
            ->assertForbidden();

        $this->actingAs($this->usuario('marina@marina.gov'))
            ->putJson('/ubicaciones/tripulacion/1', ['x' => 100, 'y' => 100])
            ->assertForbidden();
    }

    // ---------------------------------------------------------- ubicar

    public function test_al_ubicar_en_una_isla_se_guarda_la_isla(): void
    {
        $this->actingAs($this->especialista())
            ->putJson('/ubicaciones/tripulacion/3', ['x' => 300, 'y' => 100, 'isla' => 3])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseHas('ubicacion_actual', [
            'Tipo_entidad' => 'tripulacion',
            'ID_entidad' => 3,
            'ID_isla' => 3,
        ]);

        $this->actingAs($this->especialista())
            ->putJson('/ubicaciones/tripulacion/3', ['x' => 300, 'y' => 100, 'isla' => 999])
            ->assertStatus(422)
            ->assertJsonValidationErrors('isla');
    }

    // ---------------------------------------------------------- ayudas

    private function ubicarTripulacionTresEnD(): void
    {
        DB::table('ubicacion_actual')->insert([
            'Tipo_entidad' => 'tripulacion', 'ID_entidad' => 3, 'ID_isla' => 4,
            'Coordenada_X' => 300, 'Coordenada_Y' => 200, 'Fecha_actualizacion' => '2024-03-02 10:00:00',
        ]);
    }

    private function usuario(string $correo): Usuario
    {
        return Usuario::where('Correo', $correo)->firstOrFail();
    }

    private function especialista(): Usuario
    {
        return Usuario::where('Correo', 'navegacion@marina.gov')->firstOrFail();
    }

    /**
     * Mundo pequeño:  A(100,100) -> B(200,100) -> C(300,100)
     *                                         \-> D(300,200)
     * Tripulación 1 (aliada de 2, enemiga de 3) está en B.
     * Tripulación 2 está registrada en C (y su barco terminó en A). La
     * tripulación 3 no tiene ubicación: en las pruebas de predicción se pone en D.
     */
    private function cargarMundoDePrueba(): void
    {
        DB::table('mares')->insert([['ID_mar' => 5, 'Nombre' => 'Grand Line', 'Peligrosidad' => 2]]);

        DB::table('isla')->insert([
            ['ID_isla' => 1, 'Nombre' => 'Isla A', 'ID_mar' => 5, 'Tipo' => 'Pueblo', 'Coordenada_X' => 100, 'Coordenada_Y' => 100],
            ['ID_isla' => 2, 'Nombre' => 'Isla B', 'ID_mar' => 5, 'Tipo' => 'Pueblo', 'Coordenada_X' => 200, 'Coordenada_Y' => 100],
            ['ID_isla' => 3, 'Nombre' => 'Isla C', 'ID_mar' => 5, 'Tipo' => 'Pueblo', 'Coordenada_X' => 300, 'Coordenada_Y' => 100],
            ['ID_isla' => 4, 'Nombre' => 'Isla D', 'ID_mar' => 5, 'Tipo' => 'Pueblo', 'Coordenada_X' => 300, 'Coordenada_Y' => 200],
        ]);

        DB::table('isla_adyacente')->insert([
            ['ID_isla_origen' => 1, 'ID_isla_destino' => 2, 'Distancia' => 100, 'Tiempo_estimado' => 2, 'Observaciones' => null],
            ['ID_isla_origen' => 2, 'ID_isla_destino' => 3, 'Distancia' => 100, 'Tiempo_estimado' => 2, 'Observaciones' => null],
            ['ID_isla_origen' => 2, 'ID_isla_destino' => 4, 'Distancia' => 100, 'Tiempo_estimado' => 2, 'Observaciones' => 'Corriente fuerte'],
        ]);

        DB::table('tripulaciones')->insert([
            ['ID_tripulacion' => 1, 'Nombre' => 'Piratas Uno', 'Estado' => 'Activa'],
            ['ID_tripulacion' => 2, 'Nombre' => 'Piratas Dos', 'Estado' => 'Activa'],
            ['ID_tripulacion' => 3, 'Nombre' => 'Piratas Tres', 'Estado' => 'Activa'],
        ]);

        DB::table('barcos')->insert([
            ['ID_barco' => 2, 'Nombre' => 'Barco Dos', 'ID_tripulacion' => 2],
        ]);

        // El barco de la tripulación 2 terminó en A (antes pasó por C).
        DB::table('movimiento_barco')->insert([
            ['ID_barco' => 2, 'Coordenada_X' => 300, 'Coordenada_Y' => 100, 'Fecha_registro' => '2024-01-01 10:00:00'],
            ['ID_barco' => 2, 'Coordenada_X' => 100, 'Coordenada_Y' => 100, 'Fecha_registro' => '2024-03-01 10:00:00'],
        ]);

        DB::table('ubicacion_actual')->insert([
            'Tipo_entidad' => 'tripulacion', 'ID_entidad' => 1, 'ID_isla' => 2,
            'Coordenada_X' => 200, 'Coordenada_Y' => 100, 'Fecha_actualizacion' => '2024-03-01 10:00:00',
        ]);

        DB::table('historial_movimientos')->insert([
            ['Tipo_entidad' => 'Tripulacion', 'ID_entidad' => 1, 'Coordenada_X' => 200, 'Coordenada_Y' => 100, 'Fecha_registro' => '2024-02-15 10:00:00', 'Observaciones' => 'Llega a B'],
            ['Tipo_entidad' => 'Tripulacion', 'ID_entidad' => 1, 'Coordenada_X' => 100, 'Coordenada_Y' => 100, 'Fecha_registro' => '2024-01-10 10:00:00', 'Observaciones' => 'Sale de A'],
        ]);

        DB::table('relacion_tripulaciones')->insert([
            ['ID_tripulacion_A' => 1, 'ID_tripulacion_B' => 2, 'Tipo_relacion' => 'Alianza'],
            ['ID_tripulacion_A' => 3, 'ID_tripulacion_B' => 1, 'Tipo_relacion' => 'Rivalidad'],
        ]);

        // Para la predicción: la 2 está en C y la 3 en D.
        DB::table('ubicacion_actual')->insert([
            ['Tipo_entidad' => 'tripulacion', 'ID_entidad' => 2, 'ID_isla' => 3, 'Coordenada_X' => 300, 'Coordenada_Y' => 100, 'Fecha_actualizacion' => '2024-03-02 10:00:00'],
        ]);

        DB::table('reglas_ponderacion')->insert([
            ['Nombre_regla' => 'Presencia_Enemiga', 'Peso' => -25, 'Activo' => 1],
            ['Nombre_regla' => 'Objetivo_Estrategico', 'Peso' => 50, 'Activo' => 1],
            ['Nombre_regla' => 'Recompensa_Disponible', 'Peso' => 30, 'Activo' => 1],
            ['Nombre_regla' => 'Alianza_Presente', 'Peso' => 20, 'Activo' => 1],
            ['Nombre_regla' => 'Evento_Reciente', 'Peso' => 15, 'Activo' => 1],
            ['Nombre_regla' => 'Distancia_Navegacion', 'Peso' => -10, 'Activo' => 1],
            ['Nombre_regla' => 'Peligrosidad_Mar', 'Peso' => -15, 'Activo' => 1],
        ]);
    }

    private function crearTablasDeNavegacion(): void
    {
        Schema::create('mares', function (Blueprint $t) {
            $t->integer('ID_mar')->primary();
            $t->string('Nombre');
            $t->integer('Peligrosidad')->nullable();
        });

        Schema::create('isla', function (Blueprint $t) {
            $t->integer('ID_isla')->primary();
            $t->string('Nombre');
            $t->integer('ID_mar');
            $t->string('Tipo')->nullable();
            $t->text('Descripcion')->nullable();
            $t->decimal('Coordenada_X', 10, 6)->nullable();
            $t->decimal('Coordenada_Y', 10, 6)->nullable();
            $t->string('URL_imagen')->nullable();
        });

        Schema::create('isla_adyacente', function (Blueprint $t) {
            $t->increments('ID_adyacencia');
            $t->integer('ID_isla_origen');
            $t->integer('ID_isla_destino');
            $t->decimal('Distancia', 10, 2)->nullable();
            $t->decimal('Tiempo_estimado', 10, 2)->nullable();
            $t->string('Observaciones')->nullable();
        });

        Schema::create('tripulaciones', function (Blueprint $t) {
            $t->increments('ID_tripulacion');
            $t->string('Nombre');
            $t->string('Estado')->nullable();
            $t->decimal('Bounty_general', 15, 2)->nullable();
            $t->string('URL_bandera')->nullable();
            $t->string('URL_imagen')->nullable();
        });

        Schema::create('barcos', function (Blueprint $t) {
            $t->increments('ID_barco');
            $t->string('Nombre');
            $t->integer('ID_tripulacion')->nullable();
        });

        Schema::create('movimiento_barco', function (Blueprint $t) {
            $t->increments('ID_movimiento_barco');
            $t->integer('ID_barco');
            $t->decimal('Coordenada_X', 10, 6);
            $t->decimal('Coordenada_Y', 10, 6);
            $t->dateTime('Fecha_registro')->nullable();
        });

        Schema::create('historial_movimientos', function (Blueprint $t) {
            $t->increments('ID_movimiento');
            $t->string('Tipo_entidad');
            $t->integer('ID_entidad');
            $t->decimal('Coordenada_X', 10, 6)->nullable();
            $t->decimal('Coordenada_Y', 10, 6)->nullable();
            $t->dateTime('Fecha_registro');
            $t->text('Observaciones')->nullable();
        });

        Schema::create('relacion_tripulaciones', function (Blueprint $t) {
            $t->increments('ID_relacion');
            $t->integer('ID_tripulacion_A');
            $t->integer('ID_tripulacion_B');
            $t->string('Tipo_relacion')->nullable();
            $t->string('Estado')->nullable();
            $t->integer('ID_evento')->nullable();
        });

        Schema::create('reglas_ponderacion', function (Blueprint $t) {
            $t->increments('ID_regla');
            $t->string('Nombre_regla');
            $t->text('Descripcion')->nullable();
            $t->decimal('Peso', 10, 2);
            $t->boolean('Activo')->default(true);
            $t->integer('ID_evento')->nullable();
        });

        Schema::create('movimientos_predichos', function (Blueprint $t) {
            $t->increments('ID_prediccion');
            $t->integer('ID_tripulacion');
            $t->integer('ID_isla_origen')->nullable();
            $t->integer('ID_isla_destino_predicha')->nullable();
            $t->decimal('Puntaje_total', 10, 2)->nullable();
            $t->text('Factores_aplicados')->nullable();
            $t->dateTime('Fecha_prediccion');
            $t->integer('ID_isla_resultado_real')->nullable();
            $t->boolean('Acierto')->nullable();
        });
    }

    /** Mismas tablas que crean los otros tests de login (salen del .sql, no de migraciones). */
    private function crearTablasDeLogin(): void
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
