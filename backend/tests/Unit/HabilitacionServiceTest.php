<?php

namespace Tests\Unit;

use App\Models\Asignatura;
use App\Models\Carrera;
use App\Models\Estudiante;
use App\Models\Examen;
use App\Models\Facultad;
use App\Models\Habilitacion;
use App\Services\HabilitacionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Pruebas unitarias para HabilitacionService (VER-02 / S3-VER02-01).
 *
 * Criterios de aceptación:
 * 1. Los tres estados se calculan correctamente:
 *    - 'HABILITADO' (fila con habilitado = true / estado = 'HABILITADO')
 *    - 'NO_HABILITADO' (fila con habilitado = false / estado = 'NO_HABILITADO')
 *    - 'NO_ASOCIADO' (sin fila para ese examen)
 * 2. El servicio es idempotente y no modifica ninguna tabla (0 escrituras en BD).
 * 3. Se resuelve en exactamente 1 sola consulta SQL utilizando el índice único.
 * 4. Permite ejecución vía inyección de dependencias o llamada estática.
 */
class HabilitacionServiceTest extends TestCase
{
    use RefreshDatabase;

    private Examen $examen;
    private Estudiante $estudianteHabilitado;
    private Estudiante $estudianteInhabilitado;
    private Estudiante $estudianteNoAsociado;
    private HabilitacionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new HabilitacionService();

        $facultad = Facultad::create([
            'codigo' => 'FCYT',
            'nombre' => 'Facultad de Ciencias y Tecnología',
        ]);

        $carrera = Carrera::create([
            'id_facultad' => $facultad->id_facultad,
            'codigo'      => 'SIS',
            'nombre'      => 'Ingeniería de Sistemas',
        ]);

        $asignatura = Asignatura::create([
            'codigo'      => 'SIS-101',
            'nombre'      => 'Programación I',
            'descripcion' => 'Fundamentos de programación',
        ]);
        $asignatura->carreras()->attach($carrera->id_carrera);

        $this->examen = Examen::create([
            'id_asignatura'    => $asignatura->id_asignatura,
            'nombre'           => 'Primer Parcial - Programación I',
            'fecha'            => now()->addDays(2)->toDateString(),
            'hora_inicio'      => '08:00',
            'duracion_minutos' => 90,
            'estado'           => 'PROGRAMADO',
        ]);

        // 1. Estudiante habilitado
        $this->estudianteHabilitado = Estudiante::create([
            'ci'                   => '1000001',
            'nombre'               => 'Ana',
            'apellido'             => 'Pérez',
            'codigo_universitario' => '202000001',
            'correo'               => 'ana.perez@universidad.edu',
            'id_carrera'           => $carrera->id_carrera,
            'estado'               => 'ACTIVO',
        ]);

        Habilitacion::create([
            'id_estudiante' => $this->estudianteHabilitado->id_estudiante,
            'id_examen'     => $this->examen->id_examen,
            'estado'        => 'HABILITADO',
            'motivo'        => null,
        ]);

        // 2. Estudiante inhabilitado con motivo
        $this->estudianteInhabilitado = Estudiante::create([
            'ci'                   => '1000002',
            'nombre'               => 'Carlos',
            'apellido'             => 'López',
            'codigo_universitario' => '202000002',
            'correo'               => 'carlos.lopez@universidad.edu',
            'id_carrera'           => $carrera->id_carrera,
            'estado'               => 'ACTIVO',
        ]);

        Habilitacion::create([
            'id_estudiante' => $this->estudianteInhabilitado->id_estudiante,
            'id_examen'     => $this->examen->id_examen,
            'estado'        => 'NO_HABILITADO',
            'motivo'        => 'Deuda pendiente en colegiatura y baja asistencia.',
        ]);

