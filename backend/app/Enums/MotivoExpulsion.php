<?php

namespace App\Enums;

enum MotivoExpulsion: string
{
    case INTENTO_COPIA = 'INTENTO_COPIA';
    case SUPLANTACION_IDENTIDAD = 'SUPLANTACION_IDENTIDAD';
    case USO_DISPOSITIVO_NO_AUTORIZADO = 'USO_DISPOSITIVO_NO_AUTORIZADO';
    case CONDUCTA_INAPROPIADA = 'CONDUCTA_INAPROPIADA';
    case OTRO = 'OTRO';

    public function etiqueta(): string
    {
        return match ($this) {
            self::INTENTO_COPIA => 'Intento de copia o fraude académico',
            self::SUPLANTACION_IDENTIDAD => 'Suplantación de identidad',
            self::USO_DISPOSITIVO_NO_AUTORIZADO => 'Uso de celular o dispositivo no autorizado',
            self::CONDUCTA_INAPROPIADA => 'Conducta inapropiada o alteración del orden',
            self::OTRO => 'Otro motivo disciplinario',
        };
    }
}
