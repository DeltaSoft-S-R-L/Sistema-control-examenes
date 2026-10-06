<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asignatura extends Model
{
    protected $table = 'asignatura';
    protected $primaryKey = 'id_asignatura';
    public $timestamps = false;

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
    ];

    public function examenes()
    {
        return $this->hasMany(Examen::class, 'id_asignatura', 'id_asignatura');
    }

    public function estudiantes()
    {
        return $this->belongsToMany(
            Estudiante::class,
            'estudiante_asignatura',
            'id_asignatura',
            'id_estudiante'
        );
    }

    public function carreras()
    {
    return $this->belongsToMany(
        Carrera::class,
        'carrera_asignatura',
        'id_asignatura',
        'id_carrera'
    );
    }
}
