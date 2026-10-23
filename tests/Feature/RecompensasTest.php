<?php

namespace Tests\Feature;

use App\Models\AuditoriaRecompensa;
use App\Models\Bitacora;
use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Pruebas del módulo de Recompensas (rebanada C).
 *
 * Ojo: corren en SQLite en memoria, donde NO existen los triggers de la
 * base. Por eso aquí no se prueba que el historial se llene solo (eso se
 * revisa a mano en phpMyAdmin). Aquí se prueban permisos y reglas.
 *
 * Los permisos y los usuarios salen de los seeders reales (DatabaseSeeder,
 * igual que LoginYPermisosTest), así que si alguien cambia la MATRIZ por
 * error, estas pruebas lo atrapan.
 *
 * Correr con:  php artisan test --filter=RecompensasTest
 */
class RecompensasTest extends TestCase
{
    use RefreshDatabase;

    private const GESTOR    = 'Gestor de Recompensas';
    private const CIPHERPOL = 'Director de Inteligencia Cipher Pol';

    // Usuarios de UsuarioDemoSeeder.
    private const CORREOS = [
        self::GESTOR    => 'gestor@marina.gov',
        self::CIPHERPOL => 'director@cipherpol.gov',
    ];

    private int $luffy;          // pirata con recompensa Activa
    private int $zoro;           // pirata SIN recompensa
    private int $recompensaLuffy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->crearTablasSiFaltan();
        $this->seed(DatabaseSeeder::class);

        $this->luffy = DB::table('personajes')->insertGetId([
            'Nombre' => 'Monkey D. Luffy',
            'Alias'  => 'Sombrero de Paja',
            'Estado' => 'Vivo',
        ]);

        $this->zoro = DB::table('personajes')->insertGetId([
            'Nombre' => 'Roronoa Zoro',
            'Alias'  => 'Cazador de Piratas',
            'Estado' => 'Vivo',
        ]);

