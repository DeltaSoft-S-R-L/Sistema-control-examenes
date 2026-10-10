<?php

namespace App\Models;

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
    ];

    protected $hidden = [
        'password_hash',
    ];

    /**
     * Mantiene una única representación de los estados en todas las vías de
     * persistencia, incluidas las que no pasan por un controlador HTTP.
     */
    public function setEstadoAttribute(?string $value): void
    {
        $this->attributes['estado'] = $value === null
            ? null
            : strtoupper(trim($value));
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
