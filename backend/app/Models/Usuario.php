<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'usuario';
    protected $primaryKey = 'id_usuario';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'apellido',
        'correo',
        'username',
        'password_hash',
        'id_rol',
        'estado',
        'is_active',
    ];

    protected $appends = [
        'is_active',
    ];

    protected $hidden = [
        'password_hash',
    ];

    /**
     * Accessor y Mutator para el atributo 'is_active' (USR-03).
     * Mapea boolean <-> 'ACTIVO'/'REVOCADO' en la columna 'estado'.
     */
    protected function isActive(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => strtoupper((string) ($attributes['estado'] ?? '')) === 'ACTIVO',
            set: fn (mixed $value) => [
                'estado' => filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'ACTIVO' : 'REVOCADO',
            ],
        );
    }

    // Necesario para que Sanctum use 'password_hash' como campo de contraseña
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function rol()
    {
        return $this->belongsTo(Rol::class, 'id_rol', 'id_rol');
    }
    public function tienePermiso(string $permiso): bool
    {
    $this->loadMissing('rol.permisos');

    return $this->rol?->permisos
        ->contains('nombre', $permiso) ?? false;
    }
    public function auditorias()
    {
        return $this->hasMany(Auditoria::class, 'id_usuario', 'id_usuario');
    }

    public function incidencias()
    {
        return $this->hasMany(Incidencia::class, 'id_usuario', 'id_usuario');
    }

    public function ingresos()
    {
        return $this->hasMany(Ingreso::class, 'id_usuario', 'id_usuario');
    }
}
