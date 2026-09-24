<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class RegisterUserRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para realizar esta solicitud.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validador adicional para detectar si el cuerpo de la petición vino totalmente vacío.
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
                            'mensaje' => 'El cuerpo de la petición no puede estar vacío'
                        ]
                    ]
                ], 400));
            }
        });
    }

    /**
     * Reglas de validación para el registro de usuarios.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'min:2', 'max:100'],
            'apellido' => ['required', 'string', 'min:2', 'max:100'],
            'correo' => ['required', 'string', 'email', 'max:150', 'unique:usuario,correo'],
            'username' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'regex:/^[a-zA-Z0-9_]+$/',
                'unique:usuario,username',
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'max:100',
                'regex:/[A-Za-z]/',
                'regex:/[0-9]/',
            ],
            'rol' => ['required', 'string'],
        ];
    }

    /**
     * Mensajes personalizados de validación.
     */
    public function messages(): array
    {
        return [
            'correo.unique' => 'El correo electrónico ya se encuentra registrado',
            'username.unique' => 'El nombre de usuario ya está en uso',
            'username.regex' => 'El nombre de usuario solo puede contener caracteres alfanuméricos y guiones bajos',
            'password.regex' => 'La contraseña debe contener al menos una letra y un número',
        ];
    }
}
