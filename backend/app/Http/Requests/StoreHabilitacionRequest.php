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

    public function prepareForValidation()
    {
        // Retrocompatibilidad con el frontend actual
        if ($this->has('motivo') && !$this->has('motivo_detalle')) {
            $this->merge(['motivo_detalle' => $this->input('motivo')]);
        }
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

            'motivo_tipo' => [
                'nullable',
                'string',
                Rule::in(['ACADEMICO', 'MORA', 'DOCUMENTACION', 'OTRO', 'academico', 'mora', 'documentacion', 'otro']),
            ],

            'motivo_detalle' => [
                'nullable',
                'string',
                'max:500',
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
            'motivo_tipo.in'         => 'El tipo de motivo no es válido.',
            'motivo_detalle.max'     => 'El motivo no puede superar los 500 caracteres.',
        ];
    }
}