<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NormaExamen extends Model
{
    protected $table = 'norma_examen';
    protected $primaryKey = 'id_norma_examen';

    protected $fillable = [
        'id_examen',
        'id_estudiante',
        'tipo',
        'descripcion',
        'created_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function setTipoAttribute(?string $value): void
    {
        $this->attributes['tipo'] = $value === null
            ? null
            : strtoupper(trim($value));
    }

    public function examen()
    {
        return $this->belongsTo(Examen::class, 'id_examen', 'id_examen');
    }

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'id_estudiante', 'id_estudiante');
    }

    public function creador()
    {
        return $this->belongsTo(Usuario::class, 'created_by', 'id_usuario');
    }

    public function scopeGenerales($query)
    {
        return $query->where('tipo', 'GENERAL');
    }

    public function scopeParticulares($query)
    {
        return $query->where('tipo', 'PARTICULAR');
    }

    public function scopeDeExamen($query, int $idExamen)
    {
        return $query->where('id_examen', $idExamen);
    }
}
