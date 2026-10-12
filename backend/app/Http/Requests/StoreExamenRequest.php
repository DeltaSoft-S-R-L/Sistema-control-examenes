<?php

namespace App\Http\Requests;

use App\Services\TraslapeAmbienteService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreExamenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_asignatura' => [
                'required',
                'integer',
                'exists:asignatura,id_asignatura',
            ],
            'nombre' => [
                'required',
                'string',
                'max:150',
            ],
            'fecha' => [
                'required',
                'date',
                'after_or_equal:today',
            ],
            'hora_inicio' => [
                'required',
                'date_format:H:i',
            ],
            'duracion_minutos' => [
                'required',
                'integer',
                'min:15',
                'max:300', // max 5 horas
            ],
            'descripcion' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'estado' => [
                'required',
                'string',
                'in:PROGRAMADO,EN_CURSO,FINALIZADO,CANCELADO,programado,en_curso,finalizado,cancelado',
            ],
            'id_ambiente' => [
                'nullable',
                'integer',
                'exists:ambiente,id_ambiente',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id_asignatura.required' => 'Debe seleccionar una asignatura.',
            'id_asignatura.exists' => 'La asignatura seleccionada no existe.',
            'nombre.required' => 'El nombre del examen es obligatorio.',
            'fecha.required' => 'La fecha del examen es obligatoria.',
            'fecha.after_or_equal' => 'La fecha del examen no puede estar en el pasado.',
            'hora_inicio.required' => 'La hora de inicio es obligatoria.',
            'hora_inicio.date_format' => 'La hora de inicio debe tener el formato HH:MM.',
            'duracion_minutos.required' => 'La duración del examen es obligatoria.',
            'duracion_minutos.min' => 'El examen debe durar al menos 15 minutos.',
            'duracion_minutos.max' => 'El examen no puede durar más de 300 minutos (5 horas).',
            'estado.required' => 'El estado del examen es obligatorio.',
            'estado.in' => 'El estado seleccionado no es válido.',
            'id_ambiente.exists' => 'El ambiente seleccionado no existe.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $data = $validator->getData();

            if (empty($data['id_ambiente'])) {
                return;
            }

            $hayConflicto = TraslapeAmbienteService::existeConflicto(
                (int) $data['id_ambiente'],
                $data['fecha'],
                $data['hora_inicio'],
                (int) $data['duracion_minutos']
            );

            if ($hayConflicto) {
                $validator->errors()->add(
                    'id_ambiente',
                    'El ambiente seleccionado ya está ocupado por otro examen en ese horario.'
                );
            }
        });
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
