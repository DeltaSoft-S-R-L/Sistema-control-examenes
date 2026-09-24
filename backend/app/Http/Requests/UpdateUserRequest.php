<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para realizar esta solicitud.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Preparar los datos antes de la validación.
     */
    protected function prepareForValidation(): void
    {
        // Normalizar y mapear el estado a los permitidos en PostgreSQL (ck_usuario_estado: ACTIVO, REVOCADO)
        if ($this->has('estado') && is_string($this->input('estado'))) {
            $estado = strtoupper(trim($this->input('estado')));
            if ($estado === 'INACTIVO' || $estado === 'BLOQUEADO') {
                $estado = 'REVOCADO';
            }
            $this->merge(['estado' => $estado]);
        }

        if ($this->has('rol') && is_string($this->input('rol'))) {
            $this->merge(['rol' => strtoupper(trim($this->input('rol')))]);
        }
    }

    /**
     * Comprobar que el body no venga totalmente vacío.
     */
    public function withValidator($validator): void
    {
        $validator->after(function () {
            if (empty($this->all())) {
                throw new HttpResponseException(response()->json([
                    'error' => 'Error de validación',
                    'detalles' => [
                        [
                            'campo' => '',
                            'mensaje' => 'Debe proporcionar al menos un campo para actualizar'
                        ]
                    ]
                ], 400));
            }
        });
    }

    /**
     * Reglas de validación para actualización parcial de usuario.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->route('id');

        return [
            'nombre' => ['sometimes', 'string', 'min:2', 'max:100'],
            'apellido' => ['sometimes', 'string', 'min:2', 'max:100'],
            'correo' => [
                'sometimes',
                'string',
                'email',
                'max:150',
                Rule::unique('usuario', 'correo')->ignore($userId, 'id_usuario'),
            ],
            'username' => [
                'sometimes',
                'string',
                'min:3',
                'max:50',
                'regex:/^[a-zA-Z0-9_]+$/',
                Rule::unique('usuario', 'username')->ignore($userId, 'id_usuario'),
            ],
            'rol' => ['sometimes', 'string', 'min:1'],
            'estado' => [
                'sometimes',
                'string',
                Rule::in(['ACTIVO', 'REVOCADO']),
            ],
        ];
    }

    /**
     * Mensajes personalizados de validación.
     */
    public function messages(): array
    {
        return [
            'correo.unique' => 'El correo electrónico ya está registrado por otro usuario',
            'username.unique' => 'El nombre de usuario ya está en uso',
            'username.regex' => 'El nombre de usuario solo puede contener caracteres alfanuméricos y guiones bajos',
        ];
    }
}
