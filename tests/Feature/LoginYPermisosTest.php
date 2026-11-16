<?php

namespace Tests\Feature;

use App\Models\Bitacora;
use App\Models\Organizacion;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Pruebas del login, el registro, el guardia de permisos y la bitácora.
 *
 * Correr con:  php artisan test --filter=LoginYPermisosTest
 *
 * Usan una base SQLite en memoria (no tocan tu MySQL). Como las tablas
 * reales salen del .sql del equipo y no de migraciones, aquí se crean con
 * las mismas columnas que tiene ese .sql.
 */
class LoginYPermisosTest extends TestCase
{
    use RefreshDatabase;

    private const CLAVE = 'Passw0rd!';

    protected function setUp(): void
    {
        parent::setUp();

        $this->crearTablasDelSql();
        $this->seed(DatabaseSeeder::class);
    }

    // ------------------------------------------------------------------ login

    public function test_las_pantallas_publicas_cargan(): void
    {
        $this->get('/login')->assertOk()->assertSee('Identificación del Personal');
        $this->get('/registro')->assertOk()->assertSee('Gobierno Afiliado');
        $this->get('/pendiente')->assertOk()->assertSee('PENDIENTE');
    }

    public function test_usuario_activo_entra_y_queda_en_la_bitacora(): void
    {
        $this->post('/login', ['Correo' => 'seguridad@marina.gov', 'Contrasena' => self::CLAVE])
            ->assertRedirect('/inicio');

        $this->assertAuthenticatedAs($this->usuario('seguridad@marina.gov'));
        $this->assertTrue(Bitacora::where('Accion', Bitacora::LOGIN_OK)->where('Correo_intento', 'seguridad@marina.gov')->exists());

        $this->get('/inicio')->assertOk()->assertSee('Experto en Seguridad de Información')->assertSee('Bitácora de accesos');
    }

    public function test_clave_incorrecta_da_mensaje_generico_y_se_anota(): void
    {
        $this->from('/login')
            ->post('/login', ['Correo' => 'seguridad@marina.gov', 'Contrasena' => 'otra-clave'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['Correo' => 'Credenciales no reconocidas. Verifique su correo y su clave.']);

        $this->assertGuest();
        $this->assertTrue(Bitacora::where('Accion', Bitacora::LOGIN_FALLIDO)->exists());
    }

    public function test_correo_que_no_existe_da_el_mismo_mensaje(): void
    {
        $this->from('/login')
            ->post('/login', ['Correo' => 'nadie@marina.gov', 'Contrasena' => self::CLAVE])
            ->assertSessionHasErrors(['Correo' => 'Credenciales no reconocidas. Verifique su correo y su clave.']);
    }

    public function test_usuario_pendiente_no_puede_entrar(): void
    {
        $this->from('/login')
            ->post('/login', ['Correo' => 'pendiente@gobierno.gov', 'Contrasena' => self::CLAVE])
            ->assertSessionHasErrors(['Correo' => 'Su solicitud aún está pendiente de aprobación.']);

        $this->assertGuest();
    }

    public function test_despues_de_5_intentos_hay_que_esperar(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['Correo' => 'seguridad@marina.gov', 'Contrasena' => 'mala']);
        }

        // Aunque ahora la clave sea correcta, no lo deja entrar todavía.
        $this->from('/login')
            ->post('/login', ['Correo' => 'seguridad@marina.gov', 'Contrasena' => self::CLAVE])
            ->assertSessionHasErrors('Correo');

