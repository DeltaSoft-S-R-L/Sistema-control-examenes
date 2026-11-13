<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstudianteFoto extends Model
{
    protected $table = 'estudiante_foto';

    protected $primaryKey = 'id_foto';

    protected $fillable = [
        'id_estudiante',
        'cloudinary_public_id',
        'cloudinary_type',
        'estado_validacion',
        'motivo_rechazo',
        'fecha_subida',
        'fecha_validacion',
    ];

    protected $casts = [
        'fecha_subida' => 'datetime',
        'fecha_validacion' => 'datetime',
    ];

    /**
     * Estudiante al que pertenece la fotografía.
     */
    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(
            Estudiante::class,
            'id_estudiante',
            'id_estudiante'
        );
    }
}
