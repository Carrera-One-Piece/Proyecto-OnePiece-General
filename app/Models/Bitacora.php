<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

/**
 * Bitácora de accesos (RNF08 — Trazabilidad).
 *
 * Guarda quién entró, quién falló la contraseña, quién cerró sesión, quién
 * se registró, a quién se le negó una pantalla y qué cambió un administrador.
 */
class Bitacora extends Model
{
    public const LOGIN_OK = 'LOGIN_OK';
    public const LOGIN_FALLIDO = 'LOGIN_FALLIDO';
    public const LOGOUT = 'LOGOUT';
    public const REGISTRO = 'REGISTRO';
    public const ACCESO_DENEGADO = 'ACCESO_DENEGADO';
    public const USUARIO_APROBADO = 'USUARIO_APROBADO';
    public const USUARIO_RECHAZADO = 'USUARIO_RECHAZADO';
    public const USUARIO_SUSPENDIDO = 'USUARIO_SUSPENDIDO';
    public const PERMISOS_CAMBIADOS = 'PERMISOS_CAMBIADOS';

    // Cómo se muestra cada acción en la pantalla de la bitácora, y su color
    // (ok = verde, alerta = rojo, cambio = color del tema, neutro = gris).
    // Las RECOMPENSA_* las escribe AuditoriaRecompensa::anotarEnBitacora().
    public const ETIQUETAS = [
        self::LOGIN_OK => ['Inicio de sesión', 'ok'],
        self::LOGIN_FALLIDO => ['Intento fallido', 'alerta'],
        self::LOGOUT => ['Cierre de sesión', 'neutro'],
        self::REGISTRO => ['Registro nuevo', 'neutro'],
        self::ACCESO_DENEGADO => ['Acceso denegado', 'alerta'],
        self::USUARIO_APROBADO => ['Usuario aprobado', 'cambio'],
        self::USUARIO_RECHAZADO => ['Usuario rechazado', 'cambio'],
        self::USUARIO_SUSPENDIDO => ['Usuario suspendido', 'alerta'],
        self::PERMISOS_CAMBIADOS => ['Permisos cambiados', 'cambio'],
        AuditoriaRecompensa::BITACORA_CREADA => ['Recompensa emitida', 'cambio'],
        AuditoriaRecompensa::BITACORA_AUMENTADA => ['Recompensa aumentada', 'cambio'],
        AuditoriaRecompensa::BITACORA_EDITADA => ['Recompensa corregida', 'cambio'],
        AuditoriaRecompensa::BITACORA_CANCELADA => ['Recompensa cancelada', 'cambio'],
        AuditoriaRecompensa::BITACORA_ELIMINADA => ['Recompensa borrada', 'alerta'],
    ];

    protected $table = 'bitacora_accesos';

    protected $primaryKey = 'ID_bitacora';

    public $timestamps = false;

    protected $fillable = [
        'ID_usuario',
        'Correo_intento',
        'Accion',
        'IP',
        'Detalle',
        'Fecha',
    ];

    protected function casts(): array
    {
        return [
            'Fecha' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'ID_usuario', 'ID_usuario');
    }

    // En la vista: {{ $registro->etiqueta() }} y class="estado--{{ $registro->color() }}"
    public function etiqueta(): string
    {
        return self::ETIQUETAS[$this->Accion][0] ?? $this->Accion;
    }

    public function color(): string
    {
        return self::ETIQUETAS[$this->Accion][1] ?? 'neutro';
    }

    /**
     * Guarda una línea en la bitácora.
     *
     * Si la tabla todavía no existe en tu base local, no rompe el login:
     * deja un aviso en storage/logs/laravel.log. Ver docs/login-y-permisos.md.
     */
    public static function registrar(
        string $accion,
        ?int $idUsuario = null,
        ?string $correo = null,
        ?string $detalle = null
    ): void {
        try {
            static::create([
                'ID_usuario' => $idUsuario,
                'Correo_intento' => $correo,
                'Accion' => $accion,
                'IP' => request()->ip(),
                'Detalle' => $detalle !== null ? mb_substr($detalle, 0, 255) : null,
                'Fecha' => now(),
            ]);
        } catch (QueryException $e) {
            Log::warning('No se pudo guardar en la bitácora: '.$e->getMessage());
        }
    }
}
