<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNormaExamenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->has('tipo') && is_string($this->tipo)) {
            $merge['tipo'] = strtoupper(trim($this->tipo));
        }

        if ($this->has('id_estudiante') && ! $this->has('estudiante_id')) {
            $merge['estudiante_id'] = $this->id_estudiante;
        }

        if (! empty($merge)) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        return [
            'tipo' => [
                'required',
                'string',
                'in:GENERAL,PARTICULAR',
            ],
            'descripcion' => [
                'required',
                'string',
                'max:5000',
            ],
            'estudiante_id' => [
                'nullable',
                'integer',
                'exists:estudiante,id_estudiante',
                'required_if:tipo,PARTICULAR',
                'prohibited_if:tipo,GENERAL',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'tipo.required' => 'El tipo de norma es obligatorio.',
            'tipo.in' => 'El tipo de norma debe ser GENERAL o PARTICULAR.',
            'descripcion.required' => 'La descripción de la norma es obligatoria.',
            'estudiante_id.required_if' => 'El estudiante es obligatorio cuando la norma es de tipo PARTICULAR.',
            'estudiante_id.prohibited_if' => 'No debe especificarse un estudiante cuando la norma es de tipo GENERAL.',
            'estudiante_id.exists' => 'El estudiante seleccionado no existe en el sistema.',
        ];
    }
}
