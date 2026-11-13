<?php

declare(strict_types=1);

namespace Tests\Feature\Sprint4;

use App\Models\Ambiente;
use App\Models\AsignacionAmbiente;
use App\Models\Asignatura;
use App\Models\AuditLog;
use App\Models\Carrera;
use App\Models\Estudiante;
use App\Models\Examen;
use App\Models\Facultad;
use App\Models\Habilitacion;
use App\Models\Ingreso;
use App\Models\IntentoIngreso;
use App\Models\Rol;
use App\Models\Usuario;
use App\Utilities\PasswordHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Ing04ValidacionAmbienteTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $controlUser;
    private string $controlToken;

    private Usuario $docenteUser;
    private string $docenteToken;

    private Examen $examen;
    private Ambiente $ambiente691B;
    private Ambiente $ambiente690A;
    private AsignacionAmbiente $asignacion691B;
    private AsignacionAmbiente $asignacion690A;

    private Estudiante $estudiante691B;
    private Habilitacion $habilitacion691B;

    private Estudiante $estudianteSinAula;
    private Habilitacion $habilitacionSinAula;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Roles
        $rolControl = Rol::create([
            'nombre'      => 'CONTROL_INGRESO',
            'descripcion' => 'Personal de control de ingreso en puerta',
        ]);

        $rolDocente = Rol::create([
            'nombre'      => 'DOCENTE',
            'descripcion' => 'Docente universitario',
        ]);

        // 2. Usuarios
        $this->controlUser = Usuario::create([
            'nombre'        => 'Marcos',
            'apellido'      => 'Salvatierra',
            'correo'        => 'marcos.control@umss.edu.bo',
            'username'      => 'marcos_control',
            'password_hash' => PasswordHasher::hash('ControlPass123!'),
            'id_rol'        => $rolControl->id_rol,
            'estado'        => 'ACTIVO',
        ]);
        $this->controlToken = $this->controlUser->createToken('token-control')->plainTextToken;

        $this->docenteUser = Usuario::create([
            'nombre'        => 'Patricia',
            'apellido'      => 'Docente',
            'correo'        => 'patricia.docente@umss.edu.bo',
            'username'      => 'patricia_docente',
            'password_hash' => PasswordHasher::hash('DocentePass123!'),
            'id_rol'        => $rolDocente->id_rol,
            'estado'        => 'ACTIVO',
        ]);
        $this->docenteToken = $this->docenteUser->createToken('token-docente')->plainTextToken;

        // 3. Estructura Académica
        $facultad = Facultad::create([
            'codigo' => 'FCYT',
            'nombre' => 'Facultad de Ciencias y Tecnología',
        ]);

        $carrera = Carrera::create([
            'codigo'      => 'SIS',
            'nombre'      => 'Ingeniería de Sistemas',
            'id_facultad' => $facultad->id_facultad,
        ]);

        $asignatura = Asignatura::create([
            'codigo'      => 'SIS-101',
            'nombre'      => 'Programación I',
            'descripcion' => 'Primer semestre',
        ]);

        // 4. Examen
        $this->examen = Examen::create([
            'id_asignatura'    => $asignatura->id_asignatura,
            'nombre'           => 'Examen Parcial Programación I',
            'fecha'            => '2026-11-20',
            'hora_inicio'      => '08:00:00',
            'duracion_minutos' => 90,
            'estado'           => 'PROGRAMADO',
        ]);

        // 5. Ambientes y Asignaciones
        $this->ambiente691B = Ambiente::create([
            'codigo'    => '691B',
            'nombre'    => 'Aula 691B',
            'ubicacion' => 'Edificio Nuevo - Piso 2',
            'capacidad' => 40,
            'estado'    => 'DISPONIBLE',
        ]);

        $this->ambiente690A = Ambiente::create([
            'codigo'    => '690A',
            'nombre'    => 'Aula 690A',
            'ubicacion' => 'Edificio Central - Piso 1',
            'capacidad' => 50,
            'estado'    => 'DISPONIBLE',
        ]);

        $this->asignacion691B = AsignacionAmbiente::create([
            'id_examen'   => $this->examen->id_examen,
            'id_ambiente' => $this->ambiente691B->id_ambiente,
        ]);

        $this->asignacion690A = AsignacionAmbiente::create([
            'id_examen'   => $this->examen->id_examen,
            'id_ambiente' => $this->ambiente690A->id_ambiente,
        ]);

        // 6. Estudiante con Aula Asignada (Aula 691B)
        $this->estudiante691B = Estudiante::create([
            'ci'                   => '7700001',
            'nombre'               => 'Elena',
            'apellido'             => 'Rios',
            'codigo_universitario' => '202400100',
            'id_carrera'           => $carrera->id_carrera,
            'correo'               => 'elena.rios@umss.edu.bo',
            'estado'               => 'ACTIVO',
        ]);

        $this->habilitacion691B = Habilitacion::create([
            'id_estudiante'          => $this->estudiante691B->id_estudiante,
            'id_examen'              => $this->examen->id_examen,
            'id_asignacion_ambiente' => $this->asignacion691B->id_asignacion_ambiente,
            'estado'                 => 'HABILITADO',
        ]);

        // 7. Estudiante sin aula forzada (compatibilidad retroactiva)
        $this->estudianteSinAula = Estudiante::create([
            'ci'                   => '7700002',
            'nombre'               => 'Mauricio',
            'apellido'             => 'Vaca',
            'codigo_universitario' => '202400200',
            'id_carrera'           => $carrera->id_carrera,
            'correo'               => 'mauricio.vaca@umss.edu.bo',
            'estado'               => 'ACTIVO',
        ]);

        $this->habilitacionSinAula = Habilitacion::create([
            'id_estudiante'          => $this->estudianteSinAula->id_estudiante,
            'id_examen'              => $this->examen->id_examen,
            'id_asignacion_ambiente' => null,
            'estado'                 => 'HABILITADO',
        ]);
    }

    /**
     * Caso 1 (Ambiente Correcto - ING-04):
     * El estudiante ingresa por la puerta del ambiente que tiene asignado (Aula 691B).
     * Retorna 201 Created exitoso y crea el registro en la tabla ingreso.
     */
    public function test_estudiante_ingresa_por_ambiente_correcto_retorna_201_exitoso(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->controlToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/ingresos', [
            'id_examen'              => $this->examen->id_examen,
            'id_habilitacion'        => $this->habilitacion691B->id_habilitacion,
            'id_asignacion_ambiente' => $this->asignacion691B->id_asignacion_ambiente,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'id_examen'              => $this->examen->id_examen,
                'id_habilitacion'        => $this->habilitacion691B->id_habilitacion,
                'id_asignacion_ambiente' => $this->asignacion691B->id_asignacion_ambiente,
                'id_usuario'             => $this->controlUser->id_usuario,
            ]);

        $this->assertDatabaseCount('ingreso', 1);
        $this->assertDatabaseHas('ingreso', [
            'id_habilitacion'        => $this->habilitacion691B->id_habilitacion,
            'id_asignacion_ambiente' => $this->asignacion691B->id_asignacion_ambiente,
        ]);

        // No debe haberse registrado ninguna anomalía de aula incorrecta
        $this->assertDatabaseMissing('intento_ingreso', [
            'motivo' => 'AULA_INCORRECTA',
        ]);
    }

    /**
     * Caso 2 (Discrepancia de Ambiente - Aula Incorrecta - ING-04):
     * El estudiante está asignado al Aula 691B, pero intenta ingresar por la puerta del Aula 690A del mismo examen.
     * Retorna código 'AULA_INCORRECTA', incluye el objeto ambiente_asignado con "nombre": "Aula 691B",
     * NO crea registro en la tabla ingreso, y SÍ crea el registro en intento_ingreso con motivo = 'AULA_INCORRECTA' y en audit_logs.
     */
    public function test_discrepancia_de_ambiente_retorna_422_aula_incorrecta_y_registra_anomalias(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->controlToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/ingresos', [
            'id_examen'              => $this->examen->id_examen,
            'id_habilitacion'        => $this->habilitacion691B->id_habilitacion,
            'id_asignacion_ambiente' => $this->asignacion690A->id_asignacion_ambiente,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message'           => 'El estudiante está habilitado, pero le corresponde ingresar en otro ambiente.',
                'code'              => 'AULA_INCORRECTA',
                'error'             => 'AULA_INCORRECTA',
                'ambiente_asignado' => [
                    'id_ambiente'            => $this->ambiente691B->id_ambiente,
                    'id_asignacion_ambiente' => $this->asignacion691B->id_asignacion_ambiente,
                    'codigo'                 => '691B',
                    'nombre'                 => 'Aula 691B',
                    'ubicacion'              => 'Edificio Nuevo - Piso 2',
                ],
            ])
            ->assertJsonStructure([
                'message',
                'code',
                'error',
                'ambiente_asignado' => [
                    'id_ambiente',
                    'id_asignacion_ambiente',
                    'codigo',
                    'nombre',
                    'ubicacion',
                ],
            ]);

        // 1. NO crea registro en la tabla ingreso
        $this->assertDatabaseCount('ingreso', 0);

        // 2. SÍ crea el registro en intento_ingreso con motivo = 'AULA_INCORRECTA'
        $this->assertDatabaseHas('intento_ingreso', [
            'id_estudiante'          => $this->estudiante691B->id_estudiante,
            'id_examen'              => $this->examen->id_examen,
            'id_asignacion_ambiente' => $this->asignacion690A->id_asignacion_ambiente,
            'id_usuario'             => $this->controlUser->id_usuario,
            'motivo'                 => 'AULA_INCORRECTA',
        ]);

        // 3. SÍ crea el registro en audit_logs
        $this->assertDatabaseHas('audit_logs', [
            'accion'     => 'DISCREPANCIA_AMBIENTE_INGRESO',
            'entidad'    => 'ingreso',
            'id_usuario' => $this->controlUser->id_usuario,
        ]);
    }

    /**
     * Caso 3 (Ambiente de otro examen - ING-04):
     * Si se envía un id_asignacion_ambiente que no pertenece al examen activo, rechaza la operación con 422.
     */
    public function test_ambiente_de_otro_examen_rechaza_operacion_con_422_ambiente_no_corresponde(): void
    {
        // Crear un segundo examen y un ambiente asignado a él
        $asignatura2 = Asignatura::create([
            'codigo'      => 'SIS-102',
            'nombre'      => 'Base de Datos I',
            'descripcion' => 'Segundo semestre',
        ]);

        $otroExamen = Examen::create([
            'id_asignatura'    => $asignatura2->id_asignatura,
            'nombre'           => 'Examen Parcial Base de Datos I',
            'fecha'            => '2026-11-25',
            'hora_inicio'      => '10:00:00',
            'duracion_minutos' => 90,
            'estado'           => 'PROGRAMADO',
        ]);

        $ambienteOtroExamen = Ambiente::create([
            'codigo'    => 'AUD-1',
            'nombre'    => 'Auditorio Principal',
            'ubicacion' => 'Edificio Central',
            'capacidad' => 100,
            'estado'    => 'DISPONIBLE',
        ]);

        $asignacionOtroExamen = AsignacionAmbiente::create([
            'id_examen'   => $otroExamen->id_examen,
            'id_ambiente' => $ambienteOtroExamen->id_ambiente,
        ]);

        // Intentar registrar ingreso con el ambiente del otro examen
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->controlToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/ingresos', [
            'id_examen'              => $this->examen->id_examen,
            'id_habilitacion'        => $this->habilitacion691B->id_habilitacion,
            'id_asignacion_ambiente' => $asignacionOtroExamen->id_asignacion_ambiente,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'code'  => 'AMBIENTE_NO_CORRESPONDE',
                'error' => 'AMBIENTE_NO_CORRESPONDE',
            ]);

        $this->assertDatabaseCount('ingreso', 0);
    }

    /**
     * Compatibilidad hacia atrás:
     * Un estudiante sin ambiente específicamente asignado (id_asignacion_ambiente = null)
     * puede ingresar en cualquier aula válida que pertenezca al examen.
     */
    public function test_estudiante_sin_ambiente_especifico_asignado_puede_ingresar_a_cualquier_aula(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->controlToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/ingresos', [
            'id_examen'              => $this->examen->id_examen,
            'id_habilitacion'        => $this->habilitacionSinAula->id_habilitacion,
            'id_asignacion_ambiente' => $this->asignacion690A->id_asignacion_ambiente,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'id_examen'              => $this->examen->id_examen,
                'id_habilitacion'        => $this->habilitacionSinAula->id_habilitacion,
                'id_asignacion_ambiente' => $this->asignacion690A->id_asignacion_ambiente,
            ]);

        $this->assertDatabaseCount('ingreso', 1);
    }

    /**
     * Endpoint dedicado POST /api/verificaciones/ambiente:
     * Cuando el ambiente coincide, retorna HTTP 200 con valido = true y datos del ambiente.
     */
    public function test_endpoint_verificaciones_ambiente_retorna_200_cuando_coincide(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->controlToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/verificaciones/ambiente', [
            'id_examen'              => $this->examen->id_examen,
            'id_habilitacion'        => $this->habilitacion691B->id_habilitacion,
            'id_asignacion_ambiente' => $this->asignacion691B->id_asignacion_ambiente,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'valido'            => true,
                'coincide'          => true,
                'message'           => 'El ambiente corresponde al estudiante.',
                'ambiente_asignado' => [
                    'id_ambiente'            => $this->ambiente691B->id_ambiente,
                    'id_asignacion_ambiente' => $this->asignacion691B->id_asignacion_ambiente,
                    'codigo'                 => '691B',
                    'nombre'                 => 'Aula 691B',
                ],
            ]);
    }

    /**
     * Endpoint dedicado POST /api/verificaciones/ambiente:
     * Cuando hay discrepancia de aula, retorna HTTP 422 con AULA_INCORRECTA y audita el incidente.
     */
    public function test_endpoint_verificaciones_ambiente_retorna_422_cuando_hay_discrepancia(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->controlToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/verificaciones/ambiente', [
            'id_examen'              => $this->examen->id_examen,
            'id_habilitacion'        => $this->habilitacion691B->id_habilitacion,
            'id_asignacion_ambiente' => $this->asignacion690A->id_asignacion_ambiente,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message'           => 'El estudiante está habilitado, pero le corresponde ingresar en otro ambiente.',
                'code'              => 'AULA_INCORRECTA',
                'error'             => 'AULA_INCORRECTA',
                'ambiente_asignado' => [
                    'id_ambiente'            => $this->ambiente691B->id_ambiente,
                    'id_asignacion_ambiente' => $this->asignacion691B->id_asignacion_ambiente,
                    'codigo'                 => '691B',
                    'nombre'                 => 'Aula 691B',
                    'ubicacion'              => 'Edificio Nuevo - Piso 2',
                ],
            ]);

        $this->assertDatabaseHas('intento_ingreso', [
            'id_estudiante'          => $this->estudiante691B->id_estudiante,
            'id_examen'              => $this->examen->id_examen,
            'id_asignacion_ambiente' => $this->asignacion690A->id_asignacion_ambiente,
            'motivo'                 => 'AULA_INCORRECTA',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'accion'  => 'DISCREPANCIA_AMBIENTE_INGRESO',
            'entidad' => 'ingreso',
        ]);
    }

    /**
     * RBAC en POST /api/verificaciones/ambiente:
     * Petición sin token retorna 401, rol DOCENTE retorna 403, rol CONTROL_INGRESO retorna 200/422.
     */
    public function test_endpoint_verificaciones_ambiente_restringido_por_rbac(): void
    {
        // Sin token -> 401
        $this->withHeaders(['Accept' => 'application/json'])
            ->postJson('/api/verificaciones/ambiente', [
                'id_examen'              => $this->examen->id_examen,
                'id_habilitacion'        => $this->habilitacion691B->id_habilitacion,
                'id_asignacion_ambiente' => $this->asignacion691B->id_asignacion_ambiente,
            ])
            ->assertStatus(401);

        // Con rol DOCENTE -> 403
        $this->withHeaders([
            'Authorization' => "Bearer {$this->docenteToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/verificaciones/ambiente', [
            'id_examen'              => $this->examen->id_examen,
            'id_habilitacion'        => $this->habilitacion691B->id_habilitacion,
            'id_asignacion_ambiente' => $this->asignacion691B->id_asignacion_ambiente,
        ])->assertStatus(403);
    }
}
