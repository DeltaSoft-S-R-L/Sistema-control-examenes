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
}
