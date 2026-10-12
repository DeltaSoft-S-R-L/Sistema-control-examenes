<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHabilitacionMasivaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_examen' => [
                'required',
                'integer',
                'exists:examen,id_examen',
            ],

            'estudiantes' => [
                'required',
                'array',
                'min:1',
                'max:500',
            ],

            'estudiantes.*' => [
                'required',
                'integer',
                'distinct',
                'exists:estudiante,id_estudiante',
                Rule::unique('habilitacion', 'id_estudiante')
                    ->where(fn ($query) => $query->where(
                        'id_examen',
                        $this->input('id_examen')
                    )),
            ],

            'estado' => [
                'sometimes',
                'required',
                'string',
                Rule::in([
                    'HABILITADO',
                    'NO_HABILITADO',
                    'habilitado',
                    'no_habilitado',
                ]),
            ],

            'motivo' => [
                'nullable',
                'string',
                'max:500',
                'required_if:estado,NO_HABILITADO,no_habilitado',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id_examen.required' => 'El examen es obligatorio.',
            'id_examen.exists' => 'El examen seleccionado no existe.',

            'estudiantes.required' => 'Debe seleccionar estudiantes.',
            'estudiantes.array' => 'Los estudiantes deben enviarse como una lista.',
            'estudiantes.min' => 'Debe seleccionar al menos un estudiante.',
            'estudiantes.max' => 'No se permiten más de 500 estudiantes por solicitud.',

            'estudiantes.*.integer' => 'Cada identificador debe ser un número entero.',
            'estudiantes.*.distinct' => 'La lista contiene estudiantes repetidos.',
            'estudiantes.*.exists' => 'Uno de los estudiantes seleccionados no existe.',
            'estudiantes.*.unique' => 'Uno de los estudiantes ya está asignado a este examen.',

            'estado.in' => 'El estado debe ser HABILITADO o NO_HABILITADO.',
            'motivo.required_if' => 'El motivo es obligatorio cuando el estado es NO_HABILITADO.',
            'motivo.max' => 'El motivo no puede superar los 500 caracteres.',
        ];
    }
}
