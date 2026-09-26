<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Habilitacion extends Model
{
    /**
     * Nombre de la tabla en PostgreSQL.
     */
    protected $table = 'habilitacion';

    /**
     * Clave primaria personalizada.
     */
    protected $primaryKey = 'id_habilitacion';

    /**
     * Tipo de clave primaria.
     */
    protected $keyType = 'string';

    /**
     * Clave primaria autoincremental.
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
        'id_estudiante',
        'id_examen',
        'estado',
        'motivo',
    ];

    /**
     * Casts de tipos.
     */
    protected $casts = [
        'id_habilitacion' => 'string',
        'id_estudiante' => 'string',
        'id_examen' => 'string',
    ];

    /**
     * Relación con el estudiante.
     */
    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class, 'id_estudiante', 'id_estudiante');
    }
}
