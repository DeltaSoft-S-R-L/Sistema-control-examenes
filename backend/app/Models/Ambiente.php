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
     * Devuelve el número de asientos disponibles en el aula.
     * Si el ambiente se encuentra marcado como NO_DISPONIBLE, devuelve 0 asientos disponibles.
     * Si se encuentra DISPONIBLE, devuelve la capacidad de asientos asignables.
     */
    public function getAsientosDisponiblesAttribute(): int
    {
        if (strtoupper($this->estado ?? '') !== 'DISPONIBLE') {
            return 0;
        }

        return (int) $this->capacidad;
    }

    public function asignaciones()
    {
        return $this->hasMany(AsignacionAmbiente::class, 'id_ambiente', 'id_ambiente');
    }
}
