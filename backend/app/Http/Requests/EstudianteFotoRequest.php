<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
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
                    ->types(['jpg', 'jpeg'])
                    ->max('5mb')
                    ->dimensions(
                        \Illuminate\Validation\Rule::dimensions()
                            ->width(709)
                            ->height(709)
                    ),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value instanceof UploadedFile && !$this->tiene600Ppp($value)) {
                        $fail('La fotografía debe tener una resolución de 600 PPP.');
                    }
                },
            ],
        ];
    }

    /** Verifica la densidad JFIF en el archivo recibido; no confía en el cliente. */
    private function tiene600Ppp(UploadedFile $foto): bool
    {
        $bytes = @file_get_contents($foto->getRealPath());
        if ($bytes === false || !str_starts_with($bytes, "\xFF\xD8")) {
            return false;
        }

        $longitudArchivo = strlen($bytes);
        for ($posicion = 2; $posicion + 4 <= $longitudArchivo;) {
            if (ord($bytes[$posicion]) !== 0xFF) {
                return false;
            }

            $marcador = ord($bytes[$posicion + 1]);
            if ($marcador === 0xDA || $marcador === 0xD9) {
                break;
            }

            $longitud = unpack('n', substr($bytes, $posicion + 2, 2))[1] ?? 0;
            if ($longitud < 2 || $posicion + 2 + $longitud > $longitudArchivo) {
                return false;
            }

            $esJfif = $marcador === 0xE0
                && $longitud >= 16
                && substr($bytes, $posicion + 4, 5) === "JFIF\x00";

            if ($esJfif) {
                $unidades = ord($bytes[$posicion + 11]);
                $pppHorizontal = unpack('n', substr($bytes, $posicion + 12, 2))[1] ?? 0;
                $pppVertical = unpack('n', substr($bytes, $posicion + 14, 2))[1] ?? 0;

                return $unidades === 1
                    && $pppHorizontal === 600
                    && $pppVertical === 600;
            }

            $posicion += 2 + $longitud;
        }

        return false;
    }

    public function messages(): array
    {
        return [
            'foto.required' => 'Debes seleccionar una fotografía.',
            'foto.dimensions' => 'La fotografía debe tener exactamente 709 × 709 píxeles (30 × 30 mm).',
            'foto.max' => 'La fotografía no debe superar los 5 MB.',
        ];
    }
}
