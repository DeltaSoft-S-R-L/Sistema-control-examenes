<?php

namespace Tests\Feature;

use App\Models\Asignatura;
use App\Models\AuditLog;
use App\Models\Carrera;
use App\Models\Estudiante;
use App\Models\Examen;
use App\Models\Facultad;
use App\Models\Habilitacion;
use App\Models\NormaExamen;
use App\Models\Rol;
use App\Models\Usuario;
use App\Utilities\PasswordHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class NormaExamenAuditoriaTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $adminUser;
    private Usuario $docenteUser;
    private string $docenteToken;
    private Examen $examen;
    private Estudiante $estudiante;

    protected function setUp(): void
    {
        parent::setUp();

        $rolAdmin = Rol::create([
            'nombre' => 'ADMINISTRADOR',
            'descripcion' => 'Administrador',
        ]);

        $rolDocente = Rol::create([
            'nombre' => 'DOCENTE',
            'descripcion' => 'Docente',
        ]);

        $this->adminUser = Usuario::create([
            'nombre' => 'Admin',
            'apellido' => 'General',
            'correo' => 'admin@test.com',
            'username' => 'admin_test',
            'password_hash' => PasswordHasher::hash('Password123'),
            'id_rol' => $rolAdmin->id_rol,
            'estado' => 'ACTIVO',
        ]);

        $this->docenteUser = Usuario::create([
            'nombre' => 'Roberto',
            'apellido' => 'Gomez',
            'correo' => 'docente@test.com',
            'username' => 'docente_test',
            'password_hash' => PasswordHasher::hash('Password123'),
            'id_rol' => $rolDocente->id_rol,
            'estado' => 'ACTIVO',
        ]);
        $this->docenteToken = $this->docenteUser->createToken('docente-token')->plainTextToken;

        $facultad = Facultad::create([
            'codigo' => 'FCYT',
            'nombre' => 'Facultad de Ciencias y Tecnologia',
        ]);

        $carrera = Carrera::create([
            'id_facultad' => $facultad->id_facultad,
            'codigo' => 'SIS',
            'nombre' => 'Ingenieria de Sistemas',
        ]);

        $asignatura = Asignatura::create([
            'codigo' => 'SIS-101',
            'nombre' => 'Programacion I',
            'descripcion' => 'Introduccion a la programacion',
        ]);

        $this->examen = Examen::create([
            'id_asignatura' => $asignatura->id_asignatura,
            'nombre' => 'Primer Parcial Programacion I',
            'fecha' => '2026-10-20',
            'hora_inicio' => '08:00',
            'duracion_minutos' => 90,
            'estado' => 'PROGRAMADO',
        ]);

        $this->estudiante = Estudiante::create([
            'ci' => '1234567',
            'nombre' => 'Juan',
            'apellido' => 'Perez',
            'codigo_universitario' => '2022001',
            'id_carrera' => $carrera->id_carrera,
            'correo' => 'juan.perez@test.com',
            'estado' => 'ACTIVO',
        ]);

        Habilitacion::create([
            'id_examen' => $this->examen->id_examen,
            'id_estudiante' => $this->estudiante->id_estudiante,
            'estado' => 'HABILITADO',
        ]);
    }

    private function headers(): array
    {
        return [
            'Authorization' => "Bearer {$this->docenteToken}",
            'Accept' => 'application/json',
        ];
    }

    public function test_crear_norma_genera_registro_en_audit_logs_con_datos_correctos(): void
    {
        $payload = [
            'tipo' => 'PARTICULAR',
            'descripcion' => 'Permiso para rendir examen con audifonos especiales.',
            'estudiante_id' => $this->estudiante->id_estudiante,
        ];

        $conteoInicial = AuditLog::count();

        $response = $this->postJson(
            "/api/examenes/{$this->examen->id_examen}/normas",
            $payload,
            $this->headers()
        );

        $response->assertStatus(201);
        $normaId = $response->json('id');

        $this->assertEquals($conteoInicial + 1, AuditLog::count());

        $log = AuditLog::latest('id_audit_log')->first();
        $this->assertNotNull($log);
        $this->assertEquals($this->docenteUser->id_usuario, $log->id_usuario);
        $this->assertEquals('NORMA_CREADA', $log->accion);
        $this->assertEquals('norma_examen', $log->entidad);
        $this->assertEquals((string) $normaId, $log->id_registro);
        $this->assertNotNull($log->fecha_hora);

        $info = $log->informacion;
        $this->assertEquals($this->examen->id_examen, $info['examen_id']);
        $this->assertEquals('PARTICULAR', $info['tipo']);
        $this->assertEquals($this->estudiante->id_estudiante, $info['estudiante_id']);
        $this->assertEquals('Permiso para rendir examen con audifonos especiales.', $info['descripcion']);
    }

    public function test_eliminar_norma_genera_registro_en_audit_logs_con_accion_norma_eliminada(): void
    {
        $norma = NormaExamen::create([
            'id_examen' => $this->examen->id_examen,
            'id_estudiante' => null,
            'tipo' => 'GENERAL',
            'descripcion' => 'Norma general a auditar eliminacion.',
            'created_by' => $this->docenteUser->id_usuario,
        ]);

        $normaId = $norma->id_norma_examen;
        $conteoAntesDeEliminar = AuditLog::count();

        $response = $this->deleteJson(
            "/api/examenes/{$this->examen->id_examen}/normas/{$normaId}",
            [],
            $this->headers()
        );

        $response->assertStatus(204);

        $this->assertEquals($conteoAntesDeEliminar + 1, AuditLog::count());

        $log = AuditLog::latest('id_audit_log')->first();
        $this->assertNotNull($log);
        $this->assertEquals($this->docenteUser->id_usuario, $log->id_usuario);
        $this->assertEquals('NORMA_ELIMINADA', $log->accion);
        $this->assertEquals('norma_examen', $log->entidad);
        $this->assertEquals((string) $normaId, $log->id_registro);
        $this->assertNotNull($log->fecha_hora);

        $info = $log->informacion;
        $this->assertEquals($this->examen->id_examen, $info['examen_id']);
        $this->assertEquals('GENERAL', $info['tipo']);
        $this->assertNull($info['estudiante_id']);
        $this->assertEquals('Norma general a auditar eliminacion.', $info['descripcion']);
    }

    public function test_crear_norma_sin_usuario_autenticado_no_rompe_operacion_y_registra_id_usuario_null(): void
    {
        $conteoInicial = AuditLog::count();

        $norma = NormaExamen::create([
            'id_examen' => $this->examen->id_examen,
            'id_estudiante' => null,
            'tipo' => 'GENERAL',
            'descripcion' => 'Norma creada desde consola o seeder sin sesion.',
            'created_by' => $this->adminUser->id_usuario,
        ]);

        $this->assertNotNull($norma->id_norma_examen);
        $this->assertEquals($conteoInicial + 1, AuditLog::count());

        $log = AuditLog::latest('id_audit_log')->first();
        $this->assertNotNull($log);
        $this->assertNull($log->id_usuario);
        $this->assertEquals('NORMA_CREADA', $log->accion);
        $this->assertEquals('norma_examen', $log->entidad);
        $this->assertEquals((string) $norma->id_norma_examen, $log->id_registro);
        $this->assertEquals($this->examen->id_examen, $log->informacion['examen_id']);
    }

    public function test_eliminar_norma_sin_usuario_autenticado_no_rompe_operacion(): void
    {
        $norma = NormaExamen::create([
            'id_examen' => $this->examen->id_examen,
            'id_estudiante' => null,
            'tipo' => 'GENERAL',
            'descripcion' => 'Norma a eliminar sin sesion.',
            'created_by' => $this->adminUser->id_usuario,
        ]);

        $conteoAntesDeEliminar = AuditLog::count();

        $norma->delete();

        $this->assertEquals($conteoAntesDeEliminar + 1, AuditLog::count());

        $log = AuditLog::latest('id_audit_log')->first();
        $this->assertNotNull($log);
        $this->assertNull($log->id_usuario);
        $this->assertEquals('NORMA_ELIMINADA', $log->accion);
        $this->assertEquals('norma_examen', $log->entidad);
    }

    public function test_audit_logs_es_inmutable_y_rechaza_update_y_delete(): void
    {
        $log = AuditLog::create([
            'id_usuario' => $this->adminUser->id_usuario,
            'accion' => 'NORMA_CREADA',
            'entidad' => 'norma_examen',
            'id_registro' => '1',
            'fecha_hora' => now(),
            'informacion' => ['tipo' => 'GENERAL'],
        ]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Los registros de auditoría no se pueden modificar.');
        $log->update(['accion' => 'NORMA_MODIFICADA']);
    }

    public function test_audit_logs_rechaza_eliminacion(): void
    {
        $log = AuditLog::create([
            'id_usuario' => $this->adminUser->id_usuario,
            'accion' => 'NORMA_CREADA',
            'entidad' => 'norma_examen',
            'id_registro' => '1',
            'fecha_hora' => now(),
            'informacion' => ['tipo' => 'GENERAL'],
        ]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Los registros de auditoría no se pueden eliminar.');
        $log->delete();
    }

    public function test_cada_alta_y_baja_genera_exactamente_un_registro_en_audit_logs(): void
    {
        $conteoInicial = AuditLog::count();

        $responseCreate = $this->postJson(
            "/api/examenes/{$this->examen->id_examen}/normas",
            [
                'tipo' => 'GENERAL',
                'descripcion' => 'Norma para conteo exacto de auditoria.',
            ],
            $this->headers()
        );
        $responseCreate->assertStatus(201);
        $normaId = $responseCreate->json('id');

        $this->assertEquals($conteoInicial + 1, AuditLog::count());

        $responseDelete = $this->deleteJson(
            "/api/examenes/{$this->examen->id_examen}/normas/{$normaId}",
            [],
            $this->headers()
        );
        $responseDelete->assertStatus(204);

        $this->assertEquals($conteoInicial + 2, AuditLog::count());
    }
}
