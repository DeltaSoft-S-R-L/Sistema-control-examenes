<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class AuditLog extends Model
{
    protected $table = 'audit_logs';
    protected $primaryKey = 'id_audit_log';

    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'accion',
        'entidad',
        'id_registro',
        'fecha_hora',
        'informacion',
    ];

    protected $casts = [
        'fecha_hora' => 'datetime',
        'informacion' => 'array',
    ];

    protected static function booted(): void
    {
        static::updating(function () {
            throw new LogicException(
                'Los registros de auditoría no se pueden modificar.'
            );
        });

        static::deleting(function () {
            throw new LogicException(
                'Los registros de auditoría no se pueden eliminar.'
            );
        });
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(
            Usuario::class,
            'id_usuario',
            'id_usuario'
        );
    }

    public function getMetodoAttribute(): ?string
    {
        return $this->informacion['metodo'] ?? null;
    }

    public function getExamenIdAttribute(): ?int
    {
        return isset($this->informacion['examen_id']) ? (int) $this->informacion['examen_id'] : null;
    }

    public function getEstudianteIdAttribute(): ?int
    {
        return isset($this->informacion['estudiante_id']) ? (int) $this->informacion['estudiante_id'] : null;
    }

    public function getResultadoAttribute(): ?string
    {
        return $this->informacion['resultado'] ?? null;
    }

    public function getValorEnmascaradoAttribute(): ?string
    {
        return $this->informacion['valor_enmascarado'] ?? null;
    }
}