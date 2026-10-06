<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Estudiante extends Model
{
    protected $table = 'estudiante';
    protected $primaryKey = 'id_estudiante';
    public $timestamps = false;

    protected $fillable = [
        'ci',
        'nombre',
        'apellido',
        'codigo_universitario',
        'correo',
        'estado',
    ];

    public function habilitaciones()
    {
        return $this->hasMany(Habilitacion::class, 'id_estudiante', 'id_estudiante');
    }

    public function incidencias()
    {
        return $this->hasMany(Incidencia::class, 'id_estudiante', 'id_estudiante');
    }

    public function intentos()
    {
        return $this->hasMany(IntentoIngreso::class, 'id_estudiante', 'id_estudiante');
    }

    public function examenes()
    {
        return $this->belongsToMany(Examen::class, 'habilitacion', 'id_estudiante', 'id_examen')
                    ->withPivot('id_habilitacion', 'estado', 'motivo');
    }
}
