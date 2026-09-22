<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReglaIndividual extends Model
{
    protected $table = 'regla_individual';
    protected $primaryKey = 'id_regla_individual';
    public $timestamps = false;

    protected $fillable = ['id_habilitacion', 'descripcion', 'estado'];

    public function habilitacion()
    {
        return $this->belongsTo(Habilitacion::class, 'id_habilitacion', 'id_habilitacion');
    }
}
