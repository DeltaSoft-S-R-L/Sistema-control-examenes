<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Carrera extends Model
{
    protected $table = 'carrera';
    protected $primaryKey = 'id_carrera';
    public $timestamps = false;

    protected $fillable = [
        'id_facultad',
        'codigo',
        'nombre',
    ];

    public function facultad()
    {
        return $this->belongsTo(
            Facultad::class,
            'id_facultad',
            'id_facultad'
        );
    }

    public function asignaturas()
    {
        return $this->belongsToMany(
            Asignatura::class,
            'carrera_asignatura',
            'id_carrera',
            'id_asignatura'
        );
    }

    public function estudiantes()
    {
        return $this->hasMany(
            Estudiante::class,
            'id_carrera',
            'id_carrera'
        );
    }
}