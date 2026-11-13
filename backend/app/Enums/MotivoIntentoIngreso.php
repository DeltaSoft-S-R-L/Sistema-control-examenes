<?php

namespace App\Enums;

/**
 * Motivos estandarizados para el registro de anomalías en la tabla intento_ingreso.
 */
enum MotivoIntentoIngreso: string
{
    case EXAMEN_NO_CORRESPONDIENTE = 'EXAMEN_NO_CORRESPONDIENTE';
    case REINGRESO_DUPLICADO = 'REINGRESO_DUPLICADO';
    case AULA_INCORRECTA = 'AULA_INCORRECTA';
    case ESTUDIANTE_NO_HABILITADO = 'ESTUDIANTE_NO_HABILITADO';
}
