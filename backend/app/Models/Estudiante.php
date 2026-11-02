<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Estudiante extends Model
{
    protected $table = 'estudiante';
    protected $primaryKey = 'id_estudiante';
    public $timestamps = false;

    protected $fillable = [
        'ci',
        'nombre',
        'apellido',
        'codigo_universitario',
        'id_carrera',
        'correo',
        'estado',
    ];

    /**
     * Los estados se persisten con una única representación para que, por
     * ejemplo, "activo" y "ACTIVO" no sean valores distintos en la base.
     */
    public function setEstadoAttribute(?string $value): void
    {
        $this->attributes['estado'] = $value === null
            ? null
            : strtoupper(trim($value));
    }

    /**
     * Scope para búsqueda indexada por documento (CI o SIS/código universitario) (EST-02 / S3-VER01-03).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $tipo  'CI' o 'SIS'
     * @param  string  $valor Documento normalizado
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBuscarPorDocumento($query, string $tipo, string $valor)
    {
        $tipoNorm = strtoupper(trim($tipo));
        $columna = $tipoNorm === 'SIS' ? 'codigo_universitario' : 'ci';

        return $query->where(function ($q) use ($columna, $valor) {
            $q->where($columna, $valor)
              ->orWhereRaw("UPPER({$columna}) = ?", [$valor])
              ->orWhereRaw("REPLACE(UPPER({$columna}), ' ', '') = ?", [$valor]);
        });
    }

    public function habilitaciones()
    {
        return $this->hasMany(Habilitacion::class, 'id_estudiante', 'id_estudiante');
    }

    public function incidencias()
    {
        return $this->hasMany(Incidencia::class, 'id_estudiante', 'id_estudiante');
    }

    public function intentos()
    {
        return $this->hasMany(IntentoIngreso::class, 'id_estudiante', 'id_estudiante');
    }

    public function carrera()
    {
        return $this->belongsTo(Carrera::class, 'id_carrera', 'id_carrera');
    }

    public function asignaturas()
    {
        return $this->belongsToMany(
            Asignatura::class,
            'estudiante_asignatura',
            'id_estudiante',
            'id_asignatura'
        );
    }
}
