<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class EstudianteFotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $usuario = $this->user();

        return $usuario !== null
            && $usuario->rol?->nombre === 'ESTUDIANTE';
    }

    public function rules(): array
    {
        return [
            'foto' => [
                'required',
                File::image()
                    ->types(['jpg', 'jpeg', 'png'])
                    ->max('5mb')
                    ->dimensions(
                        \Illuminate\Validation\Rule::dimensions()
                            ->minWidth(400)
                            ->minHeight(400)
                            ->maxWidth(4000)
                            ->maxHeight(4000)
                    ),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'foto.required' => 'Debes seleccionar una fotografía.',
        ];
    }
}
