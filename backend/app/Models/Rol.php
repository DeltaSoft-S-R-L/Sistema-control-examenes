<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rol extends Model
{
    /**
     * Nombre de la tabla asociada en PostgreSQL.
     */
    protected $table = 'rol';

    /**
     * Clave primaria personalizada.
     */
    protected $primaryKey = 'id_rol';

    /**
     * Tipo de clave primaria (string para compatibilidad BigInt en JSON).
     */
    protected $keyType = 'string';

    /**
     * La clave primaria es autoincremental en PostgreSQL.
     */
    public $incrementing = true;

    /**
     * Desactivar timestamps automáticos de Eloquent (created_at, updated_at).
     */
    public $timestamps = false;

    /**
     * Atributos asignables en masa.
     */
    protected $fillable = [
        'nombre',
        'descripcion',
    ];

    /**
     * Conversiones de tipos de atributos.
     */
    protected $casts = [
        'id_rol' => 'string',
    ];

    /**
     * Relación uno a muchos con usuarios.
     */
    public function usuarios(): HasMany
    {
        return $this->hasMany(Usuario::class, 'id_rol', 'id_rol');
    }
}
