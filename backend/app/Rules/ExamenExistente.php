<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

class ExamenExistente implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value) || ! DB::table('examen')->where('id_examen', $value)->exists()) {
            $fail('El examen seleccionado no existe.');
        }
    }
}
