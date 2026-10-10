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
}