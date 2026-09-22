<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Habilitacion extends Model
{
    protected $table = 'habilitacion';
    protected $primaryKey = 'id_habilitacion';
    public $timestamps = false;

    protected $fillable = ['id_estudiante', 'id_examen', 'estado', 'motivo'];

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'id_estudiante', 'id_estudiante');
    }

    public function examen()
    {
        return $this->belongsTo(Examen::class, 'id_examen', 'id_examen');
    }

    public function ingreso()
    {
        return $this->hasOne(Ingreso::class, 'id_habilitacion', 'id_habilitacion');
    }

    public function reglasIndividuales()
    {
        return $this->hasMany(ReglaIndividual::class, 'id_habilitacion', 'id_habilitacion');
    }
}
