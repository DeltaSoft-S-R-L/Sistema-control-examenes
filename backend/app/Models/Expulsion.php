<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expulsion extends Model
{
    protected $table = 'expulsion';
    protected $primaryKey = 'id_expulsion';
    public $timestamps = false;

    protected $fillable = ['id_ingreso', 'id_usuario', 'motivo', 'fecha_hora'];
    protected $casts = ['fecha_hora' => 'datetime'];

    public function ingreso()
    {
        return $this->belongsTo(Ingreso::class, 'id_ingreso', 'id_ingreso');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }
}
