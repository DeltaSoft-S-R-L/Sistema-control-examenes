<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ingreso extends Model
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

    protected $casts = ['fecha_hora' => 'datetime'];

    public function habilitacion()
    {
        return $this->belongsTo(Habilitacion::class, 'id_habilitacion', 'id_habilitacion');
    }

    public function asignacionAmbiente()
    {
        return $this->belongsTo(AsignacionAmbiente::class, 'id_asignacion_ambiente', 'id_asignacion_ambiente');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    public function expulsion()
    {
        return $this->hasOne(Expulsion::class, 'id_ingreso', 'id_ingreso');
    }
}
