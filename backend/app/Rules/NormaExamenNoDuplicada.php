<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

class NormaExamenNoDuplicada implements ValidationRule, DataAwareRule
{
    protected array $data = [];
    protected int|string|null $idExamen;
    protected int|string|null $ignoreId;

    public function __construct(int|string|null $idExamen = null, int|string|null $ignoreId = null)
    {
        $this->idExamen = $idExamen;
        $this->ignoreId = $ignoreId;
    }

    public function setData(array $data): static
    {
        $this->data = $data;
        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $examenId = $this->idExamen ?? ($this->data['id_examen'] ?? $this->data['examen_id'] ?? null);
        if (! $examenId) {
            return;
        }

        $tipo = strtoupper(trim((string) ($this->data['tipo'] ?? '')));
        $idEstudiante = $tipo === 'PARTICULAR'
            ? ($this->data['estudiante_id'] ?? $this->data['id_estudiante'] ?? null)
            : null;

        $targetText = preg_replace('/\s+/', ' ', trim((string) $value));
        $normalizedTarget = mb_strtolower($targetText, 'UTF-8');

        $query = DB::table('norma_examen')
            ->where('id_examen', $examenId);

        if ($this->ignoreId) {
            $query->where('id_norma_examen', '!=', $this->ignoreId);
        }

        if ($tipo === 'PARTICULAR' && $idEstudiante) {
            $query->where('id_estudiante', $idEstudiante);
        } else {
            $query->whereNull('id_estudiante');
        }

        $existentes = $query->pluck('descripcion');

        foreach ($existentes as $desc) {
            $normalizedExistente = mb_strtolower(preg_replace('/\s+/', ' ', trim((string) $desc)), 'UTF-8');
            if ($normalizedTarget === $normalizedExistente) {
                $fail('Ya existe una norma idéntica registrada para este examen.');
                return;
            }
        }
    }
}
