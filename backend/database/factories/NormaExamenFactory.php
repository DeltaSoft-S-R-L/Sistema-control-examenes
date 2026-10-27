<?php

namespace Database\Factories;

use App\Models\Estudiante;
use App\Models\Examen;
use App\Models\NormaExamen;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

class NormaExamenFactory extends Factory
{
    protected $model = NormaExamen::class;

    public function definition(): array
    {
        return [
            'id_examen'    => Examen::query()->inRandomOrder()->value('id_examen') ?? 1,
            'id_estudiante'=> null,
            'tipo'         => 'GENERAL',
            'descripcion'  => fake()->sentence(12),
            'created_by'   => Usuario::query()->inRandomOrder()->value('id_usuario') ?? 1,
        ];
    }

    public function general(): static
    {
        return $this->state(fn () => [
            'tipo'          => 'GENERAL',
            'id_estudiante' => null,
        ]);
    }

    public function particular(?int $idEstudiante = null): static
    {
        return $this->state(fn () => [
            'tipo'          => 'PARTICULAR',
            'id_estudiante' => $idEstudiante
                ?? Estudiante::query()->inRandomOrder()->value('id_estudiante')
                ?? 1,
        ]);
    }
}
