<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Incidencia extends Model
{
    protected $table = 'incidencia';
    protected $primaryKey = 'id_incidencia';
    public $timestamps = false;

    protected $fillable = ['id_estudiante', 'id_examen', 'id_usuario', 'tipo', 'descripcion', 'fecha_hora'];
    protected $casts = ['fecha_hora' => 'datetime'];

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'id_estudiante', 'id_estudiante');
    }

    public function examen()
    {
        return $this->belongsTo(Examen::class, 'id_examen', 'id_examen');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }
}
