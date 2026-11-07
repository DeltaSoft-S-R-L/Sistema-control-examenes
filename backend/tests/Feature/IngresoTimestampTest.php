<?php

namespace Tests\Feature;

use App\Models\AsignacionAmbiente;
use App\Models\Asignatura;
use App\Models\Carrera;
use App\Models\Estudiante;
use App\Models\Examen;
use App\Models\Habilitacion;
use App\Models\Ingreso;
use App\Models\Rol;
use App\Models\Usuario;
use App\Utilities\PasswordHasher;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\TestCase;

class IngresoTimestampTest extends TestCase
{
    use RefreshDatabase;

    private array $datosIngreso;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Crear rol y usuario controlador.
        $rol = Rol::create([
            'nombre' => 'CONTROL_INGRESO',
            'descripcion' => 'Personal de control de ingreso',
        ]);

        $usuario = Usuario::create([
            'nombre' => 'Carlos',
            'apellido' => 'Control',
            'correo' => 'control.timestamp@universidad.edu',
            'username' => 'control_timestamp',
            'password_hash' => PasswordHasher::hash('Password123'),
            'id_rol' => $rol->id_rol,
            'estado' => 'ACTIVO',
        ]);

        // 2. Crear estructura académica.
        $facultad = \App\Models\Facultad::create([
            'codigo' => 'FCYT',
            'nombre' => 'Facultad de Ciencias',
        ]);

        $carrera = Carrera::create([
            'id_facultad' => $facultad->id_facultad,
            'codigo' => '411702',
            'nombre' => 'Ingenieria de Sistemas',
        ]);

        $asignatura = Asignatura::create([
            'codigo' => 'SIS-101',
            'nombre' => 'Ingenieria de Software',
            'descripcion' => 'Materia de prueba',
        ]);

        // 3. Crear examen.
        $examen = Examen::create([
            'id_asignatura' => $asignatura->id_asignatura,
            'nombre' => 'Examen de prueba timestamp',
            'fecha' => '2026-11-07',
            'hora_inicio' => '08:00',
            'duracion_minutos' => 90,
            'estado' => 'PROGRAMADO',
        ]);

        // 4. Crear estudiante.
        $estudiante = Estudiante::create([
            'ci' => '7777777',
            'nombre' => 'Juan',
            'apellido' => 'Perez',
            'codigo_universitario' => '202000777',
            'id_carrera' => $carrera->id_carrera,
            'correo' => 'estudiante.timestamp@universidad.edu',
            'estado' => 'ACTIVO',
        ]);

        // 5. Crear habilitación.
        $habilitacion = Habilitacion::create([
            'id_estudiante' => $estudiante->id_estudiante,
            'id_examen' => $examen->id_examen,
            'estado' => 'HABILITADO',
            'motivo' => null,
        ]);

        // 6. Crear ambiente.
        $ambienteId = DB::table('ambiente')->insertGetId([
            'codigo' => 'AMB-TIMESTAMP',
            'nombre' => 'Ambiente de prueba',
            'ubicacion' => 'Bloque A',
            'capacidad' => 30,
            'estado' => 'ACTIVO',
        ], 'id_ambiente');

        // 7. Asignar ambiente al examen.
        $asignacion = AsignacionAmbiente::create([
            'id_examen' => $examen->id_examen,
            'id_ambiente' => $ambienteId,
        ]);

        $this->datosIngreso = [
            'id_examen' => $examen->id_examen,
            'id_habilitacion' => $habilitacion->id_habilitacion,
            'id_asignacion_ambiente' => $asignacion->id_asignacion_ambiente,
            'id_usuario' => $usuario->id_usuario,
        ];
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function crearIngreso(): Ingreso
    {
        return Ingreso::create($this->datosIngreso);
    }

    /**
     * Comprueba que el timestamp se asigne con la hora del servidor.
     */
    public function test_asigna_timestamp_del_servidor_al_crear_ingreso(): void
    {
        Carbon::setTestNow('2026-11-07 10:15:30');

        $ingreso = $this->crearIngreso();

        $this->assertSame(
            '2026-11-07 10:15:30',
            $ingreso->fresh()->fecha_hora->format('Y-m-d H:i:s')
        );
    }

    /**
     * Comprueba que una fecha asignada externamente sea reemplazada.
     */
    public function test_no_permite_imponer_fecha_externa_al_crear_ingreso(): void
    {
        Carbon::setTestNow('2026-11-07 11:20:30');

        $ingreso = new Ingreso($this->datosIngreso);

        // Simula un intento de asignar una fecha manipulada.
        $ingreso->fecha_hora = Carbon::parse('2000-01-01 00:00:00');

        $ingreso->save();

        $this->assertSame(
            '2026-11-07 11:20:30',
            $ingreso->fresh()->fecha_hora->format('Y-m-d H:i:s')
        );
    }

    /**
     * Comprueba que el timestamp realmente se persista en la BD.
     */
    public function test_persiste_el_timestamp_generado_por_el_servidor(): void
    {
        Carbon::setTestNow('2026-11-07 12:30:45');

        $ingreso = $this->crearIngreso();

        $fechaGuardada = DB::table('ingreso')
            ->where('id_ingreso', $ingreso->id_ingreso)
            ->value('fecha_hora');

        $this->assertNotNull($fechaGuardada);

        $this->assertSame(
            '2026-11-07 12:30:45',
            Carbon::parse($fechaGuardada)->format('Y-m-d H:i:s')
        );
    }

    /**
     * Comprueba que no se pueda modificar el timestamp después de crear.
     */
    public function test_no_permite_modificar_timestamp_de_un_ingreso(): void
    {
        Carbon::setTestNow('2026-11-07 13:45:00');

        $ingreso = $this->crearIngreso();

        $fechaOriginal = $ingreso->fresh()->fecha_hora->format('Y-m-d H:i:s');

        $ingreso->fecha_hora = Carbon::parse('2000-01-01 00:00:00');

        try {
            $ingreso->save();

            $this->fail(
                'Se permitió modificar el timestamp del ingreso.'
            );
        } catch (LogicException $exception) {
            $this->assertSame(
                'La fecha y hora del ingreso no pueden modificarse.',
                $exception->getMessage()
            );
        }

        $fechaPersistida = Ingreso::findOrFail($ingreso->id_ingreso)
            ->fecha_hora
            ->format('Y-m-d H:i:s');

        $this->assertSame($fechaOriginal, $fechaPersistida);
    }
}
