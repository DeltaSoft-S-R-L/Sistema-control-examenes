<?php

namespace App\Services;

use App\Models\Examen;
use Carbon\Carbon;

class TraslapeAmbienteService
{
    public static function existeConflicto(
        int $idAmbiente,
        string $fecha,
        string $horaInicio,
        int $duracionMinutos,
        ?int $excluirExamenId = null
    ): bool {
        $inicioNuevo = Carbon::parse("$fecha $horaInicio");
        $finNuevo = $inicioNuevo->copy()->addMinutes($duracionMinutos);

        return Examen::query()
            ->whereDate('fecha', '>=', $inicioNuevo->copy()->subDay()->toDateString())
            ->whereDate('fecha', '<=', $finNuevo->toDateString())
            ->whereHas('asignacionesAmbiente', function ($query) use ($idAmbiente) {
                $query->where('id_ambiente', $idAmbiente);
            })
            ->when($excluirExamenId !== null, function ($query) use ($excluirExamenId) {
                $query->where('id_examen', '!=', $excluirExamenId);
            })
            ->get()
            ->contains(function ($examen) use ($inicioNuevo, $finNuevo) {
                $inicioExistente = Carbon::parse(
                    $examen->fecha->format('Y-m-d').' '.$examen->hora_inicio
                );

                $finExistente = $inicioExistente->copy()
                    ->addMinutes($examen->duracion_minutos);

                return $inicioNuevo->lt($finExistente)
                    && $finNuevo->gt($inicioExistente);
            });
    }
}
