<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntentoIngreso extends Model
{
    protected $table = 'intento_ingreso';
    protected $primaryKey = 'id_intento';
    public $timestamps = false;

    protected $fillable = ['id_estudiante', 'id_examen', 'id_asignacion_ambiente', 'id_usuario', 'fecha_hora', 'motivo'];
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
