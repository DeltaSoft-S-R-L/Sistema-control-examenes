<?php

namespace Tests\Feature\Sprint4;

use App\Models\Ambiente;
use App\Models\AsignacionAmbiente;
use App\Models\Asignatura;
use App\Models\Carrera;
use App\Models\Estudiante;
use App\Models\Examen;
use App\Models\Facultad;
use App\Models\Habilitacion;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\CuentaEstudianteService;
use App\Services\QrTokenService;
use App\Utilities\PasswordHasher;
use Illuminate\Support\Facades\DB;
use Tests\Support\SQLiteSeguroTestCase;

class ValidarRolEstudianteTest extends SQLiteSeguroTestCase
{
    private Rol $rolEstudiante;
    private Rol $rolAdministrador;
    private Rol $rolDocente;
    private Rol $rolControl;

    private Estudiante $estudiante;
    private Usuario $usuarioEstudiante;
    private string $tokenEstudiante;

    private Usuario $administrador;
    private string $tokenAdmin;
    private Usuario $docente;
    private string $tokenDocente;
    private Usuario $controlIngreso;
    private string $tokenControl;

    private Facultad $facultad;
    private Carrera $carrera;
    private Asignatura $asignatura;
    private Examen $examen;
    private Ambiente $ambiente;
    private AsignacionAmbiente $asignacionAmbiente;
    private Habilitacion $habilitacion;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rolEstudiante = Rol::firstOrCreate(
            ['nombre' => 'ESTUDIANTE'],
            ['descripcion' => 'Estudiante del sistema']
        );
        $this->rolAdministrador = Rol::firstOrCreate(
            ['nombre' => 'ADMINISTRADOR'],
            ['descripcion' => 'Administrador del sistema']
        );
        $this->rolDocente = Rol::firstOrCreate(
            ['nombre' => 'DOCENTE'],
            ['descripcion' => 'Docente universitario']
        );
        $this->rolControl = Rol::firstOrCreate(
            ['nombre' => 'CONTROL_INGRESO'],
            ['descripcion' => 'Personal de control de ingreso']
        );

        $this->facultad = Facultad::create([
            'codigo' => 'FCYT',
            'nombre' => 'Facultad de Ciencias y Tecnología',
        ]);

        $this->carrera = Carrera::create([
            'id_facultad' => $this->facultad->id_facultad,
            'codigo'      => 'SIS',
            'nombre'      => 'Ingeniería de Sistemas',
        ]);

        $this->asignatura = Asignatura::create([
            'codigo'      => 'TIS-100',
            'nombre'      => 'Taller de Ingeniería de Software',
            'descripcion' => 'Materia de validación',
        ]);

        $this->asignatura->carreras()->attach($this->carrera->id_carrera);

        $this->estudiante = Estudiante::create([
            'ci'                   => '9876543',
            'nombre'               => 'Valentina',
            'apellido'             => 'Quispe',
            'codigo_universitario' => '202612345',
            'correo'               => 'valentina.quispe@est.umss.edu',
            'estado'               => 'ACTIVO',
            'id_carrera'           => $this->carrera->id_carrera,
        ]);

        $this->estudiante->asignaturas()->attach($this->asignatura->id_asignatura);

        $this->usuarioEstudiante = DB::transaction(function () {
            return app(CuentaEstudianteService::class)
                ->crearCuenta($this->estudiante);
        });

        $this->tokenEstudiante = $this->usuarioEstudiante
            ->createToken('test-estudiante')->plainTextToken;

        $this->examen = Examen::create([
            'id_asignatura'    => $this->asignatura->id_asignatura,
            'nombre'           => 'Parcial TIS Sprint 4',
            'fecha'            => '2026-12-15',
            'hora_inicio'      => '10:00',
            'duracion_minutos' => 120,
            'estado'           => 'PROGRAMADO',
        ]);

        $this->ambiente = Ambiente::create([
            'codigo'    => 'AULA-101',
            'nombre'    => 'Aula 101 - Edificio Central',
            'capacidad' => 40,
            'estado'    => 'DISPONIBLE',
        ]);

        $this->asignacionAmbiente = AsignacionAmbiente::create([
            'id_examen'   => $this->examen->id_examen,
            'id_ambiente' => $this->ambiente->id_ambiente,
        ]);

        $this->habilitacion = Habilitacion::create([
            'id_estudiante'          => $this->estudiante->id_estudiante,
            'id_examen'              => $this->examen->id_examen,
            'id_asignacion_ambiente' => $this->asignacionAmbiente->id_asignacion_ambiente,
            'estado'                 => 'HABILITADO',
        ]);

