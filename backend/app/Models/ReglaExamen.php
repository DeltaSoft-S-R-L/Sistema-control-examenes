<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReglaExamen extends Model
{
    protected $table = 'regla_examen';
    protected $primaryKey = 'id_regla_examen';
    public $timestamps = false;

    protected $fillable = ['id_examen', 'descripcion', 'estado'];

    public function examen()
    {
        return $this->belongsTo(Examen::class, 'id_examen', 'id_examen');
    }
}
