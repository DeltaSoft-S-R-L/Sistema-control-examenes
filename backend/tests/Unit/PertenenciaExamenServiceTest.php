<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exceptions\EstudianteNoAsociadoException;
use App\Models\Asignatura;
use App\Models\Carrera;
use App\Models\Estudiante;
use App\Models\Examen;
use App\Models\Facultad;
use App\Models\Habilitacion;
use App\Services\PertenenciaExamenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class PertenenciaExamenServiceTest extends TestCase
{
    use RefreshDatabase;

    private PertenenciaExamenService $service;
    private Carrera $carrera;
    private Asignatura $asignatura;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new PertenenciaExamenService();

        $facultad = Facultad::create([
            'codigo' => 'FCYT',
            'nombre' => 'Facultad de Ciencias y Tecnología',
        ]);

        $this->carrera = Carrera::create([
            'codigo'      => 'SIS',
            'nombre'      => 'Ingeniería de Sistemas',
            'id_facultad' => $facultad->id_facultad,
        ]);

        $this->asignatura = Asignatura::create([
            'codigo'     => 'SIS-101',
            'nombre'     => 'Programación I',
            'id_carrera' => $this->carrera->id_carrera,
        ]);
    }

    private function crearEstudiante(string $ci = '12345678', string $codigoSis = '20240001'): Estudiante
    {
        return Estudiante::create([
            'ci'                   => $ci,
            'nombre'               => 'Carlos',
            'apellido'             => 'Perez',
            'codigo_universitario' => $codigoSis,
            'id_carrera'           => $this->carrera->id_carrera,
            'estado'               => 'ACTIVO',
        ]);
    }

    private function crearExamen(string $nombre = 'Primer Parcial Programación I'): Examen
    {
        return Examen::create([
            'id_asignatura'    => $this->asignatura->id_asignatura,
            'nombre'           => $nombre,
            'fecha'            => '2026-11-20',
            'hora_inicio'      => '08:00:00',
            'duracion_minutos' => 90,
            'estado'           => 'PROGRAMADO',
        ]);
    }

    /**
     * Caso 1 (Pertenencia válida):
     * Crear en base de datos un examen, un estudiante y una habilitación.
     * Invocar el servicio y verificar que retorne la instancia de Habilitacion asociada
     * con relaciones eager loaded precargadas.
     */
    public function test_pertenencia_valida_retorna_instancia_habilitacion_con_relaciones(): void
    {
        $estudiante = $this->crearEstudiante('6543210', '20240002');
        $examen = $this->crearExamen('Examen Final Programación I');

        $habilitacionCreada = Habilitacion::create([
            'id_estudiante' => $estudiante->id_estudiante,
            'id_examen'     => $examen->id_examen,
            'estado'        => 'HABILITADO',
        ]);

        $resultado = $this->service->verificarPertenencia(
            (int) $estudiante->id_estudiante,
            (int) $examen->id_examen
        );

        $this->assertInstanceOf(Habilitacion::class, $resultado);
        $this->assertSame($habilitacionCreada->id_habilitacion, $resultado->id_habilitacion);
        $this->assertSame((int) $estudiante->id_estudiante, (int) $resultado->id_estudiante);
        $this->assertSame((int) $examen->id_examen, (int) $resultado->id_examen);

        // Verificar que las relaciones requeridas fueron cargadas via eager loading
        $this->assertTrue($resultado->relationLoaded('estudiante'), 'La relación estudiante debe estar cargada.');
        $this->assertTrue($resultado->relationLoaded('examen'), 'La relación examen debe estar cargada.');
        $this->assertSame($estudiante->id_estudiante, $resultado->estudiante->id_estudiante);
        $this->assertSame($examen->id_examen, $resultado->examen->id_examen);
    }

    /**
     * Caso 1.B: Verificar funcionamiento del método estático helper ::verificar()
     */
    public function test_helper_estatico_verificar_retorna_habilitacion_correctamente(): void
    {
        $estudiante = $this->crearEstudiante('7788990', '20240003');
        $examen = $this->crearExamen('Examen Parcial');

        $habilitacion = Habilitacion::create([
            'id_estudiante' => $estudiante->id_estudiante,
            'id_examen'     => $examen->id_examen,
            'estado'        => 'HABILITADO',
        ]);

        $resultado = PertenenciaExamenService::verificar(
            (int) $estudiante->id_estudiante,
            (int) $examen->id_examen
        );

        $this->assertInstanceOf(Habilitacion::class, $resultado);
        $this->assertSame($habilitacion->id_habilitacion, $resultado->id_habilitacion);
    }

    /**
     * Caso 2 (Examen ajeno):
     * Crear examen A y examen B. Registrar al estudiante solo en el examen B.
     * Invocar el servicio pasando examen A y verificar que lance EstudianteNoAsociadoException.
     */
    public function test_examen_ajeno_lanza_estudiante_no_asociado_exception(): void
    {
        $estudiante = $this->crearEstudiante('1122334', '20240004');
        $examenA = $this->crearExamen('Examen A');
        $examenB = $this->crearExamen('Examen B');

        // Registrar estudiante únicamente en examen B
        Habilitacion::create([
            'id_estudiante' => $estudiante->id_estudiante,
            'id_examen'     => $examenB->id_examen,
            'estado'        => 'HABILITADO',
        ]);

        $this->expectException(EstudianteNoAsociadoException::class);
        $this->expectExceptionMessage('El estudiante no pertenece a este examen.');

        // Intentar verificar con examen A
        $this->service->verificarPertenencia(
            (int) $estudiante->id_estudiante,
            (int) $examenA->id_examen
        );
    }

    /**
     * Caso 3 (Estudiante no registrado en ningún examen):
     * Verificar que lance EstudianteNoAsociadoException.
     */
    public function test_estudiante_no_registrado_en_ningun_examen_lanza_excepcion(): void
    {
        $estudianteHuerfano = $this->crearEstudiante('9988776', '20240005');
        $examen = $this->crearExamen('Examen C');

        $this->expectException(EstudianteNoAsociadoException::class);
        $this->expectExceptionMessage('El estudiante no pertenece a este examen.');

        $this->service->verificarPertenencia(
            (int) $estudianteHuerfano->id_estudiante,
            (int) $examen->id_examen
        );
    }

    /**
     * Caso 4: Contrato de respuesta y renderizado JSON de EstudianteNoAsociadoException
     */
    public function test_excepcion_estudiante_no_asociado_genera_payload_json_correcto(): void
    {
        $excepcion = new EstudianteNoAsociadoException();

        $this->assertSame(422, $excepcion->getStatusCode());
        $this->assertSame('NO_ASOCIADO', $excepcion->getErrorCode());

        $payload = $excepcion->toArray();
        $this->assertSame([
            'message' => 'El estudiante no pertenece a este examen.',
            'code'    => 'NO_ASOCIADO',
            'error'   => 'NO_ASOCIADO',
        ], $payload);

        $request = Request::create('/api/verificaciones/qr', 'POST');
        $response = $excepcion->render($request);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame($payload, $response->getData(true));
    }
}
