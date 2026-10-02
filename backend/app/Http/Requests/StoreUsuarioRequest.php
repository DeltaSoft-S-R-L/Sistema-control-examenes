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
                'min:2',
                'max:100',
            ],

            'apellido' => [
                'required',
                'string',
                'min:2',
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
                'min:3',
                'max:50',
                'regex:/^[a-zA-Z0-9_]+$/',
                'unique:usuario,username',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/[a-zA-Z]/', // al menos una letra
                'regex:/[0-9]/',    // al menos un número
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
            'nombre.required'  => 'El nombre es obligatorio.',
            'nombre.string'    => 'El nombre debe ser texto.',
            'nombre.min'       => 'El nombre debe tener al menos 2 caracteres.',
            'nombre.max'       => 'El nombre no puede superar los 100 caracteres.',

            'apellido.required' => 'El apellido es obligatorio.',
            'apellido.string'   => 'El apellido debe ser texto.',
            'apellido.min'      => 'El apellido debe tener al menos 2 caracteres.',
            'apellido.max'      => 'El apellido no puede superar los 100 caracteres.',

            'correo.required' => 'El correo electrónico es obligatorio.',
            'correo.email'    => 'El correo electrónico no tiene un formato válido.',
            'correo.max'      => 'El correo electrónico no puede superar los 150 caracteres.',
            'correo.unique'   => 'El correo electrónico ya está registrado.',

            'username.required' => 'El nombre de usuario es obligatorio.',
            'username.min'      => 'El nombre de usuario debe tener al menos 3 caracteres.',
            'username.max'      => 'El nombre de usuario no puede superar los 50 caracteres.',
            'username.regex'    => 'El nombre de usuario solo puede contener letras, números y guiones bajos.',
            'username.unique'   => 'El nombre de usuario ya está registrado.',

            'password.required' => 'La contraseña es obligatoria.',
            'password.min'      => 'La contraseña debe tener al menos 8 caracteres.',
            'password.regex'    => 'La contraseña debe contener al menos una letra y un número.',

            'id_rol.required' => 'El rol es obligatorio.',
            'id_rol.integer'  => 'El rol debe ser un identificador numérico.',
            'id_rol.exists'   => 'El rol seleccionado no existe.',

            'estado.in' => 'El estado debe ser ACTIVO o REVOCADO.',
        ];
    }
}