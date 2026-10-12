<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Facultad extends Model
{
    protected $table = 'facultad';
    protected $primaryKey = 'id_facultad';
    public $timestamps = false;

    protected $fillable = [
        'codigo',
        'nombre',
    ];

    public function carreras()
    {
        return $this->hasMany(
            Carrera::class,
            'id_facultad',
            'id_facultad'
        );
    }
}