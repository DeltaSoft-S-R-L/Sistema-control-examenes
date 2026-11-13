<?php

namespace App\Enums;

/**
 * Códigos estandarizados de error retornados en los flujos de verificación e ingreso.
 */
enum CodigoErrorVerificacion: string
{
    case VALIDACION_FALLIDA = 'VALIDACION_FALLIDA';
    case QR_INVALIDO = 'QR_INVALIDO';
    case ESTUDIANTE_NO_ENCONTRADO = 'ESTUDIANTE_NO_ENCONTRADO';
    case EXAMEN_NO_ENCONTRADO = 'EXAMEN_NO_ENCONTRADO';
    case NO_ASOCIADO = 'NO_ASOCIADO';
    case NO_HABILITADO = 'NO_HABILITADO';
    case AMBIENTE_NO_CORRESPONDE = 'AMBIENTE_NO_CORRESPONDE';
    case AULA_INCORRECTA = 'AULA_INCORRECTA';
    case REINGRESO_NO_PERMITIDO = 'REINGRESO_NO_PERMITIDO';
    case DATOS_INSUFICIENTES = 'DATOS_INSUFICIENTES';
}
