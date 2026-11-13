<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class Ingreso extends Model
{
    protected $table = 'ingreso';
    protected $primaryKey = 'id_ingreso';
    public $timestamps = false;

    protected $fillable = [
        'id_examen',
        'id_habilitacion',
        'id_asignacion_ambiente',
        'id_usuario',
    ];

    protected $casts = [
        'fecha_hora' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Ingreso $ingreso) {
            // La fecha y hora siempre provienen del servidor.
            $ingreso->fecha_hora = now();
        });

        static::updating(function (Ingreso $ingreso) {
            // Impedir modificaciones posteriores del timestamp.
            if ($ingreso->isDirty('fecha_hora')) {
                throw new LogicException(
                    'La fecha y hora del ingreso no pueden modificarse.'
                );
            }
        });
    }

    public function examen()
    {
        return $this->belongsTo(Examen::class, 'id_examen', 'id_examen');
    }

    public function habilitacion()
    {
        return $this->belongsTo(
            Habilitacion::class,
            'id_habilitacion',
            'id_habilitacion'
        );
    }

    public function asignacionAmbiente()
    {
        return $this->belongsTo(
            AsignacionAmbiente::class,
            'id_asignacion_ambiente',
            'id_asignacion_ambiente'
        );
    }

    public function usuario()
    {
        return $this->belongsTo(
            Usuario::class,
            'id_usuario',
            'id_usuario'
        );
    }

    public function expulsion()
    {
        return $this->hasOne(
            Expulsion::class,
            'id_ingreso',
            'id_ingreso'
        );
    }
}
