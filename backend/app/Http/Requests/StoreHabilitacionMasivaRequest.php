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

    public function prepareForValidation()
    {
        // Retrocompatibilidad: Si el frontend viejo manda 'motivo', lo movemos a 'motivo_detalle'
        if ($this->has('motivo') && !$this->has('motivo_detalle')) {
            $this->merge(['motivo_detalle' => $this->input('motivo')]);
        }
    }

    public function rules(): array
    {
        return [
            'id_examen' => ['required', 'integer', 'exists:examen,id_examen'],
            'estudiantes' => ['required', 'array', 'min:1', 'max:500'],
            'estudiantes.*' => [
                'required', 'integer', 'distinct', 'exists:estudiante,id_estudiante',
                Rule::unique('habilitacion', 'id_estudiante')
                    ->where(fn ($query) => $query->where('id_examen', $this->input('id_examen'))),
            ],
            'estado' => ['sometimes', 'required', 'string', Rule::in(['HABILITADO', 'NO_HABILITADO', 'habilitado', 'no_habilitado'])],
            'motivo_tipo' => ['nullable', 'string', Rule::in(['ACADEMICO', 'MORA', 'DOCUMENTACION', 'OTRO', 'academico', 'mora', 'documentacion', 'otro'])],
            'motivo_detalle' => ['nullable', 'string', 'max:500'],
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
            'motivo_tipo.in' => 'El tipo de motivo no es válido.',
            'motivo_detalle.max' => 'El motivo no puede superar los 500 caracteres.',
        ];
    }
}