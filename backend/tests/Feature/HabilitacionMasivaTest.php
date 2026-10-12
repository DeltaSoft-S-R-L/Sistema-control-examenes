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

class HabilitacionMasivaTest extends TestCase
{
    protected function setUp(): void
{
    parent::setUp();

    $connection = DB::connection();
    $database = $connection->selectOne(
        'SELECT current_database() AS nombre'
    )->nombre;

    if (
        $connection->getDriverName() !== 'pgsql' ||
        $database !== 'control_examenes_pruebas'
    ) {
        throw new RuntimeException(
            'Pruebas detenidas: la conexion no es la base autorizada.'
        );
    }

    DB::beginTransaction();
}

protected function tearDown(): void
{
    try {
        if (DB::connection()->transactionLevel() > 0) {
            DB::rollBack();
        }
    } finally {
        parent::tearDown();
    }
}

    public function test_conexion_segura_a_base_de_pruebas(): void
    {
        $this->assertSame(
            'control_examenes_pruebas',
            DB::connection()->getDatabaseName()
        );
    }
    public function test_habilita_dos_estudiantes_en_una_sola_solicitud(): void
{
    $sufijo = strtolower(\Illuminate\Support\Str::random(10));

    // Crear un usuario con permiso para gestionar habilitaciones.
    $permiso = Permiso::firstOrCreate(
        ['nombre' => 'GESTIONAR_HABILITACIONES'],
        ['descripcion' => 'Gestionar habilitaciones de estudiantes']
    );

    $rol = Rol::create([
        'nombre' => 'TEST_HAB_' . $sufijo,
        'descripcion' => 'Rol temporal para pruebas',
    ]);

    $rol->permisos()->attach($permiso->id_permiso);

    $usuario = Usuario::create([
        'nombre' => 'Usuario',
        'apellido' => 'Prueba',
        'correo' => "hab_{$sufijo}@example.com",
        'username' => "hab_{$sufijo}",
        'password_hash' => PasswordHasher::hash('Password123'),
        'id_rol' => $rol->id_rol,
        'estado' => 'ACTIVO',
    ]);

    $token = $usuario->createToken('test-habilitacion')->plainTextToken;

    // Crear los datos académicos temporales.
    $facultad = Facultad::create([
        'codigo' => 'F' . $sufijo,
        'nombre' => 'Facultad de Prueba',
    ]);

    $carrera = Carrera::create([
        'id_facultad' => $facultad->id_facultad,
        'codigo' => 'C' . $sufijo,
        'nombre' => 'Carrera de Prueba',
    ]);

    $asignatura = Asignatura::create([
        'codigo' => 'A' . $sufijo,
        'nombre' => 'Asignatura de Prueba',
        'descripcion' => 'Asignatura temporal',
    ]);

    $examen = Examen::create([
        'id_asignatura' => $asignatura->id_asignatura,
        'nombre' => 'Examen de Prueba',
        'fecha' => '2026-10-20',
        'hora_inicio' => '08:00',
        'duracion_minutos' => 90,
        'estado' => 'PROGRAMADO',
    ]);

    $estudiantes = [];

    foreach ([1, 2] as $numero) {
        $estudiantes[] = Estudiante::create([
            'ci' => $sufijo . $numero,
            'nombre' => 'Estudiante',
            'apellido' => "Prueba {$numero}",
            'codigo_universitario' => $sufijo . $numero,
            'id_carrera' => $carrera->id_carrera,
            'correo' => "est_{$sufijo}_{$numero}@example.com",
            'estado' => 'ACTIVO',
        ]);
    }

    // Solicitar la habilitación masiva.
    $response = $this->postJson(
        '/api/habilitaciones/masiva',
        [
            'id_examen' => $examen->id_examen,
            'estudiantes' => [
                $estudiantes[0]->id_estudiante,
                $estudiantes[1]->id_estudiante,
            ],
            'estado' => 'HABILITADO',
        ],
        [
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
        ]
    );

    // Verificar el resultado.
    $response->assertStatus(201)
        ->assertJsonPath('total', 2);

    foreach ($estudiantes as $estudiante) {
        $this->assertDatabaseHas('habilitacion', [
            'id_examen' => $examen->id_examen,
            'id_estudiante' => $estudiante->id_estudiante,
            'estado' => 'HABILITADO',
        ]);
    }
    // Intentar habilitar nuevamente a los mismos estudiantes.
$respuestaDuplicada = $this->postJson(
    '/api/habilitaciones/masiva',
    [
        'id_examen' => $examen->id_examen,
        'estudiantes' => [
            $estudiantes[0]->id_estudiante,
            $estudiantes[1]->id_estudiante,
        ],
        'estado' => 'HABILITADO',
    ],
    [
        'Authorization' => "Bearer {$token}",
        'Accept' => 'application/json',
    ]
);

$respuestaDuplicada->assertStatus(422);

// Deben seguir existiendo únicamente las dos habilitaciones originales.
$this->assertSame(
    2,
    Habilitacion::where('id_examen', $examen->id_examen)->count()
);
// Rechazar estudiantes repetidos dentro de una misma solicitud.
$respuestaRepetidos = $this->postJson(
    '/api/habilitaciones/masiva',
    [
        'id_examen' => $examen->id_examen,
        'estudiantes' => [
            $estudiantes[0]->id_estudiante,
            $estudiantes[0]->id_estudiante,
        ],
        'estado' => 'HABILITADO',
    ],
    [
        'Authorization' => "Bearer {$token}",
        'Accept' => 'application/json',
    ]
);

$respuestaRepetidos->assertStatus(422);

// La solicitud rechazada no debe agregar habilitaciones.
$this->assertSame(
    2,
    Habilitacion::where('id_examen', $examen->id_examen)->count()
);
// Rechazar una solicitud que incluye un estudiante inexistente.
$idInexistente = Estudiante::max('id_estudiante') + 1000000;

$respuestaInexistente = $this->postJson(
    '/api/habilitaciones/masiva',
    [
        'id_examen' => $examen->id_examen,
        'estudiantes' => [
            $estudiantes[0]->id_estudiante,
            $idInexistente,
        ],
        'estado' => 'HABILITADO',
    ],
    [
        'Authorization' => "Bearer {$token}",
        'Accept' => 'application/json',
    ]
);

$respuestaInexistente->assertStatus(422);

// No deben crearse habilitaciones adicionales.
$this->assertSame(
    2,
    Habilitacion::where('id_examen', $examen->id_examen)->count()
);
// Crear un usuario sin permisos de habilitación.
$rolSinPermiso = Rol::create([
    'nombre' => 'TEST_SIN_PERMISO_' . $sufijo,
    'descripcion' => 'Rol temporal sin permisos',
]);

$usuarioSinPermiso = Usuario::create([
    'nombre' => 'Usuario',
    'apellido' => 'Sin Permiso',
    'correo' => "sin_permiso_{$sufijo}@example.com",
    'username' => "sin_permiso_{$sufijo}",
    'password_hash' => PasswordHasher::hash('Password123'),
    'id_rol' => $rolSinPermiso->id_rol,
    'estado' => 'ACTIVO',
]);

$tokenSinPermiso = $usuarioSinPermiso
    ->createToken('test-sin-permiso')
    ->plainTextToken;
    $this->assertFalse(
    $usuarioSinPermiso->tienePermiso('GESTIONAR_HABILITACIONES')
);
// Limpiar el usuario autenticado de las solicitudes anteriores.
auth('sanctum')->forgetUser();
auth()->forgetUser();
$respuestaSinPermiso = $this->postJson(
    '/api/habilitaciones/masiva',
    [
        'id_examen' => $examen->id_examen,
        'estudiantes' => [
            $estudiantes[0]->id_estudiante,
        ],
        'estado' => 'HABILITADO',
    ],
    [
        'Authorization' => "Bearer {$tokenSinPermiso}",
        'Accept' => 'application/json',
    ]
);

// El usuario no tiene autorización para utilizar este endpoint.
$respuestaSinPermiso->assertStatus(403);

// No deben registrarse habilitaciones adicionales.
$this->assertSame(
    2,
    Habilitacion::where('id_examen', $examen->id_examen)->count()
);
}
}
