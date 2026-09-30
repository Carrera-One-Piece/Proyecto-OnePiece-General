<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Una persona que inicia sesión (Capa 1): un funcionario de la Marina,
 * Cipher Pol o un Gobierno Afiliado. Los piratas NO son usuarios, son
 * datos de la Capa 2.
 *
 * La tabla `usuarios` viene del .sql del equipo y no usa los nombres que
 * Laravel espera (`users`, `email`, `password`...). Por eso aquí se le dice
 * a Laravel cómo se llama cada cosa.
 */
class Usuario extends Authenticatable
{
    public const ESTADO_PENDIENTE = 'Pendiente';
    public const ESTADO_ACTIVO = 'Activo';
    public const ESTADO_SUSPENDIDO = 'Suspendido';
    public const ESTADO_INACTIVO = 'Inactivo';

    protected $table = 'usuarios';

    protected $primaryKey = 'ID_usuario';

    // La tabla no tiene created_at ni updated_at.
    public $timestamps = false;

    // Laravel busca la contraseña en la columna `password`; la nuestra es `Contrasena`.
    protected $authPasswordName = 'Contrasena';

    // No hay columna para "recordarme", así que se apaga.
    protected $rememberTokenName = '';

    protected $fillable = [
        'Nombre',
        'Correo',
        'Contrasena',
        'ID_rol',
        'Estado',
        'ID_organizacion',
    ];

    // Nunca mandar el hash de la contraseña a una vista o a un JSON.
    protected $hidden = [
        'Contrasena',
    ];

    protected function casts(): array
    {
        return [
            // Si alguien guarda la contraseña en texto plano, Laravel la cifra igual.
            'Contrasena' => 'hashed',
        ];
    }

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'ID_rol', 'ID_rol');
    }

    public function organizacion(): BelongsTo
    {
        return $this->belongsTo(Organizacion::class, 'ID_organizacion', 'ID_organizacion');
    }

    public function estaActivo(): bool
    {
        return $this->Estado === self::ESTADO_ACTIVO;
    }

    /**
     * ¿Este usuario puede hacer esto? Ejemplo: tienePermiso('TRIPULACIONES.CREAR').
     *
     * Un usuario que no está Activo, o que no tiene rol, no puede nada.
     * Los permisos del rol se leen una sola vez por petición.
     */
    public function tienePermiso(string $permiso): bool
    {
        if (! $this->estaActivo() || ! $this->rol) {
            return false;
        }

        return $this->rol->permisos->contains('Nombre_permiso', $permiso);
    }
}
