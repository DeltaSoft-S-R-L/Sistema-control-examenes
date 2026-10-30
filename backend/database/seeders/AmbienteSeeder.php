<?php

namespace Database\Seeders;

use App\Models\Ambiente;
use Illuminate\Database\Seeder;

class AmbienteSeeder extends Seeder
{
    /**
     * Sembrar ambientes con diferentes cantidades de asientos/espacios.
     */
    public function run(): void
    {
        $ambientes = [
            [
                'codigo'    => 'A101',
                'nombre'    => 'Laboratorio de Cómputo 101',
                'ubicacion' => 'Bloque A - Piso 1',
                'capacidad' => 30,
                'estado'    => 'DISPONIBLE',
            ],
            [
                'codigo'    => 'A102',
                'nombre'    => 'Laboratorio de Cómputo 102',
                'ubicacion' => 'Bloque A - Piso 1',
                'capacidad' => 35,
                'estado'    => 'DISPONIBLE',
            ],
            [
                'codigo'    => 'A103',
                'nombre'    => 'Aula de Cómputo Especializado 103',
                'ubicacion' => 'Bloque A - Piso 1',
                'capacidad' => 25,
                'estado'    => 'DISPONIBLE',
            ],
            [
                'codigo'    => 'B201',
                'nombre'    => 'Aula Magna Central',
                'ubicacion' => 'Bloque B - Piso 2',
                'capacidad' => 150,
                'estado'    => 'DISPONIBLE',
            ],
            [
                'codigo'    => 'B202',
                'nombre'    => 'Aula Teórica 202',
                'ubicacion' => 'Bloque B - Piso 2',
                'capacidad' => 45,
                'estado'    => 'DISPONIBLE',
            ],
            [
                'codigo'    => 'B203',
                'nombre'    => 'Aula Teórica 203',
                'ubicacion' => 'Bloque B - Piso 2',
                'capacidad' => 60,
                'estado'    => 'DISPONIBLE',
            ],
            [
                'codigo'    => 'C301',
                'nombre'    => 'Laboratorio de Redes y Telecomunicaciones',
                'ubicacion' => 'Bloque C - Piso 3',
                'capacidad' => 20,
                'estado'    => 'DISPONIBLE',
            ],
            [
                'codigo'    => 'C302',
                'nombre'    => 'Laboratorio de Electrónica y Sistemas Embebidos',
                'ubicacion' => 'Bloque C - Piso 3',
                'capacidad' => 28,
                'estado'    => 'DISPONIBLE',
            ],
            [
                'codigo'    => 'D401',
                'nombre'    => 'Auditorio de Tecnología',
                'ubicacion' => 'Bloque D - Planta Baja',
                'capacidad' => 120,
                'estado'    => 'DISPONIBLE',
            ],
            [
                'codigo'    => 'D402',
                'nombre'    => 'Aula Escalonada 402',
                'ubicacion' => 'Bloque D - Piso 4',
                'capacidad' => 80,
                'estado'    => 'DISPONIBLE',
            ],
            [
                'codigo'    => 'E101',
                'nombre'    => 'Aula Polivalente 101',
                'ubicacion' => 'Bloque E - Piso 1',
                'capacidad' => 50,
                'estado'    => 'DISPONIBLE',
            ],
            [
                'codigo'    => 'E102',
                'nombre'    => 'Taller de Proyectos de Software',
                'ubicacion' => 'Bloque E - Piso 1',
                'capacidad' => 32,
                'estado'    => 'DISPONIBLE',
            ],
            [
                'codigo'    => 'F201',
                'nombre'    => 'Sala de Grados y Conferencias',
                'ubicacion' => 'Edificio Central - Piso 2',
                'capacidad' => 75,
                'estado'    => 'DISPONIBLE',
            ],
            [
                'codigo'    => 'S101',
                'nombre'    => 'Laboratorio de Desarrollo de Sistemas',
                'ubicacion' => 'Pabellón de Sistemas - Piso 1',
                'capacidad' => 40,
                'estado'    => 'DISPONIBLE',
            ],
        ];

        foreach ($ambientes as $data) {
            Ambiente::firstOrCreate(
                ['codigo' => $data['codigo']],
                $data
            );
        }
    }
}
