<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ambiente extends Model
{
    protected $table = 'ambiente';
    protected $primaryKey = 'id_ambiente';
    public $timestamps = false;

    protected $fillable = [
        'codigo',
        'nombre',
        'ubicacion',
        'capacidad',
        'estado',
    ];

    public function asignaciones()
    {
        return $this->hasMany(AsignacionAmbiente::class, 'id_ambiente', 'id_ambiente');
    }
}
