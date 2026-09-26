<?php

namespace Tests\Feature;

use App\Models\Estudiante;
use App\Models\Habilitacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstudianteEndpointTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: puede consultar estudiante por id_estudiante (200 OK con JSON estructurado).
     */
    public function test_puede_consultar_estudiante_por_id(): void
    {
        $estudiante = Estudiante::create([
            'ci' => '1234567',
            'nombre' => 'Juan',
            'apellido' => 'Perez',
            'codigo_universitario' => 'SIS-2023001',
            'correo' => 'juan.perez@universidad.edu',
            'estado' => 'ACTIVO',
        ]);

        $response = $this->getJson('/api/estudiantes/' . $estudiante->id_estudiante);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Estudiante encontrado exitosamente',
                'estudiante' => [
                    'id_estudiante' => (string) $estudiante->id_estudiante,
                    'ci' => '1234567',
                    'nombre' => 'Juan',
                    'apellido' => 'Perez',
                    'codigo_universitario' => 'SIS-2023001',
                    'correo' => 'juan.perez@universidad.edu',
                    'estado' => 'ACTIVO',
                ],
            ]);
    }

    /**
     * Test: puede consultar estudiante por código SIS o CI (criterio unívoco).
     */
    public function test_puede_consultar_estudiante_por_codigo_o_ci(): void
    {
        $estudiante = Estudiante::create([
            'ci' => '7654321',
            'nombre' => 'Maria',
            'apellido' => 'Gomez',
            'codigo_universitario' => 'SIS-2023002',
            'correo' => 'maria.gomez@universidad.edu',
            'estado' => 'ACTIVO',
        ]);

        // 1. Consulta por código SIS / código universitario
        $responseCodigo = $this->getJson('/api/estudiantes/SIS-2023002');
        $responseCodigo->assertStatus(200)
            ->assertJsonPath('estudiante.id_estudiante', (string) $estudiante->id_estudiante)
            ->assertJsonPath('estudiante.codigo_universitario', 'SIS-2023002');

        // 2. Consulta por carnet de identidad (CI)
        $responseCi = $this->getJson('/api/estudiantes/7654321');
        $responseCi->assertStatus(200)
            ->assertJsonPath('estudiante.id_estudiante', (string) $estudiante->id_estudiante)
            ->assertJsonPath('estudiante.ci', '7654321');
    }

    /**
     * Test: retorna 404 cuando el estudiante no existe.
     */
    public function test_retorna_404_cuando_estudiante_no_existe(): void
    {
        $response = $this->getJson('/api/estudiantes/CODIGO_INEXISTENTE_9999');

        $response->assertStatus(404)
            ->assertJson([
                'error' => 'Estudiante no encontrado',
            ]);
    }

    /**
     * Test: listado con filtros y paginación en GET /api/estudiantes.
     */
    public function test_listado_con_filtros_y_paginacion(): void
    {
        Estudiante::create([
            'ci' => '1001',
            'nombre' => 'Carlos',
            'apellido' => 'Alvarez',
            'codigo_universitario' => 'SIS-1001',
            'correo' => 'carlos@universidad.edu',
            'estado' => 'ACTIVO',
        ]);

        Estudiante::create([
            'ci' => '1002',
            'nombre' => 'Carla',
            'apellido' => 'Benitez',
            'codigo_universitario' => 'SIS-1002',
            'correo' => 'carla@universidad.edu',
            'estado' => 'INACTIVO',
        ]);

        Estudiante::create([
            'ci' => '1003',
            'nombre' => 'Roberto',
            'apellido' => 'Castro',
            'codigo_universitario' => 'SIS-1003',
            'correo' => 'roberto@universidad.edu',
            'estado' => 'ACTIVO',
        ]);

        // 1. Filtrar por búsqueda de texto 'Car' y estado 'ACTIVO'
        $responseSearch = $this->getJson('/api/estudiantes?search=Car&estado=ACTIVO');
        $responseSearch->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nombre', 'Carlos');

        // 2. Filtrar por CI específico
        $responseCi = $this->getJson('/api/estudiantes?ci=1003');
        $responseCi->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.ci', '1003');

        // 3. Filtrar por código SIS específico
        $responseSis = $this->getJson('/api/estudiantes?codigo_sis=SIS-1002');
        $responseSis->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.codigo_universitario', 'SIS-1002');

        // 4. Validar estructura de paginación y límite
        $responsePaginado = $this->getJson('/api/estudiantes?limit=2');
        $responsePaginado->assertStatus(200)
            ->assertJsonStructure([
                'current_page',
                'data' => [
                    '*' => [
                        'id_estudiante',
                        'ci',
                        'nombre',
                        'apellido',
                        'codigo_universitario',
                        'correo',
                        'estado',
                    ]
                ],
                'first_page_url',
                'from',
                'last_page',
                'per_page',
                'total',
            ])
            ->assertJsonCount(2, 'data');
    }

    /**
     * Test: puede consultar estudiante e incluir relaciones con habilitaciones.
     */
    public function test_puede_incluir_habilitaciones_en_consulta(): void
    {
        $estudiante = Estudiante::create([
            'ci' => '998877',
            'nombre' => 'Laura',
            'apellido' => 'Morales',
            'codigo_universitario' => 'SIS-998877',
            'correo' => 'laura@universidad.edu',
            'estado' => 'ACTIVO',
        ]);

        Habilitacion::create([
            'id_estudiante' => $estudiante->id_estudiante,
            'id_examen' => 10,
            'estado' => 'HABILITADO',
            'motivo' => 'Cumple con los requisitos académicos',
        ]);

        $response = $this->getJson('/api/estudiantes/' . $estudiante->id_estudiante . '?include_habilitaciones=true');

        $response->assertStatus(200)
            ->assertJsonPath('estudiante.habilitaciones.0.id_examen', '10')
            ->assertJsonPath('estudiante.habilitaciones.0.estado', 'HABILITADO');
    }
}
