<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Examen extends Model
{
    use SoftDeletes;

    protected $table = 'examen';
    protected $primaryKey = 'id_examen';
    public $timestamps = false;

    const DELETED_AT = 'deleted_at';

    protected $fillable = [
        'id_asignatura',
        'nombre',
        'fecha',
        'hora_inicio',
        'duracion_minutos',
        'descripcion',
        'estado',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function asignatura()
    {
        return $this->belongsTo(Asignatura::class, 'id_asignatura', 'id_asignatura');
    }

    public function asignacionesAmbiente()
    {
        return $this->hasMany(AsignacionAmbiente::class, 'id_examen', 'id_examen');
    }

    public function habilitaciones()
    {
        return $this->hasMany(Habilitacion::class, 'id_examen', 'id_examen');
    }

    public function incidencias()
    {
        return $this->hasMany(Incidencia::class, 'id_examen', 'id_examen');
    }

    public function reglas()
    {
        return $this->hasMany(ReglaExamen::class, 'id_examen', 'id_examen');
    }

    public function normas()
    {
        return $this->hasMany(NormaExamen::class, 'id_examen', 'id_examen');
    }
}
