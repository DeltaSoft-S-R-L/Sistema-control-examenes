<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExamenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_asignatura'    => [
                'sometimes',
                'required',
                'integer',
                'exists:asignatura,id_asignatura',
            ],
            'nombre'           => [
                'sometimes',
                'required',
                'string',
                'max:150',
            ],
            'fecha'            => [
                'sometimes',
                'required',
                'date',
                // Removido 'after_or_equal:today' ya que se podría actualizar un examen pasado.
            ],
            'hora_inicio'      => [
                'sometimes',
                'required',
                'date_format:H:i',
            ],
            'duracion_minutos' => [
                'sometimes',
                'required',
                'integer',
                'min:15',
                'max:300',
            ],
            'descripcion'      => [
                'nullable',
                'string',
                'max:1000',
            ],
            'estado'           => [
                'sometimes',
                'required',
                'string',
                'in:PROGRAMADO,EN_CURSO,FINALIZADO,CANCELADO,programado,en_curso,finalizado,cancelado',
            ],
            'id_ambiente'      => [
                'nullable',
                'integer',
                'exists:ambiente,id_ambiente',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id_asignatura.required'     => 'Debe seleccionar una asignatura.',
            'id_asignatura.exists'       => 'La asignatura seleccionada no existe.',
            'nombre.required'            => 'El nombre del examen es obligatorio.',
            'fecha.required'             => 'La fecha del examen es obligatoria.',
            'hora_inicio.required'       => 'La hora de inicio es obligatoria.',
            'hora_inicio.date_format'    => 'La hora de inicio debe tener el formato HH:MM.',
            'duracion_minutos.required'  => 'La duración del examen es obligatoria.',
            'duracion_minutos.min'       => 'El examen debe durar al menos 15 minutos.',
            'duracion_minutos.max'       => 'El examen no puede durar más de 300 minutos (5 horas).',
            'estado.required'            => 'El estado del examen es obligatorio.',
            'estado.in'                  => 'El estado seleccionado no es válido.',
            'id_ambiente.exists'         => 'El ambiente seleccionado no existe.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('estado')) {
            $this->merge([
                'estado' => strtoupper($this->estado),
            ]);
        }
    }
}