        $this->assertGuest();
        $this->assertStringContainsString('Demasiados intentos', session('errors')->first('Correo'));
    }

    public function test_cerrar_sesion(): void
    {
        $this->actingAs($this->usuario('seguridad@marina.gov'))
            ->post('/logout')
            ->assertRedirect('/login');

        $this->assertGuest();
        $this->assertTrue(Bitacora::where('Accion', Bitacora::LOGOUT)->exists());
    }

    // --------------------------------------------------------------- registro

    public function test_registro_queda_pendiente_sin_rol_y_con_clave_cifrada(): void
    {
        $this->post('/registro', [
            'Nombre' => 'Capitán Nuevo',
            'Correo' => 'nuevo@marina.gov',
            'ID_organizacion' => $this->organizacion('Marina'),
            'Contrasena' => 'ClaveSegura1',
            'Contrasena_confirmation' => 'ClaveSegura1',
        ])->assertRedirect('/pendiente');

        $nuevo = $this->usuario('nuevo@marina.gov');
        $this->assertSame(Usuario::ESTADO_PENDIENTE, $nuevo->Estado);
        $this->assertNull($nuevo->ID_rol);
        $this->assertNotSame('ClaveSegura1', $nuevo->Contrasena);
        $this->assertTrue(Hash::check('ClaveSegura1', $nuevo->Contrasena));
        $this->assertTrue(Bitacora::where('Accion', Bitacora::REGISTRO)->where('ID_usuario', $nuevo->ID_usuario)->exists());
        $this->assertGuest();
    }

    public function test_registro_valida_los_datos(): void
    {
        $this->from('/registro')->post('/registro', [
            'Nombre' => '',
            'Correo' => 'seguridad@marina.gov', // ya existe
            'ID_organizacion' => $this->organizacion('Marina'),
            'Contrasena' => 'corta',
            'Contrasena_confirmation' => 'otra',
        ])->assertSessionHasErrors(['Nombre', 'Correo', 'Contrasena']);
    }

    public function test_no_se_puede_registrar_en_una_organizacion_pirata(): void
    {
        $pirata = Organizacion::create(['Nombre' => 'Baroque Works', 'Tipo' => 'Pirata']);

        $this->from('/registro')->post('/registro', [
            'Nombre' => 'Mr. 3',
            'Correo' => 'mr3@baroque.works',
            'ID_organizacion' => $pirata->ID_organizacion,
            'Contrasena' => 'ClaveSegura1',
            'Contrasena_confirmation' => 'ClaveSegura1',
        ])->assertSessionHasErrors('ID_organizacion');

        $this->get('/registro')->assertDontSee('Baroque Works');
    }

    // ----------------------------------------------------------------- guardia

    public function test_sin_sesion_manda_al_login(): void
    {
        $this->get('/usuarios')->assertRedirect('/login');
        $this->get('/bitacora')->assertRedirect('/login');
    }

    public function test_sin_permiso_ve_403_y_el_intento_queda_anotado(): void
    {
        $director = $this->usuario('director@cipherpol.gov');

        $this->actingAs($director)->get('/usuarios')->assertForbidden()->assertSee('Acceso denegado');
        $this->actingAs($director)->get('/bitacora')->assertForbidden();

        $this->assertTrue(
            Bitacora::where('Accion', Bitacora::ACCESO_DENEGADO)->where('ID_usuario', $director->ID_usuario)->exists()
        );
    }

    public function test_el_almirante_ve_usuarios_pero_no_puede_aprobar(): void
    {
        $almirante = $this->usuario('almirante@marina.gov');
        $pendiente = $this->usuario('pendiente@gobierno.gov');

        $this->actingAs($almirante)->get('/usuarios')->assertOk();
        $this->actingAs($almirante)
            ->post("/usuarios/{$pendiente->ID_usuario}/aprobar", ['ID_rol' => $this->rol('Almirante de la Marina')])
            ->assertForbidden();

        $this->assertSame(Usuario::ESTADO_PENDIENTE, $pendiente->fresh()->Estado);
    }

    public function test_un_usuario_suspendido_con_sesion_abierta_es_expulsado(): void
    {
        $experto = $this->usuario('seguridad@marina.gov');
        $this->actingAs($experto);

        $experto->update(['Estado' => Usuario::ESTADO_SUSPENDIDO]);

        $this->get('/usuarios')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_gate_y_can_responden_segun_el_rol(): void
    {
        $director = $this->usuario('director@cipherpol.gov');

        $this->assertTrue($director->can('TRIPULACIONES.CREAR'));
        $this->assertFalse($director->can('RECOMPENSAS.EDITAR'));
        $this->assertFalse($this->usuario('pendiente@gobierno.gov')->can('TRIPULACIONES.VER'));
    }

    // ------------------------------------------------------ administración (C)

    public function test_el_experto_aprueba_y_el_aprobado_puede_entrar(): void
    {
        $experto = $this->usuario('seguridad@marina.gov');
        $pendiente = $this->usuario('pendiente@gobierno.gov');

        $this->actingAs($experto)->get('/usuarios/pendientes')->assertOk()->assertSee('Consejero Orlan Vesk');
        $this->actingAs($experto)->get("/usuarios/{$pendiente->ID_usuario}/aprobar")->assertOk();
        $this->actingAs($experto)
            ->post("/usuarios/{$pendiente->ID_usuario}/aprobar", ['ID_rol' => $this->rol('Administrador de Bases Navales')])
            ->assertRedirect('/usuarios');

        $pendiente->refresh();
        $this->assertSame(Usuario::ESTADO_ACTIVO, $pendiente->Estado);
        $this->assertTrue(Bitacora::where('Accion', Bitacora::USUARIO_APROBADO)->exists());

        $this->post('/logout');
        $this->post('/login', ['Correo' => 'pendiente@gobierno.gov', 'Contrasena' => self::CLAVE])->assertRedirect('/inicio');
    }

    public function test_rechazar_y_suspender_no_borran(): void
    {
        $experto = $this->usuario('seguridad@marina.gov');
        $pendiente = $this->usuario('pendiente@gobierno.gov');
        $director = $this->usuario('director@cipherpol.gov');

        $this->actingAs($experto)->patch("/usuarios/{$pendiente->ID_usuario}/rechazar")->assertRedirect('/usuarios');
        $this->actingAs($experto)->patch("/usuarios/{$director->ID_usuario}/suspender")->assertRedirect('/usuarios');

        $this->assertSame(Usuario::ESTADO_INACTIVO, $pendiente->fresh()->Estado);
        $this->assertSame(Usuario::ESTADO_SUSPENDIDO, $director->fresh()->Estado);
    }

    public function test_nadie_puede_suspenderse_a_si_mismo(): void
    {
        $experto = $this->usuario('seguridad@marina.gov');

        $this->actingAs($experto)
            ->patch("/usuarios/{$experto->ID_usuario}/suspender")
            ->assertSessionHas('error', 'No puede suspender su propia cuenta.');

        $this->assertSame(Usuario::ESTADO_ACTIVO, $experto->fresh()->Estado);
    }

    public function test_la_matriz_carga_y_guarda(): void
    {
        $experto = $this->usuario('seguridad@marina.gov');
        $analista = Rol::where('Nombre_rol', 'Analista de Recompensas')->first();
        $verTripulaciones = Permiso::where('Nombre_permiso', 'TRIPULACIONES.VER')->value('ID_permiso');

        $this->actingAs($experto)->get("/roles/permisos?rol={$analista->ID_rol}")
            ->assertOk()->assertSee('TRIPULACIONES')->assertSee('RECOMPENSAS');

        $this->actingAs($experto)
            ->post('/roles/permisos', ['ID_rol' => $analista->ID_rol, 'permisos' => [$verTripulaciones]])
            ->assertRedirect();

        $this->assertSame(['TRIPULACIONES.VER'], $analista->fresh()->permisos->pluck('Nombre_permiso')->all());
        $this->assertTrue(Bitacora::where('Accion', Bitacora::PERMISOS_CAMBIADOS)->exists());
    }

    public function test_no_puede_quitarle_a_su_rol_el_permiso_de_administrar(): void
    {
        $experto = $this->usuario('seguridad@marina.gov');

        $this->actingAs($experto)
            ->post('/roles/permisos', ['ID_rol' => $experto->ID_rol, 'permisos' => []])
            ->assertSessionHas('error');

        $this->assertTrue($experto->fresh()->tienePermiso('USUARIOS.EDITAR'));
    }

    public function test_la_bitacora_se_consulta_y_filtra(): void
    {
        $this->post('/login', ['Correo' => 'nadie@marina.gov', 'Contrasena' => 'x']);

        $this->actingAs($this->usuario('seguridad@marina.gov'))
            ->get('/bitacora?accion='.Bitacora::LOGIN_FALLIDO)
            ->assertOk()
            ->assertSee('nadie@marina.gov');
    }

    // ---------------------------------------------------------------- ayudas

    private function usuario(string $correo): Usuario
    {
        return Usuario::where('Correo', $correo)->firstOrFail();
    }

    private function organizacion(string $nombre): int
    {
        return Organizacion::where('Nombre', $nombre)->value('ID_organizacion');
    }

    private function rol(string $nombre): int
    {
        return Rol::where('Nombre_rol', $nombre)->value('ID_rol');
    }

    /**
     * Mismas columnas que la sección "1. SEGURIDAD, ROLES Y PERMISOS" del .sql
     * (y de organizaciones). La bitácora la crea su migración.
     */
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
            $t->string('Estado', 50)->default('Activo'); // igual que en el .sql
            $t->integer('ID_organizacion')->nullable();
        });
    }
}
