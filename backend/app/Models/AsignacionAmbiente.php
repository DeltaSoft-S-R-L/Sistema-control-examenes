<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsignacionAmbiente extends Model
{
    protected $table = 'asignacion_ambiente';
    protected $primaryKey = 'id_asignacion_ambiente';
    public $timestamps = false;

    protected $fillable = [
        'id_examen',
        'id_ambiente',
    ];

    public function ambiente()
    {
        return $this->belongsTo(Ambiente::class, 'id_ambiente', 'id_ambiente');
    }

    public function examen()
    {
        return $this->belongsTo(Examen::class, 'id_examen', 'id_examen');
    }

    public function ingresos()
    {
        return $this->hasMany(Ingreso::class, 'id_asignacion_ambiente', 'id_asignacion_ambiente');
    }

    public function intentos()
    {
        return $this->hasMany(IntentoIngreso::class, 'id_asignacion_ambiente', 'id_asignacion_ambiente');
    }
}
