<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EstudianteHabilitadoEnExamen implements ValidationRule, DataAwareRule
{
    protected array $data = [];
    protected int|string|null $idExamen;

    public function __construct(int|string|null $idExamen = null)
    {
        $this->idExamen = $idExamen;
    }

    public function setData(array $data): static
    {
        $this->data = $data;
        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $tipo = strtoupper(trim((string) ($this->data['tipo'] ?? '')));
        if ($tipo !== 'PARTICULAR') {
            return;
        }

        if (empty($value)) {
            return;
        }

        $examenId = $this->idExamen ?? ($this->data['id_examen'] ?? $this->data['examen_id'] ?? null);
        if (! $examenId) {
            return;
        }

        if (! DB::table('examen')->where('id_examen', $examenId)->exists()) {
            return;
        }

        $estudianteExiste = DB::table('estudiante')->where('id_estudiante', $value)->exists();
        if (! $estudianteExiste) {
            return;
        }

        $habilitado = false;

        if (Schema::hasTable('habilitacion')) {
            $habilitado = DB::table('habilitacion')
                ->where('id_examen', $examenId)
                ->where('id_estudiante', $value)
                ->where(function ($query) {
                    $query->where('estado', 'HABILITADO')
                        ->orWhere('estado', 'habilitado');
                })
                ->exists();
        }

        if (! $habilitado && Schema::hasTable('estudiante_examen')) {
            $query = DB::table('estudiante_examen');

            if (Schema::hasColumn('estudiante_examen', 'id_examen')) {
                $query->where('id_examen', $examenId);
            } elseif (Schema::hasColumn('estudiante_examen', 'examen_id')) {
                $query->where('examen_id', $examenId);
            }

            if (Schema::hasColumn('estudiante_examen', 'id_estudiante')) {
                $query->where('id_estudiante', $value);
            } elseif (Schema::hasColumn('estudiante_examen', 'estudiante_id')) {
                $query->where('estudiante_id', $value);
            }

            if (Schema::hasColumn('estudiante_examen', 'habilitado')) {
                $query->where('habilitado', true);
            } elseif (Schema::hasColumn('estudiante_examen', 'estado')) {
                $query->whereIn('estado', ['HABILITADO', 'habilitado']);
            }

            $habilitado = $query->exists();
        }

        if (! $habilitado) {
            $fail('El estudiante no está habilitado en este examen.');
        }
    }
}
