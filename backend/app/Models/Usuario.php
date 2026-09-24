<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens, Notifiable;

    /**
     * Nombre de la tabla asociada en PostgreSQL.
     */
    protected $table = 'usuario';

    /**
     * Clave primaria personalizada.
     */
    protected $primaryKey = 'id_usuario';

    /**
     * Tipo de clave primaria (string para evitar pérdida de precisión de 64 bits en JavaScript).
     */
    protected $keyType = 'string';

    /**
     * La clave primaria es autoincremental en PostgreSQL.
     */
    public $incrementing = true;

    /**
     * Desactivar timestamps automáticos de Eloquent.
     */
    public $timestamps = false;

    /**
     * Atributos asignables en masa.
     */
    protected $fillable = [
        'nombre',
        'apellido',
        'correo',
        'username',
        'password_hash',
        'id_rol',
        'estado',
    ];

    /**
     * Atributos ocultos para la serialización JSON.
     */
    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    /**
     * Conversiones de tipos nativos.
     */
    protected $casts = [
        'id_usuario' => 'string',
        'id_rol' => 'string',
    ];

    /**
     * Obtener el hash de la contraseña para el sistema de autenticación de Laravel.
     */
    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    /**
     * Relación muchos a uno con el modelo Rol.
     */
    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'id_rol', 'id_rol');
    }
}