        // 3. Estudiante sin registro en este examen
        $this->estudianteNoAsociado = Estudiante::create([
            'ci'                   => '1000003',
            'nombre'               => 'Elena',
            'apellido'             => 'Rojas',
            'codigo_universitario' => '202000003',
            'correo'               => 'elena.rojas@universidad.edu',
            'id_carrera'           => $carrera->id_carrera,
            'estado'               => 'ACTIVO',
        ]);
    }

    /**
     * Regla 1: Fila en estudiante_examen / habilitacion con habilitado = true.
     */
    public function test_comprobar_retorna_estado_habilitado_cuando_estudiante_tiene_permiso(): void
    {
        $resultado = $this->service->comprobar(
            $this->examen->id_examen,
            $this->estudianteHabilitado->id_estudiante
        );

        $this->assertIsObject($resultado);
        $this->assertSame('HABILITADO', $resultado->estado);
        $this->assertTrue($resultado->habilitado);
        $this->assertNull($resultado->motivo);
        $this->assertNotNull($resultado->id_habilitacion);
        $this->assertSame($this->examen->id_examen, $resultado->id_examen);
        $this->assertSame($this->estudianteHabilitado->id_estudiante, $resultado->id_estudiante);
    }

    /**
     * Regla 2: Fila en estudiante_examen / habilitacion con habilitado = false.
     */
    public function test_comprobar_retorna_estado_no_habilitado_con_motivo(): void
    {
        $resultado = $this->service->comprobar(
            $this->examen->id_examen,
            $this->estudianteInhabilitado->id_estudiante
        );

        $this->assertIsObject($resultado);
        $this->assertSame('NO_HABILITADO', $resultado->estado);
        $this->assertFalse($resultado->habilitado);
        $this->assertSame('Deuda pendiente en colegiatura y baja asistencia.', $resultado->motivo);
        $this->assertNotNull($resultado->id_habilitacion);
    }

    /**
     * Regla 3: Sin fila para ese examen (estudiante no registrado).
     */
    public function test_comprobar_retorna_estado_no_asociado_cuando_no_existe_registro(): void
    {
        $resultado = $this->service->comprobar(
            $this->examen->id_examen,
            $this->estudianteNoAsociado->id_estudiante
        );

        $this->assertIsObject($resultado);
        $this->assertSame('NO_ASOCIADO', $resultado->estado);
        $this->assertFalse($resultado->habilitado);
        $this->assertSame('El estudiante no está registrado en este examen.', $resultado->motivo);
        $this->assertNull($resultado->id_habilitacion);
    }

    /**
     * Idempotencia y sin efectos secundarios: El servicio no modifica ninguna tabla.
     */
    public function test_el_servicio_es_idempotente_y_no_modifica_ninguna_tabla(): void
    {
        $conteoHabilitacionesInicial = DB::table('habilitacion')->count();
        $conteoEstudiantesInicial    = DB::table('estudiante')->count();
        $conteoExamenesInicial       = DB::table('examen')->count();

        // Ejecutar comprobación en los 3 escenarios múltiples veces
        $this->service->comprobar($this->examen->id_examen, $this->estudianteHabilitado->id_estudiante);
        $this->service->comprobar($this->examen->id_examen, $this->estudianteInhabilitado->id_estudiante);
        $this->service->comprobar($this->examen->id_examen, $this->estudianteNoAsociado->id_estudiante);

        // Invocar nuevamente para confirmar idempotencia
        $this->service->comprobar($this->examen->id_examen, $this->estudianteNoAsociado->id_estudiante);

        $this->assertSame($conteoHabilitacionesInicial, DB::table('habilitacion')->count());
        $this->assertSame($conteoEstudiantesInicial, DB::table('estudiante')->count());
        $this->assertSame($conteoExamenesInicial, DB::table('examen')->count());
    }

    /**
     * Eficiencia: Se resuelve con exactamente una sola consulta SQL utilizando el índice único.
     */
    public function test_se_resuelve_con_una_sola_consulta_sql(): void
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->service->comprobar(
            $this->examen->id_examen,
            $this->estudianteHabilitado->id_estudiante
        );

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertCount(1, $queries, 'La comprobación debe ejecutarse en exactamente 1 sola consulta SQL.');
        $this->assertStringContainsString('habilitacion', strtolower($queries[0]['query']));
    }

    /**
     * Soporte de invocación estática HabilitacionService::comprobar(examenId, estudianteId).
     */
    public function test_permite_llamada_estatica(): void
    {
        $resultado = HabilitacionService::comprobar(
            $this->examen->id_examen,
            $this->estudianteHabilitado->id_estudiante
        );

        $this->assertIsObject($resultado);
        $this->assertSame('HABILITADO', $resultado->estado);
        $this->assertTrue($resultado->habilitado);
    }
}
