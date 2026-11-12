<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CodigoErrorVerificacion;
use App\Exceptions\EstudianteNoAsociadoException;
use App\Models\Habilitacion;

class PertenenciaExamenService
{
    /**
     * Verifica si un estudiante pertenece a la nómina de habilitaciones del examen indicado.
     *
     * Realiza una consulta pura de solo lectura con eager loading ('estudiante', 'examen')
     * para evitar consultas N+1 en las etapas posteriores del flujo de verificación.
     *
     * @param int $idEstudiante Identificador único del estudiante
     * @param int $idExamen Identificador único del examen
     * @return Habilitacion Instancia de la habilitación con relaciones precargadas
     *
     * @throws EstudianteNoAsociadoException Si el estudiante no pertenece a la nómina del examen
     */
    public function verificarPertenencia(int $idEstudiante, int $idExamen): Habilitacion
    {
        $habilitacion = Habilitacion::with(['estudiante', 'examen'])
            ->where('id_estudiante', $idEstudiante)
            ->where('id_examen', $idExamen)
            ->first();

        if ($habilitacion === null) {
            throw new EstudianteNoAsociadoException(errorCode: CodigoErrorVerificacion::NO_ASOCIADO);
        }

        return $habilitacion;
    }

    /**
     * Helper estático para invocaciones desacopladas o directas.
     *
     * @param int $idEstudiante Identificador único del estudiante
     * @param int $idExamen Identificador único del examen
     * @return Habilitacion
     *
     * @throws EstudianteNoAsociadoException
     */
    public static function verificar(int $idEstudiante, int $idExamen): Habilitacion
    {
        return (new self())->verificarPertenencia($idEstudiante, $idExamen);
    }
}
