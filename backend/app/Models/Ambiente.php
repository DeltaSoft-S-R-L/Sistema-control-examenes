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

    protected $appends = [
        'asientos_disponibles',
    ];

    /**
     * Accesor explícito para el número de asientos disponibles en el aula.
     */
    public function getAsientosDisponiblesAttribute(): int
    {
        return (int) $this->capacidad;
    }

    public function asignaciones()
    {
        return $this->hasMany(AsignacionAmbiente::class, 'id_ambiente', 'id_ambiente');
    }
}
