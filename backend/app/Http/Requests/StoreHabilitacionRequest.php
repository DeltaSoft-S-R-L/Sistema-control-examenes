<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHabilitacionRequest extends FormRequest
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

            'id_estudiante' => [
                'required',
                'integer',
                'exists:estudiante,id_estudiante',
                Rule::unique('habilitacion', 'id_estudiante')->where(function ($query) {
                    return $query->where('id_examen', $this->input('id_examen'));
                }),
            ],

            'estado' => [
                'sometimes',
                'required',
                'string',
                Rule::in(['HABILITADO', 'NO_HABILITADO', 'habilitado', 'no_habilitado']),
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
            'id_estudiante.required' => 'El estudiante es obligatorio.',
            'id_estudiante.integer'  => 'El identificador del estudiante debe ser un número entero.',
            'id_estudiante.exists'   => 'El estudiante seleccionado no existe.',
            'id_estudiante.unique'   => 'El estudiante ya se encuentra asignado a este examen.',

            'id_examen.required'     => 'El examen es obligatorio.',
            'id_examen.integer'      => 'El identificador del examen debe ser un número entero.',
            'id_examen.exists'       => 'El examen seleccionado no existe.',

            'estado.in'              => 'El estado debe ser HABILITADO o NO_HABILITADO.',
            'motivo.required_if'     => 'El motivo es obligatorio cuando el estado es NO_HABILITADO.',
            'motivo.max'             => 'El motivo no puede superar los 500 caracteres.',
        ];
    }
}