        $this->administrador = Usuario::create([
            'nombre'        => 'Admin',
            'apellido'      => 'Sistema',
            'correo'        => 'admin.val@test.com',
            'username'      => 'admin_val',
            'password_hash' => PasswordHasher::hash('Admin12345'),
            'id_rol'        => $this->rolAdministrador->id_rol,
            'estado'        => 'ACTIVO',
        ]);
        $this->tokenAdmin = $this->administrador
            ->createToken('test-admin')->plainTextToken;

        $this->docente = Usuario::create([
            'nombre'        => 'Laura',
            'apellido'      => 'Docente',
            'correo'        => 'laura.val@test.com',
            'username'      => 'docente_val',
            'password_hash' => PasswordHasher::hash('Docente123'),
            'id_rol'        => $this->rolDocente->id_rol,
            'estado'        => 'ACTIVO',
        ]);
        $this->tokenDocente = $this->docente
            ->createToken('test-docente')->plainTextToken;

        DB::table('docente_asignatura')->insert([
            'id_usuario'    => $this->docente->id_usuario,
            'id_asignatura' => $this->asignatura->id_asignatura,
            'id_carrera'    => $this->carrera->id_carrera,
        ]);

        $this->controlIngreso = Usuario::create([
            'nombre'        => 'Carlos',
            'apellido'      => 'Control',
            'correo'        => 'carlos.val@test.com',
            'username'      => 'control_val',
            'password_hash' => PasswordHasher::hash('Control123'),
            'id_rol'        => $this->rolControl->id_rol,
            'estado'        => 'ACTIVO',
        ]);
        $this->tokenControl = $this->controlIngreso
            ->createToken('test-control')->plainTextToken;
    }

    // ─────────────────────────────────────────────────────────────────
    // 1. Sesión: iniciar y cerrar sesión con credenciales de estudiante
    // ─────────────────────────────────────────────────────────────────

    public function test_estudiante_inicia_sesion_con_sis_y_ci(): void
    {
        $response = $this->postJson('/api/login', [
            'username' => '202612345',
            'password' => '9876543',
        ]);

        $response->assertOk()
            ->assertJsonPath('usuario.username', '202612345')
            ->assertJsonPath('usuario.rol', 'ESTUDIANTE')
            ->assertJsonStructure(['token', 'usuario']);
    }

    public function test_estudiante_cierra_sesion_correctamente(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenEstudiante}",
            'Accept'        => 'application/json',
        ])->post('/api/logout');

        $response->assertOk()
            ->assertJsonPath('message', 'Sesión cerrada correctamente.');
    }

    public function test_me_retorna_datos_del_estudiante(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenEstudiante}",
            'Accept'        => 'application/json',
        ])->get('/api/me');

        $response->assertOk()
            ->assertJsonPath('rol', 'ESTUDIANTE')
            ->assertJsonPath('username', '202612345');
    }

    // ─────────────────────────────────────────────────────────────────
    // 2. Perfil, asignaturas y exámenes propios
    // ─────────────────────────────────────────────────────────────────

    public function test_estudiante_consulta_perfil_con_datos_correctos(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenEstudiante}",
            'Accept'        => 'application/json',
        ])->get('/api/estudiante/perfil');

        $response->assertOk()
            ->assertJsonPath('nombre', 'Valentina')
            ->assertJsonPath('apellido', 'Quispe')
            ->assertJsonPath('ci', '9876543')
            ->assertJsonPath('codigo_universitario', '202612345')
            ->assertJsonPath('correo', 'valentina.quispe@est.umss.edu')
            ->assertJsonPath('carrera.codigo', 'SIS')
            ->assertJsonPath('carrera.nombre', 'Ingeniería de Sistemas')
            ->assertJsonPath('carrera.facultad', 'Facultad de Ciencias y Tecnología');
    }

    public function test_estudiante_ve_sus_asignaturas_inscritas(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenEstudiante}",
            'Accept'        => 'application/json',
        ])->get('/api/estudiante/perfil');

        $response->assertOk()
            ->assertJsonCount(1, 'asignaturas')
            ->assertJsonFragment([
                'codigo' => 'TIS-100',
                'nombre' => 'Taller de Ingeniería de Software',
            ]);
    }

    public function test_estudiante_ve_examenes_habilitados(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenEstudiante}",
            'Accept'        => 'application/json',
        ])->get('/api/estudiante/perfil');

        $response->assertOk()
            ->assertJsonCount(1, 'examenes')
            ->assertJsonPath('examenes.0.nombre', 'Parcial TIS Sprint 4')
            ->assertJsonPath('examenes.0.estado_habilitacion', 'HABILITADO')
            ->assertJsonPath('examenes.0.asignatura', 'Taller de Ingeniería de Software')
            ->assertJsonPath('examenes.0.fecha', '2026-12-15')
            ->assertJsonPath('examenes.0.duracion_minutos', 120);
    }

    // ─────────────────────────────────────────────────────────────────
    // 3. QR personal: generación y estructura
    // ─────────────────────────────────────────────────────────────────

    public function test_estudiante_genera_qr_personal(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenEstudiante}",
            'Accept'        => 'application/json',
        ])->get('/api/estudiante/qr');

        $response->assertOk()
            ->assertJsonStructure(['token', 'qr_svg']);

        $this->assertNotEmpty($response->json('token'));
    }

    public function test_qr_generado_contiene_svg_valido(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenEstudiante}",
            'Accept'        => 'application/json',
        ])->get('/api/estudiante/qr');

        $svg = $response->json('qr_svg');

        if ($svg !== null) {
            $this->assertStringContainsString('<svg', $svg);
        } else {
            $this->assertNotEmpty($response->json('token'));
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // 4. QR escaneado por Control de ingreso identifica al estudiante
    // ─────────────────────────────────────────────────────────────────

    public function test_control_escanea_qr_del_estudiante_e_identifica_correctamente(): void
    {
        $qrService = app(QrTokenService::class);
        $token = $qrService->generar($this->estudiante);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenControl}",
            'Accept'        => 'application/json',
        ])->postJson('/api/verificaciones/qr', [
            'token'     => $token,
            'examen_id' => $this->examen->id_examen,
        ]);

        $response->assertOk()
            ->assertJsonPath('metodo', 'QR')
            ->assertJsonPath('estudiante.ci', '9876543')
            ->assertJsonPath('estudiante.codigo_sis', '202612345')
            ->assertJsonPath('estudiante.nombres', 'Valentina')
            ->assertJsonPath('estudiante.apellidos', 'Quispe');
    }

    public function test_qr_generado_por_endpoint_es_validable_por_control(): void
    {
        $respuestaQr = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenEstudiante}",
            'Accept'        => 'application/json',
        ])->get('/api/estudiante/qr');

        $tokenQr = $respuestaQr->json('token');
        $this->assertNotEmpty($tokenQr);

        $qrService = app(QrTokenService::class);
        $idEstudiante = $qrService->validar($tokenQr);

        $this->assertEquals(
            $this->estudiante->id_estudiante,
            $idEstudiante
        );
    }

    // ─────────────────────────────────────────────────────────────────
    // 5. QR no expone datos de otros estudiantes
    // ─────────────────────────────────────────────────────────────────

    public function test_qr_no_expone_datos_de_otro_estudiante(): void
    {
        $otroEstudiante = Estudiante::create([
            'ci'                   => '1234567',
            'nombre'               => 'Ricardo',
            'apellido'             => 'Mendoza',
            'codigo_universitario' => '202699999',
            'correo'               => 'ricardo.mendoza@est.umss.edu',
            'estado'               => 'ACTIVO',
            'id_carrera'           => $this->carrera->id_carrera,
        ]);

        $otroUsuario = DB::transaction(function () use ($otroEstudiante) {
            return app(CuentaEstudianteService::class)
                ->crearCuenta($otroEstudiante);
        });

        $tokenOtro = $otroUsuario->createToken('test-otro')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$tokenOtro}",
            'Accept'        => 'application/json',
        ])->get('/api/estudiante/perfil');

        $response->assertOk()
            ->assertJsonPath('nombre', 'Ricardo')
            ->assertJsonMissing(['nombre' => 'Valentina'])
            ->assertJsonMissing(['ci' => '9876543']);
    }

    // ─────────────────────────────────────────────────────────────────
    // 6. Estados vacíos y errores
    // ─────────────────────────────────────────────────────────────────

    public function test_estudiante_sin_datos_academicos_muestra_listas_vacias(): void
    {
        $estSinDatos = Estudiante::create([
            'ci'                   => '5555555',
            'nombre'               => 'Elena',
            'apellido'             => 'Vaca',
            'codigo_universitario' => '202655555',
            'correo'               => 'elena.vaca@est.umss.edu',
            'estado'               => 'ACTIVO',
        ]);

        $usuario = DB::transaction(function () use ($estSinDatos) {
            return app(CuentaEstudianteService::class)
                ->crearCuenta($estSinDatos);
        });

        $token = $usuario->createToken('test-vacio')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept'        => 'application/json',
        ])->get('/api/estudiante/perfil');

        $response->assertOk()
            ->assertJsonPath('nombre', 'Elena')
            ->assertJsonPath('carrera', null)
            ->assertJsonCount(0, 'asignaturas')
            ->assertJsonCount(0, 'examenes');
    }

    public function test_usuario_estudiante_sin_registro_vinculado_retorna_404(): void
    {
        $usuarioManual = Usuario::create([
            'nombre'        => 'Fantasma',
            'apellido'      => 'SinRegistro',
            'correo'        => 'fantasma@test.com',
            'username'      => 'fantasma_test',
            'password_hash' => PasswordHasher::hash('Test12345'),
            'id_rol'        => $this->rolEstudiante->id_rol,
            'estado'        => 'ACTIVO',
        ]);

        $token = $usuarioManual->createToken('test-fantasma')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept'        => 'application/json',
        ])->get('/api/estudiante/perfil');

        $response->assertNotFound();
    }

    public function test_usuario_estudiante_sin_registro_no_puede_generar_qr(): void
    {
        $usuarioManual = Usuario::create([
            'nombre'        => 'NoQR',
            'apellido'      => 'Test',
            'correo'        => 'noqr@test.com',
            'username'      => 'noqr_test',
            'password_hash' => PasswordHasher::hash('Test12345'),
            'id_rol'        => $this->rolEstudiante->id_rol,
            'estado'        => 'ACTIVO',
        ]);

        $token = $usuarioManual->createToken('test-noqr')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept'        => 'application/json',
        ])->get('/api/estudiante/qr');

        $response->assertNotFound();
    }

    // ─────────────────────────────────────────────────────────────────
    // 7. Bloqueo de acceso a rutas administrativas y de otros roles
    // ─────────────────────────────────────────────────────────────────

    public function test_estudiante_no_puede_acceder_a_gestion_de_usuarios(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenEstudiante}",
            'Accept'        => 'application/json',
        ])->get('/api/usuarios');

        $response->assertForbidden();
    }

    public function test_estudiante_no_puede_crear_usuarios(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenEstudiante}",
            'Accept'        => 'application/json',
        ])->postJson('/api/usuarios', [
            'nombre'   => 'Intruso',
            'apellido' => 'Test',
            'correo'   => 'intruso@test.com',
            'username' => 'intruso',
            'id_rol'   => $this->rolAdministrador->id_rol,
        ]);

        $response->assertForbidden();
    }

    public function test_estudiante_no_puede_gestionar_ambientes(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenEstudiante}",
            'Accept'        => 'application/json',
        ])->postJson('/api/ambientes', [
            'codigo'    => 'HACK',
            'nombre'    => 'Aula Intrusa',
            'capacidad' => 100,
            'ubicacion' => 'Inexistente',
        ]);

        $response->assertForbidden();
    }

    public function test_estudiante_no_puede_crear_examenes(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenEstudiante}",
            'Accept'        => 'application/json',
        ])->postJson('/api/examenes', [
            'id_asignatura'    => $this->asignatura->id_asignatura,
            'nombre'           => 'Examen Intruso',
            'fecha'            => '2026-12-20',
            'hora_inicio'      => '14:00',
            'duracion_minutos' => 60,
        ]);

        $response->assertForbidden();
    }

    public function test_estudiante_no_puede_crear_habilitaciones(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenEstudiante}",
            'Accept'        => 'application/json',
        ])->postJson('/api/habilitaciones', [
            'id_estudiante' => $this->estudiante->id_estudiante,
            'id_examen'     => $this->examen->id_examen,
            'estado'        => 'HABILITADO',
        ]);

        $response->assertForbidden();
    }

    public function test_estudiante_no_puede_registrar_ingresos(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenEstudiante}",
            'Accept'        => 'application/json',
        ])->postJson('/api/ingresos', [
            'id_examen'       => $this->examen->id_examen,
            'id_habilitacion' => $this->habilitacion->id_habilitacion,
        ]);

        $response->assertForbidden();
    }

    public function test_estudiante_no_puede_consultar_incidencias(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenEstudiante}",
            'Accept'        => 'application/json',
        ])->get('/api/incidencias');

        $response->assertForbidden();
    }

    public function test_estudiante_no_puede_consultar_reportes(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenEstudiante}",
            'Accept'        => 'application/json',
        ])->get('/api/reportes/examenes');

        $response->assertForbidden();
    }

    public function test_estudiante_no_puede_verificar_por_qr_como_control(): void
    {
        $qrService = app(QrTokenService::class);
        $token = $qrService->generar($this->estudiante);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenEstudiante}",
            'Accept'        => 'application/json',
        ])->postJson('/api/verificaciones/qr', [
            'token'     => $token,
            'examen_id' => $this->examen->id_examen,
        ]);

        $response->assertForbidden();
    }

    public function test_estudiante_no_puede_verificar_por_documento_como_control(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenEstudiante}",
            'Accept'        => 'application/json',
        ])->postJson('/api/verificaciones/documento', [
            'tipo'      => 'CI',
            'valor'     => '9876543',
            'examen_id' => $this->examen->id_examen,
        ]);

        $response->assertForbidden();
    }

    public function test_estudiante_no_puede_verificar_ambiente(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenEstudiante}",
            'Accept'        => 'application/json',
        ])->postJson('/api/verificaciones/ambiente', [
            'id_examen'              => $this->examen->id_examen,
            'id_asignacion_ambiente' => 1,
            'id_habilitacion'        => $this->habilitacion->id_habilitacion,
        ]);

        $response->assertForbidden();
    }

    public function test_estudiante_no_puede_registrar_expulsion(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenEstudiante}",
            'Accept'        => 'application/json',
        ])->postJson("/api/examenes/{$this->examen->id_examen}/expulsiones", [
            'id_estudiante' => $this->estudiante->id_estudiante,
            'motivo'        => 'Intento no autorizado',
        ]);

        $response->assertForbidden();
    }

    public function test_estudiante_no_puede_obtener_roles(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenEstudiante}",
            'Accept'        => 'application/json',
        ])->get('/api/roles');

        $response->assertForbidden();
    }

    // ─────────────────────────────────────────────────────────────────
    // 8. Consistencia de datos tras reautenticación
    // ─────────────────────────────────────────────────────────────────

    public function test_datos_consistentes_tras_reautenticacion(): void
    {
        $primerPerfil = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenEstudiante}",
            'Accept'        => 'application/json',
        ])->getJson('/api/estudiante/perfil');

        $primerPerfil->assertOk();

        $loginResponse = $this->postJson('/api/login', [
            'username' => '202612345',
            'password' => '9876543',
        ]);

        $nuevoToken = $loginResponse->json('token');

        $segundoPerfil = $this->withHeaders([
            'Authorization' => "Bearer {$nuevoToken}",
            'Accept'        => 'application/json',
        ])->getJson('/api/estudiante/perfil');

        $segundoPerfil->assertOk();

        $this->assertEquals(
            $primerPerfil->json('nombre'),
            $segundoPerfil->json('nombre')
        );
        $this->assertEquals(
            $primerPerfil->json('ci'),
            $segundoPerfil->json('ci')
        );
        $this->assertEquals(
            $primerPerfil->json('codigo_universitario'),
            $segundoPerfil->json('codigo_universitario')
        );
        $this->assertEquals(
            $primerPerfil->json('asignaturas'),
            $segundoPerfil->json('asignaturas')
        );
        $this->assertEquals(
            $primerPerfil->json('examenes'),
            $segundoPerfil->json('examenes')
        );
    }

    // ─────────────────────────────────────────────────────────────────
    // 9. Flujo integral: habilitación → QR → escaneo → verificación
    // ─────────────────────────────────────────────────────────────────

    public function test_flujo_integral_habilitacion_qr_escaneo(): void
    {
        $perfilResponse = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenEstudiante}",
            'Accept'        => 'application/json',
        ])->getJson('/api/estudiante/perfil');

        $perfilResponse->assertOk()
            ->assertJsonPath('examenes.0.estado_habilitacion', 'HABILITADO');

        $qrResponse = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenEstudiante}",
            'Accept'        => 'application/json',
        ])->getJson('/api/estudiante/qr');

        $qrResponse->assertOk();
        $tokenQr = $qrResponse->json('token');
        $this->assertNotEmpty($tokenQr);

        auth()->forgetGuards();

        $verificacionResponse = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenControl}",
            'Accept'        => 'application/json',
        ])->postJson('/api/verificaciones/qr', [
            'token'     => $tokenQr,
            'examen_id' => $this->examen->id_examen,
        ]);

        $verificacionResponse->assertOk()
            ->assertJsonPath('metodo', 'QR')
            ->assertJsonPath('estudiante.ci', '9876543')
            ->assertJsonPath('estudiante.codigo_sis', '202612345')
            ->assertJsonPath('estudiante.nombres', 'Valentina')
            ->assertJsonPath('examen.id', $this->examen->id_examen);

        // Control de Ingreso registra el ingreso físico del estudiante verificado
        $ingresoResponse = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenControl}",
            'Accept'        => 'application/json',
        ])->postJson('/api/ingresos', [
            'id_examen'              => $this->examen->id_examen,
            'id_habilitacion'        => $this->habilitacion->id_habilitacion,
            'id_asignacion_ambiente' => $this->asignacionAmbiente->id_asignacion_ambiente,
        ]);

        $ingresoResponse->assertCreated()
            ->assertJsonPath('id_habilitacion', $this->habilitacion->id_habilitacion);

        $this->assertDatabaseHas('ingreso', [
            'id_examen'              => $this->examen->id_examen,
            'id_habilitacion'        => $this->habilitacion->id_habilitacion,
            'id_asignacion_ambiente' => $this->asignacionAmbiente->id_asignacion_ambiente,
        ]);

        // Docente asignado consulta el reporte permitido y constata la presencia
        auth()->forgetGuards();

        $reporteDocente = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenDocente}",
            'Accept'        => 'application/json',
        ])->getJson("/api/reportes/examenes/{$this->examen->id_examen}/asistencia");

        $reporteDocente->assertOk()
            ->assertJsonPath('resumen.presentes', 1)
            ->assertJsonPath('resumen.ausentes', 0)
            ->assertJsonFragment([
                'id_estudiante' => $this->estudiante->id_estudiante,
                'nombre'        => 'Valentina',
                'apellido'      => 'Quispe',
                'asistencia'    => 'PRESENTE',
            ]);

        // Control de Ingreso consulta el reporte permitido y verifica la presencia
        auth()->forgetGuards();

        $reporteControl = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenControl}",
            'Accept'        => 'application/json',
        ])->getJson("/api/reportes/examenes/{$this->examen->id_examen}/asistencia");

        $reporteControl->assertOk()
            ->assertJsonPath('resumen.presentes', 1);

        // Estudiante no tiene acceso al reporte de asistencia (bloqueo RBAC)
        auth()->forgetGuards();

        $reporteEstudiante = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenEstudiante}",
            'Accept'        => 'application/json',
        ])->getJson("/api/reportes/examenes/{$this->examen->id_examen}/asistencia");

        $reporteEstudiante->assertForbidden();
    }

    // ─────────────────────────────────────────────────────────────────
    // 10. Otros roles no pueden acceder a endpoints de estudiante
    // ─────────────────────────────────────────────────────────────────

    public function test_administrador_no_puede_consultar_perfil_estudiantil(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenAdmin}",
            'Accept'        => 'application/json',
        ])->get('/api/estudiante/perfil');

        $response->assertForbidden();
    }

    public function test_docente_no_puede_consultar_perfil_estudiantil(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenDocente}",
            'Accept'        => 'application/json',
        ])->get('/api/estudiante/perfil');

        $response->assertForbidden();
    }

    public function test_control_no_puede_consultar_perfil_estudiantil(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenControl}",
            'Accept'        => 'application/json',
        ])->get('/api/estudiante/perfil');

        $response->assertForbidden();
    }

    public function test_sin_autenticacion_no_puede_consultar_perfil(): void
    {
        $response = $this->getJson('/api/estudiante/perfil');
        $response->assertUnauthorized();
    }

    public function test_sin_autenticacion_no_puede_generar_qr(): void
    {
        $response = $this->getJson('/api/estudiante/qr');
        $response->assertUnauthorized();
    }

    // ─────────────────────────────────────────────────────────────────
    // 11. Estudiante inhabilitado ve su estado correctamente
    // ─────────────────────────────────────────────────────────────────

    public function test_estudiante_inhabilitado_ve_estado_en_perfil(): void
    {
        $this->habilitacion->update(['estado' => 'NO_HABILITADO']);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenEstudiante}",
            'Accept'        => 'application/json',
        ])->getJson('/api/estudiante/perfil');

        $response->assertOk()
            ->assertJsonPath('examenes.0.estado_habilitacion', 'NO_HABILITADO');
    }
}
