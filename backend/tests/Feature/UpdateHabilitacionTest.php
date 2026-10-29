<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;
use App\Models\Asignatura;
use App\Models\Carrera;
use App\Models\Estudiante;
use App\Models\Examen;
use App\Models\Facultad;
use App\Models\Habilitacion;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\Usuario;
use App\Utilities\PasswordHasher;
use Illuminate\Support\Str;

class UpdateHabilitacionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $connection = DB::connection();
        $database = $connection->selectOne('SELECT current_database() AS nombre')->nombre;

        if ($connection->getDriverName() !== 'pgsql' || $database !== 'control_examenes_pruebas') {
            throw new RuntimeException('Pruebas detenidas: base no autorizada.');
        }
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        if (DB::connection()->transactionLevel() > 0) {
            DB::rollBack();
        }
        parent::tearDown();
    }

    private function crearDatosBase(): array
    {
        $sufijo = strtolower(Str::random(8));

        $permiso = Permiso::firstOrCreate(['nombre' => 'GESTIONAR_HABILITACIONES']);
        $rol = Rol::firstOrCreate(['nombre' => 'control']);
        if (!$rol->permisos()->where('permiso.id_permiso', $permiso->id_permiso)->exists()) {
            $rol->permisos()->attach($permiso->id_permiso);
        }

        $usuario = Usuario::create([
            'nombre' => 'Test', 'apellido' => 'User', 'correo' => "u_{$sufijo}@example.com",
            'username' => "u_{$sufijo}", 'password_hash' => PasswordHasher::hash('123'),
            'id_rol' => $rol->id_rol, 'estado' => 'ACTIVO',
        ]);
        $token = $usuario->createToken('test')->plainTextToken;

        $facultad = Facultad::create(['codigo' => 'F'.$sufijo, 'nombre' => 'Fac']);
        $carrera = Carrera::create(['id_facultad' => $facultad->id_facultad, 'codigo' => 'C'.$sufijo, 'nombre' => 'Car']);
        $asignatura = Asignatura::create(['codigo' => 'A'.$sufijo, 'nombre' => 'Asig']);
        $examen = Examen::create([
            'id_asignatura' => $asignatura->id_asignatura, 'nombre' => 'Examen',
            'fecha' => '2026-12-01', 'hora_inicio' => '10:00', 'duracion_minutos' => 90, 'estado' => 'PROGRAMADO',
        ]);
        $estudiante = Estudiante::create([
            'ci' => $sufijo, 'nombre' => 'Est', 'apellido' => 'Test',
            'codigo_universitario' => $sufijo, 'id_carrera' => $carrera->id_carrera,
            'correo' => "e_{$sufijo}@example.com", 'estado' => 'ACTIVO',
        ]);

        $habilitacion = Habilitacion::create([
            'id_examen' => $examen->id_examen, 'id_estudiante' => $estudiante->id_estudiante, 'estado' => 'HABILITADO'
        ]);

        return [$habilitacion, $token, $usuario];
    }

    public function test_update_inhabilitar_asigna_otro_y_auditoria()
    {
        [$habilitacion, $token, $usuario] = $this->crearDatosBase();

        $response = $this->putJson("/api/habilitaciones/{$habilitacion->id_habilitacion}", [
            'estado' => 'NO_HABILITADO'
        ], ['Authorization' => "Bearer {$token}"]);

        $response->assertStatus(200)
                 ->assertJsonPath('motivo_tipo', 'OTRO')
                 ->assertJsonPath('inhabilitado_por', $usuario->id_usuario);
        $this->assertNotNull($response->json('inhabilitado_at'));
    }

    public function test_update_retrocompatibilidad_motivo_antiguo()
    {
        [$habilitacion, $token] = $this->crearDatosBase();

        $response = $this->putJson("/api/habilitaciones/{$habilitacion->id_habilitacion}", [
            'estado' => 'NO_HABILITADO',
            'motivo' => 'Falta de pago antiguo' // Simula envío del frontend desactualizado
        ], ['Authorization' => "Bearer {$token}"]);

        $response->assertStatus(200)
                 ->assertJsonPath('motivo_tipo', 'OTRO')
                 ->assertJsonPath('motivo_detalle', 'Falta de pago antiguo');
    }

    public function test_update_habilitar_limpia_auditoria()
    {
        [$habilitacion, $token] = $this->crearDatosBase();

        $this->putJson("/api/habilitaciones/{$habilitacion->id_habilitacion}", [
            'estado' => 'NO_HABILITADO', 'motivo_tipo' => 'MORA', 'motivo_detalle' => 'Deuda'
        ], ['Authorization' => "Bearer {$token}"]);

        $response = $this->putJson("/api/habilitaciones/{$habilitacion->id_habilitacion}", [
            'estado' => 'HABILITADO'
        ], ['Authorization' => "Bearer {$token}"]);

        $response->assertStatus(200)
                 ->assertJsonPath('estado', 'HABILITADO')
                 ->assertJsonPath('motivo_tipo', null)
                 ->assertJsonPath('motivo_detalle', null)
                 ->assertJsonPath('inhabilitado_por', null)
                 ->assertJsonPath('inhabilitado_at', null);
    }
}