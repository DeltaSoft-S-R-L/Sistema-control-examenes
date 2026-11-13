<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class IngresoExamen extends Model
{
    protected $table = 'ingreso';
    protected $primaryKey = 'id_ingreso';
    public $timestamps = false;

    protected $fillable = [
        'id_examen',
        'id_habilitacion',
        'id_asignacion_ambiente',
        'id_usuario',
        'fecha_hora',
    ];

    protected $casts = [
        'fecha_hora' => 'datetime',
    ];

    public function examen(): BelongsTo
    {
        return $this->belongsTo(Examen::class, 'id_examen', 'id_examen');
    }

    public function habilitacion(): BelongsTo
    {
        return $this->belongsTo(Habilitacion::class, 'id_habilitacion', 'id_habilitacion');
    }

    public function estudiante(): HasOneThrough
    {
        return $this->hasOneThrough(
            Estudiante::class,
            Habilitacion::class,
            'id_habilitacion',
            'id_estudiante',
            'id_habilitacion',
            'id_estudiante'
        );
    }

    public function asignacionAmbiente(): BelongsTo
    {
        return $this->belongsTo(AsignacionAmbiente::class, 'id_asignacion_ambiente', 'id_asignacion_ambiente');
    }

    public function ambiente(): HasOneThrough
    {
        return $this->hasOneThrough(
            Ambiente::class,
            AsignacionAmbiente::class,
            'id_asignacion_ambiente',
            'id_ambiente',
            'id_asignacion_ambiente',
            'id_ambiente'
        );
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    public function expulsion(): HasOne
    {
        return $this->hasOne(Expulsion::class, 'id_ingreso', 'id_ingreso');
    }
}

