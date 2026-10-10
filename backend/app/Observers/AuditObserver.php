<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditObserver
{
    public function created(Model $model): void
    {
        $this->registrar($model, 'CREADO', [
            'atributos' => $this->datosSeguros($model->getAttributes()),
        ]);
    }

    public function updated(Model $model): void
    {
        $this->registrar($model, 'ACTUALIZADO', [
            'cambios' => $this->datosSeguros($model->getChanges()),
        ]);
    }

    public function deleted(Model $model): void
    {
        $this->registrar($model, 'ELIMINADO', [
            'id_registro' => $model->getKey(),
        ]);
    }

    private function registrar(
        Model $model,
        string $accion,
        array $informacion
    ): void {
        AuditLog::create([
            'id_usuario' => Auth::id(),
            'accion' => $accion,
            'entidad' => $model->getTable(),
            'id_registro' => (string) $model->getKey(),
            'fecha_hora' => now(),
            'informacion' => [
                'modelo' => get_class($model),
                ...$informacion,
            ],
        ]);
    }

    private function datosSeguros(array $datos): array
    {
        foreach (array_keys($datos) as $campo) {
            if (preg_match('/password|token|secret/i', $campo)) {
                unset($datos[$campo]);
            }
        }

        return $datos;
    }
}