        $this->recompensaLuffy = DB::table('recompensas')->insertGetId([
            'ID_personaje' => $this->luffy,
            'Valor'        => 3000000000,
            'Motivo'       => 'Emperador del Mar',
            'Fecha'        => '2026-10-01',
            'Estado'       => 'Activa',
        ]);
    }

    // ------------------------------------------------------------------
    // Rol nuevo y permisos (seeders)
    // ------------------------------------------------------------------

    public function test_el_rol_gestor_existe_con_sus_cinco_permisos(): void
    {
        $this->assertEqualsCanonicalizing(
            [
                'RECOMPENSAS.VER',
                'RECOMPENSAS.CREAR',
                'RECOMPENSAS.EDITAR',
                'RECOMPENSAS.ELIMINAR',
                'TRIPULACIONES.VER',
            ],
            $this->permisosDe(self::GESTOR)
        );
    }

    public function test_existe_el_usuario_de_prueba_del_gestor(): void
    {
        $gestor = Usuario::where('Correo', 'gestor@marina.gov')->first();

        $this->assertNotNull($gestor);
        $this->assertTrue($gestor->estaActivo());
        $this->assertSame(self::GESTOR, $gestor->rol->Nombre_rol);
        $this->assertTrue(Hash::check('Passw0rd!', $gestor->Contrasena));
    }

    public function test_el_gestor_entra_con_su_clave(): void
    {
        $this->post('/login', ['Correo' => 'gestor@marina.gov', 'Contrasena' => 'Passw0rd!'])
            ->assertRedirect('/inicio');

        $this->assertAuthenticated();
    }

    public function test_cipher_pol_solo_puede_ver_recompensas(): void
    {
        $permisos = $this->permisosDe(self::CIPHERPOL);

        $this->assertContains('RECOMPENSAS.VER', $permisos);
        $this->assertNotContains('RECOMPENSAS.CREAR', $permisos);
        $this->assertNotContains('RECOMPENSAS.EDITAR', $permisos);
        $this->assertNotContains('RECOMPENSAS.ELIMINAR', $permisos);
    }

    // ------------------------------------------------------------------
    // Sin sesión
    // ------------------------------------------------------------------

    public function test_sin_sesion_no_se_entra_a_recompensas(): void
    {
        $this->get('/recompensas')->assertRedirect(route('login'));
        $this->get("/recompensas/pirata/{$this->luffy}")->assertRedirect(route('login'));
    }

    // ------------------------------------------------------------------
    // Cipher Pol: mira, no toca
    // ------------------------------------------------------------------

    public function test_cipher_pol_ve_la_lista_y_la_ficha(): void
    {
        $this->entrarComo(self::CIPHERPOL);

        $this->get('/recompensas')->assertOk();
        $this->get("/recompensas/pirata/{$this->luffy}")->assertOk();
    }

    public function test_cipher_pol_no_ve_botones_de_cambiar(): void
    {
        $this->entrarComo(self::CIPHERPOL);

        $this->get("/recompensas/pirata/{$this->luffy}")
            ->assertOk()
            ->assertDontSee("/recompensas/{$this->recompensaLuffy}/aumentar")
            ->assertDontSee("/recompensas/{$this->recompensaLuffy}/cancelar");
    }

    public function test_cipher_pol_recibe_403_al_crear(): void
    {
        $this->entrarComo(self::CIPHERPOL);

        $this->get('/recompensas/crear')->assertForbidden();
        $this->post('/recompensas', $this->datosNuevaRecompensa($this->zoro))->assertForbidden();

        $this->assertDatabaseMissing('recompensas', ['ID_personaje' => $this->zoro]);
    }

    public function test_cipher_pol_recibe_403_al_aumentar(): void
    {
        $this->entrarComo(self::CIPHERPOL);

        $this->post("/recompensas/{$this->recompensaLuffy}/aumentar", [
            'Valor'  => 4000000000,
            'Motivo' => 'Intento de Cipher Pol',
        ])->assertForbidden();

        $this->assertValorDeLuffy(3000000000);
    }

    public function test_cipher_pol_recibe_403_al_cancelar(): void
    {
        $this->entrarComo(self::CIPHERPOL);

        $this->post("/recompensas/{$this->recompensaLuffy}/cancelar", [
            'Estado' => 'Cancelada - Capturado',
        ])->assertForbidden();

        $this->assertEstadoDeLuffy('Activa');
    }

    public function test_cipher_pol_recibe_403_al_editar(): void
    {
        $this->entrarComo(self::CIPHERPOL);

        $this->get("/recompensas/{$this->recompensaLuffy}/editar")->assertForbidden();
        $this->put("/recompensas/{$this->recompensaLuffy}", [
            'Motivo' => 'Cambio no permitido',
        ])->assertForbidden();
    }

    public function test_cipher_pol_recibe_403_al_borrar(): void
    {
        $this->entrarComo(self::CIPHERPOL);

        $this->delete("/recompensas/{$this->recompensaLuffy}")->assertForbidden();

        $this->assertDatabaseHas('recompensas', ['ID_recompensa' => $this->recompensaLuffy]);
    }

    // ------------------------------------------------------------------
    // Gestor: puede todo
    // ------------------------------------------------------------------

    public function test_el_gestor_ve_lista_ficha_y_formulario(): void
    {
        $this->entrarComo(self::GESTOR);

        $this->get('/recompensas')->assertOk();
        $this->get("/recompensas/pirata/{$this->luffy}")->assertOk();
        $this->get('/recompensas/crear')->assertOk();
        $this->get("/recompensas/{$this->recompensaLuffy}/editar")->assertOk();
    }

    public function test_el_gestor_puede_crear_una_recompensa(): void
    {
        $this->entrarComo(self::GESTOR);

        $this->post('/recompensas', $this->datosNuevaRecompensa($this->zoro))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('recompensas', [
            'ID_personaje' => $this->zoro,
            'Estado'       => 'Activa',
        ]);
    }

    public function test_el_gestor_puede_aumentar_una_recompensa(): void
    {
        $this->entrarComo(self::GESTOR);

        $this->post("/recompensas/{$this->recompensaLuffy}/aumentar", [
            'Valor'  => 3500000000,
            'Motivo' => 'Incidente de Egghead',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertValorDeLuffy(3500000000);
    }

    public function test_el_gestor_puede_cancelar_una_recompensa(): void
    {
        $this->entrarComo(self::GESTOR);

        $this->post("/recompensas/{$this->recompensaLuffy}/cancelar", [
            'Estado' => 'Cancelada - Capturado',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertEstadoDeLuffy('Cancelada - Capturado');
    }

    public function test_el_gestor_puede_borrar_una_recompensa(): void
    {
        $this->entrarComo(self::GESTOR);

        $this->delete("/recompensas/{$this->recompensaLuffy}")->assertRedirect();

        $this->assertDatabaseMissing('recompensas', ['ID_recompensa' => $this->recompensaLuffy]);
    }

    // ------------------------------------------------------------------
    // Reglas
    // ------------------------------------------------------------------

    public function test_no_se_puede_crear_una_segunda_recompensa_activa(): void
    {
        $this->entrarComo(self::GESTOR);

        $this->post('/recompensas', $this->datosNuevaRecompensa($this->luffy))
            ->assertSessionHasErrors();

        $this->assertSame(
            1,
            DB::table('recompensas')
                ->where('ID_personaje', $this->luffy)
                ->where('Estado', 'Activa')
                ->count()
        );
    }

    public function test_si_se_puede_crear_otra_si_la_anterior_esta_cancelada(): void
    {
        $this->entrarComo(self::GESTOR);

        DB::table('recompensas')
            ->where('ID_recompensa', $this->recompensaLuffy)
            ->update(['Estado' => 'Cancelada - Capturado']);

        $this->post('/recompensas', $this->datosNuevaRecompensa($this->luffy))
            ->assertSessionHasNoErrors();

        $this->assertSame(
            1,
            DB::table('recompensas')
                ->where('ID_personaje', $this->luffy)
                ->where('Estado', 'Activa')
                ->count()
        );
    }

    public function test_no_se_puede_crear_para_un_pirata_que_no_existe(): void
    {
        $this->entrarComo(self::GESTOR);

        $this->post('/recompensas', $this->datosNuevaRecompensa(99999))
            ->assertSessionHasErrors('ID_personaje');
    }

    public function test_no_se_puede_aumentar_a_un_valor_menor(): void
    {
        $this->entrarComo(self::GESTOR);

        $this->post("/recompensas/{$this->recompensaLuffy}/aumentar", [
            'Valor'  => 1000,
            'Motivo' => 'Intento de bajarla',
        ])->assertSessionHasErrors();

        $this->assertValorDeLuffy(3000000000);
    }

    public function test_el_valor_con_puntos_de_miles_se_entiende(): void
    {
        // El formulario muestra "3.500.000.000" (public/js/berries.js).
        $this->entrarComo(self::GESTOR);

        $this->post("/recompensas/{$this->recompensaLuffy}/aumentar", [
            'Valor'  => '3.500.000.000',
            'Motivo' => 'Incidente de Egghead',
        ])->assertSessionHasNoErrors();
        $this->assertValorDeLuffy(3500000000);

        $this->post('/recompensas', ['Valor' => '1.111.000.000'] + $this->datosNuevaRecompensa($this->zoro))
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('recompensas', ['ID_personaje' => $this->zoro, 'Valor' => 1111000000]);
    }

    public function test_crear_marca_a_los_que_ya_tienen_recompensa_y_avisa_en_espanol(): void
    {
        $this->entrarComo(self::GESTOR);

        $this->get('/recompensas/crear')
            ->assertOk()
            ->assertSee('Monkey D. Luffy «Sombrero de Paja» — ya tiene recompensa activa')
            ->assertDontSee('Roronoa Zoro «Cazador de Piratas» — ya tiene');

        $this->post('/recompensas', ['ID_personaje' => '', 'Valor' => '1,5', 'Motivo' => ''])
            ->assertSessionHasErrors([
                'ID_personaje' => 'Falta el pirata.',
                'Valor' => 'El valor tiene que ser un número (sin comas).',
            ]);
    }

    public function test_la_casilla_de_aumentar_trae_el_valor_actual(): void
    {
        $this->entrarComo(self::GESTOR);

        $this->get("/recompensas/pirata/{$this->luffy}")
            ->assertSee('value="3.000.000.000"', false);
    }

    public function test_no_se_puede_aumentar_al_mismo_valor(): void
    {
        $this->entrarComo(self::GESTOR);

        $this->post("/recompensas/{$this->recompensaLuffy}/aumentar", [
            'Valor'  => 3000000000,
            'Motivo' => 'Sin cambio',
        ])->assertSessionHasErrors();
    }

    public function test_cancelar_no_borra_solo_cambia_el_estado(): void
    {
        $this->entrarComo(self::GESTOR);

        $this->post("/recompensas/{$this->recompensaLuffy}/cancelar", [
            'Estado' => 'Cancelada - Muerto',
        ]);

        $this->assertDatabaseHas('recompensas', [
            'ID_recompensa' => $this->recompensaLuffy,
            'Estado'        => 'Cancelada - Muerto',
        ]);
        $this->assertValorDeLuffy(3000000000);
    }

    public function test_cancelar_deja_la_fila_cancelar_en_el_historial(): void
    {
        // Esta fila la escribe Laravel (no hay trigger para el Estado).
        $this->entrarComo(self::GESTOR);

        $this->post("/recompensas/{$this->recompensaLuffy}/cancelar", [
            'Estado' => 'Cancelada - Capturado',
        ]);

        $this->assertDatabaseHas('auditoria_recompensas', [
            'ID_recompensa' => $this->recompensaLuffy,
            'Operacion'     => 'CANCELAR',
        ]);
        $this->assertSame(
            1,
            DB::table('auditoria_recompensas')
                ->where('ID_recompensa', $this->recompensaLuffy)
                ->where('Operacion', 'CANCELAR')
                ->count()
        );
    }

    public function test_cancelar_queda_en_la_bitacora_con_quien_lo_hizo(): void
    {
        $gestor = $this->entrarComo(self::GESTOR);

        $this->post("/recompensas/{$this->recompensaLuffy}/cancelar", [
            'Estado' => 'Cancelada - Capturado',
        ]);

        $this->assertTrue(
            Bitacora::where('Accion', AuditoriaRecompensa::BITACORA_CANCELADA)
                ->where('ID_usuario', $gestor->ID_usuario)
                ->exists()
        );
    }

    public function test_la_bitacora_dice_el_nombre_del_pirata(): void
    {
        $this->entrarComo(self::GESTOR);

        $this->post("/recompensas/{$this->recompensaLuffy}/aumentar", [
            'Valor'  => 3500000000,
            'Motivo' => 'Incidente de Egghead',
        ]);

        $detalle = Bitacora::where('Accion', AuditoriaRecompensa::BITACORA_AUMENTADA)->value('Detalle');

        $this->assertStringContainsString('Monkey D. Luffy', $detalle);
        $this->assertStringNotContainsString('pirata #', $detalle);
    }

    // ------------------------------------------------------------------
    // Pantalla del historial (partes/historial.blade.php)
    // ------------------------------------------------------------------

    public function test_historial_vacio_dice_sin_cambios_registrados(): void
    {
        $this->view('recompensas.partes.historial', [
            'personaje' => (object) ['ID_personaje' => $this->luffy],
        ])->assertSee('Sin cambios registrados');
    }

    public function test_historial_muestra_las_filas_en_espanol_y_con_puntos(): void
    {
        // Simula lo que harían los triggers en MySQL.
        DB::table('auditoria_recompensas')->insert([
            [
                'ID_recompensa'      => $this->recompensaLuffy,
                'Operacion'          => 'INSERT',
                'Valor_Anterior'     => null,
                'Valor_Nuevo'        => 3000000000,
                'Fecha_Modificacion' => '2026-10-01 10:00:00',
            ],
            [
                'ID_recompensa'      => $this->recompensaLuffy,
                'Operacion'          => 'UPDATE',
                'Valor_Anterior'     => 3000000000,
                'Valor_Nuevo'        => 3500000000,
                'Fecha_Modificacion' => '2026-10-05 10:00:00',
            ],
        ]);

        $this->view('recompensas.partes.historial', [
            'personaje' => (object) ['ID_personaje' => $this->luffy],
        ])
            ->assertDontSee('Sin cambios registrados')
            ->assertSeeInOrder(['Recompensa creada', 'Aumento'])
            ->assertSee('3.000.000.000')
            ->assertSee('3.500.000.000')
            ->assertDontSee('INSERT')
            ->assertDontSee('UPDATE');
    }

    public function test_historial_viene_de_la_mas_vieja_a_la_mas_nueva(): void
    {
        DB::table('auditoria_recompensas')->insert([
            ['ID_recompensa' => $this->recompensaLuffy, 'Operacion' => 'UPDATE',
             'Valor_Anterior' => 3000000000, 'Valor_Nuevo' => 3500000000,
             'Fecha_Modificacion' => '2026-10-05 10:00:00'],
            ['ID_recompensa' => $this->recompensaLuffy, 'Operacion' => 'INSERT',
             'Valor_Anterior' => null, 'Valor_Nuevo' => 3000000000,
             'Fecha_Modificacion' => '2026-10-01 10:00:00'],
        ]);

        $this->assertSame(
            ['INSERT', 'UPDATE'],
            AuditoriaRecompensa::delPersonaje($this->luffy)->pluck('Operacion')->all()
        );
    }

    // ------------------------------------------------------------------
    // Ayudantes
    // ------------------------------------------------------------------

    private function entrarComo(string $nombreRol): Usuario
    {
        $usuario = Usuario::where('Correo', self::CORREOS[$nombreRol])->firstOrFail();
        $this->actingAs($usuario);

        return $usuario;
    }

    private function permisosDe(string $nombreRol): array
    {
        return DB::table('roles')
            ->join('rol_permiso', 'roles.ID_rol', '=', 'rol_permiso.ID_rol')
            ->join('permisos', 'permisos.ID_permiso', '=', 'rol_permiso.ID_permiso')
            ->where('roles.Nombre_rol', $nombreRol)
            ->pluck('permisos.Nombre_permiso')
            ->all();
    }

    private function datosNuevaRecompensa(int $idPersonaje): array
    {
        return [
            'ID_personaje' => $idPersonaje,
            'Valor'        => 1111000000,
            'ID_evento'    => null,
            'Motivo'       => 'Recompensa de prueba',
        ];
    }

    private function assertValorDeLuffy(int $esperado): void
    {
        $this->assertEquals(
            $esperado,
            (float) DB::table('recompensas')
                ->where('ID_recompensa', $this->recompensaLuffy)
                ->value('Valor')
        );
    }

    private function assertEstadoDeLuffy(string $esperado): void
    {
        $this->assertSame(
            $esperado,
            DB::table('recompensas')
                ->where('ID_recompensa', $this->recompensaLuffy)
                ->value('Estado')
        );
    }

    /**
     * Las tablas del módulo vienen del .sql, no de migraciones, así que en
     * SQLite no existen. Se crean aquí con las mismas columnas del .sql.
     * Si la configuración de pruebas del login ya las crea, no se tocan.
     */
    private function crearTablasSiFaltan(): void
    {
        $tablas = [
            'roles' => function (Blueprint $t) {
                $t->increments('ID_rol');
                $t->string('Nombre_rol', 50)->unique();
                $t->string('Descripcion', 255)->nullable();
            },
            'permisos' => function (Blueprint $t) {
                $t->increments('ID_permiso');
                $t->string('Nombre_permiso', 50)->unique();
                $t->string('Descripcion', 255)->nullable();
            },
            'rol_permiso' => function (Blueprint $t) {
                $t->integer('ID_rol');
                $t->integer('ID_permiso');
                $t->primary(['ID_rol', 'ID_permiso']);
            },
            'organizaciones' => function (Blueprint $t) {
                $t->increments('ID_organizacion');
                $t->string('Nombre', 150);
                $t->integer('ID_lider')->nullable();
                $t->string('Tipo', 80)->nullable();
                $t->string('Estado', 50)->nullable();
                $t->string('URL_insignia', 500)->nullable();
            },
            'usuarios' => function (Blueprint $t) {
                $t->increments('ID_usuario');
                $t->string('Nombre', 150);
                $t->string('Correo', 150)->unique();
                $t->string('Contrasena', 255);
                $t->integer('ID_rol')->nullable();
                $t->string('Estado', 50)->default('Activo');
                $t->integer('ID_organizacion')->nullable();
            },
            'personajes' => function (Blueprint $t) {
                $t->increments('ID_personaje');
                $t->string('Nombre', 150);
                $t->string('Alias', 150)->nullable();
                $t->string('Raza', 50)->nullable();
                $t->string('Estado', 50)->nullable();
                $t->integer('ID_isla_origen')->nullable();
                $t->integer('Primer_avistamiento')->nullable();
                $t->decimal('Coordenada_X', 10, 6)->nullable();
                $t->decimal('Coordenada_Y', 10, 6)->nullable();
                $t->string('URL_imagen', 500)->nullable();
            },
            'eventos' => function (Blueprint $t) {
                $t->increments('ID_evento');
                $t->string('Nombre', 150);
                $t->integer('ID_tipo_evento')->nullable();
                $t->integer('ID_isla')->nullable();
                $t->dateTime('Fecha_evento')->nullable();
                $t->text('Descripcion')->nullable();
                $t->string('Nivel_amenaza', 50)->nullable();
            },
            'recompensas' => function (Blueprint $t) {
                $t->increments('ID_recompensa');
                $t->integer('ID_personaje');
                $t->decimal('Valor', 15, 2);
                $t->integer('ID_evento')->nullable();
                $t->text('Motivo')->nullable();
                $t->date('Fecha')->nullable();
                $t->string('Estado', 50)->default('Activa');
            },
            'auditoria_recompensas' => function (Blueprint $t) {
                $t->increments('ID_audit');
                $t->integer('ID_recompensa')->nullable();
                $t->string('Operacion', 50);
                $t->decimal('Valor_Anterior', 15, 2)->nullable();
                $t->decimal('Valor_Nuevo', 15, 2)->nullable();
                $t->dateTime('Fecha_Modificacion')->useCurrent();
            },
            'tripulaciones' => function (Blueprint $t) {
                $t->increments('ID_tripulacion');
                $t->string('Nombre', 150);
                $t->string('Estado', 50)->nullable();
                $t->decimal('Bounty_general', 15, 2)->nullable();
                $t->string('URL_bandera', 500)->nullable();
                $t->string('URL_imagen', 500)->nullable();
            },
            'tripulacion_personaje' => function (Blueprint $t) {
                $t->integer('ID_tripulacion');
                $t->integer('ID_personaje');
                $t->string('Rol', 100)->nullable();
                $t->date('Fecha_Ingreso')->nullable();
                $t->primary(['ID_tripulacion', 'ID_personaje']);
            },
            'organizacion_miembro' => function (Blueprint $t) {
                $t->increments('ID_afiliacion');
                $t->integer('ID_organizacion');
                $t->integer('ID_personaje');
                $t->string('Cargo', 100)->nullable();
                $t->date('Fecha_ingreso')->nullable();
                $t->string('Estado', 50)->default('Activo');
            },
        ];

        foreach ($tablas as $nombre => $definicion) {
            if (! Schema::hasTable($nombre)) {
                Schema::create($nombre, $definicion);
            }
        }
    }
}
