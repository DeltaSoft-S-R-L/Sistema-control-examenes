<?php

namespace App\Http\Requests;

use App\Models\Examen;
use App\Rules\EstudianteHabilitadoEnExamen;
use App\Rules\ExamenExistente;
use App\Rules\NormaExamenNoDuplicada;
use Illuminate\Foundation\Http\FormRequest;

class StoreNormaExamenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function getExamenId(): int|string|null
    {
        $examen = $this->route('examen') ?? $this->route('id');

        if ($examen instanceof Examen) {
            return $examen->id_examen;
        }

        if (is_numeric($examen)) {
            return (int) $examen;
        }

        return $this->input('id_examen', $this->input('examen_id'));
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->has('tipo') && is_string($this->tipo)) {
            $merge['tipo'] = strtoupper(trim($this->tipo));
        }

        if ($this->has('descripcion') && is_string($this->descripcion)) {
            $merge['descripcion'] = trim($this->descripcion);
        }

        if ($this->has('id_habilitacion') && ! $this->has('estudiante_id') && ! $this->has('id_estudiante')) {
            $hab = \Illuminate\Support\Facades\DB::table('habilitacion')->where('id_habilitacion', $this->id_habilitacion)->first();
            if ($hab) {
                $merge['estudiante_id'] = $hab->id_estudiante;
                $merge['id_estudiante'] = $hab->id_estudiante;
            }
        }

        if ($this->has('id_estudiante') && ! $this->has('estudiante_id')) {
            $merge['estudiante_id'] = $this->id_estudiante;
        }

        if ($this->has('estudiante_id') && ! $this->has('id_estudiante')) {
            $merge['id_estudiante'] = $this->estudiante_id;
        }

        if ($this->has('id_examen') && ! $this->has('examen_id')) {
            $merge['examen_id'] = $this->id_examen;
        }

        if (! empty($merge)) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        $examenId = $this->getExamenId();

        $rules = [
            'tipo' => [
                'required',
                'string',
                'in:GENERAL,PARTICULAR,general,particular',
            ],
            'descripcion' => [
                'required',
                'string',
                'max:1000',
                new NormaExamenNoDuplicada($examenId),
            ],
            'estudiante_id' => [
                'nullable',
                'integer',
                'exists:estudiante,id_estudiante',
                'required_if:tipo,PARTICULAR,particular',
                'prohibited_if:tipo,GENERAL,general',
                new EstudianteHabilitadoEnExamen($examenId),
            ],
        ];

        if ($this->has('examen_id') || $this->has('id_examen')) {
            $rules['examen_id'] = ['sometimes', 'integer', new ExamenExistente()];
            $rules['id_examen'] = ['sometimes', 'integer', new ExamenExistente()];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'tipo.required' => 'El tipo de norma es obligatorio.',
            'tipo.in' => 'El tipo de norma debe ser General o Particular.',
            'descripcion.required' => 'La descripción de la norma es obligatoria.',
            'descripcion.string' => 'La descripción debe ser una cadena de texto.',
            'descripcion.max' => 'La descripción no puede superar los 1000 caracteres.',
            'estudiante_id.required_if' => 'Debe seleccionar un estudiante con habilitación válida para este examen.',
            'estudiante_id.prohibited_if' => 'No debe especificarse un estudiante cuando la norma es de tipo GENERAL.',
            'estudiante_id.exists' => 'El estudiante seleccionado no existe en el sistema.',
            'estudiante_id.integer' => 'El identificador del estudiante debe ser un número entero.',
            'examen_id.exists' => 'El examen seleccionado no existe.',
            'examen_id.integer' => 'El identificador del examen debe ser un número entero.',
            'id_examen.exists' => 'El examen seleccionado no existe.',
            'id_examen.integer' => 'El identificador del examen debe ser un número entero.',
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $errors = $validator->errors();
        if ($errors->has('estudiante_id') && ! $errors->has('id_estudiante')) {
            $errors->add('id_estudiante', $errors->first('estudiante_id'));
        }
        if ($errors->has('id_estudiante') && ! $errors->has('estudiante_id')) {
            $errors->add('estudiante_id', $errors->first('id_estudiante'));
        }
        parent::failedValidation($validator);
    }
}
