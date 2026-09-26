<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Estudiante extends Model
{
    /**
     * Nombre de la tabla en PostgreSQL.
     */
    protected $table = 'estudiante';

    /**
     * Clave primaria personalizada.
     */
    protected $primaryKey = 'id_estudiante';

    /**
     * Tipo de clave primaria (string para compatibilidad BigInt en JSON y JavaScript).
     */
    protected $keyType = 'string';

    /**
     * Clave autoincremental en PostgreSQL (IDENTITY).
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
        'ci',
        'nombre',
        'apellido',
        'codigo_universitario',
        'correo',
        'estado',
    ];

    /**
     * Casts de tipos.
     */
    protected $casts = [
        'id_estudiante' => 'string',
    ];

    /**
     * Relación con habilitaciones a exámenes.
     */
    public function habilitaciones(): HasMany
    {
        return $this->hasMany(Habilitacion::class, 'id_estudiante', 'id_estudiante');
    }
}
