<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => [
                'required',
                'string',
                'max:100',
            ],

            'apellido' => [
                'required',
                'string',
                'max:100',
            ],

            'correo' => [
                'required',
                'email',
                'max:150',
                'unique:usuario,correo',
            ],

            'username' => [
                'required',
                'string',
                'max:100',
                'unique:usuario,username',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
            ],

            'id_rol' => [
                'required',
                'integer',
                'exists:rol,id_rol',
            ],

            'estado' => [
                'sometimes',
                'string',
                Rule::in(['ACTIVO', 'REVOCADO']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.string' => 'El nombre debe ser texto.',
            'nombre.max' => 'El nombre no puede superar los 100 caracteres.',

            'apellido.required' => 'El apellido es obligatorio.',
            'apellido.string' => 'El apellido debe ser texto.',
            'apellido.max' => 'El apellido no puede superar los 100 caracteres.',

            'correo.required' => 'El correo electrónico es obligatorio.',
            'correo.email' => 'El correo electrónico no tiene un formato válido.',
            'correo.unique' => 'El correo electrónico ya está registrado.',

            'username.required' => 'El nombre de usuario es obligatorio.',
            'username.unique' => 'El nombre de usuario ya está registrado.',

            'password.required' => 'La contraseña es obligatoria.',
    'password.min' => 'La contraseña debe tener al menos 8 caracteres.',

            'id_rol.required' => 'El rol es obligatorio.',
            'id_rol.exists' => 'El rol seleccionado no existe.',

            'estado.in' => 'El estado debe ser ACTIVO o REVOCADO.',
        ];
    }
}