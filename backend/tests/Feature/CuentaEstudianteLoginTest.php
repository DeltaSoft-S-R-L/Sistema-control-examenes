<?php

namespace Tests\Feature;

use App\Models\Estudiante;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\CuentaEstudianteService;
use App\Utilities\PasswordHasher;
use Illuminate\Support\Facades\DB;
use Tests\Support\SQLiteSeguroTestCase;
use App\Models\Facultad;
use App\Models\Carrera;
use App\Mail\BienvenidaEstudianteMail;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use App\Models\Asignatura;
use Illuminate\Http\UploadedFile;

class CuentaEstudianteLoginTest extends SQLiteSeguroTestCase
{
    public function test_crea_cuenta_para_estudiante_activo(): void
    {
        Rol::firstOrCreate(
    ['nombre' => 'ESTUDIANTE'],
    ['descripcion' => 'Estudiante del sistema']
);

        $estudiante = Estudiante::create([
            'ci' => '12345678',
            'nombre' => 'Carlos',
            'apellido' => 'Lopez',
            'codigo_universitario' => '202600123',
            'correo' => 'carlos.prueba@example.com',
            'estado' => 'ACTIVO',
        ]);

        $usuario = DB::transaction(function () use ($estudiante) {
            return app(CuentaEstudianteService::class)
                ->crearCuenta($estudiante);
        });

        $this->assertInstanceOf(Usuario::class, $usuario);
        $this->assertSame('202600123', $usuario->username);
        $this->assertSame('ACTIVO', $usuario->estado);

        $this->assertTrue(
            PasswordHasher::verify('12345678', $usuario->password_hash)
        );

        $this->assertNotSame(
            '12345678',
            $usuario->password_hash
        );

        $this->assertSame(
            $usuario->id_usuario,
            $estudiante->fresh()->id_usuario
        );

        $this->assertSame(
            'ESTUDIANTE',
            Rol::findOrFail($usuario->id_rol)->nombre
        );
    }


public function test_estudiante_puede_iniciar_sesion_con_sis_y_ci(): void
{
    Rol::firstOrCreate(
        ['nombre' => 'ESTUDIANTE'],
        ['descripcion' => 'Estudiante del sistema']
    );

    $estudiante = Estudiante::create([
        'ci' => '87654321',
        'nombre' => 'Maria',
        'apellido' => 'Gomez',
        'codigo_universitario' => '202600456',
        'correo' => 'maria.prueba@example.com',
        'estado' => 'ACTIVO',
    ]);

    DB::transaction(function () use ($estudiante) {
        app(CuentaEstudianteService::class)
            ->crearCuenta($estudiante);
    });

    $response = $this->postJson('/api/login', [
        'username' => '202600456',
        'password' => '87654321',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'token',
            'usuario' => [
                'id',
                'nombre',
                'apellido',
                'username',
                'correo',
                'rol',
            ],
        ])
        ->assertJson([
            'usuario' => [
                'username' => '202600456',
                'rol' => 'ESTUDIANTE',
            ],
        ]);

    $this->assertNotEmpty($response->json('token'));
}


public function test_estudiante_no_puede_iniciar_sesion_con_ci_incorrecto(): void
{
    Rol::firstOrCreate(
        ['nombre' => 'ESTUDIANTE'],
        ['descripcion' => 'Estudiante del sistema']
    );

    $estudiante = Estudiante::create([
        'ci' => '76543210',
        'nombre' => 'Pedro',
        'apellido' => 'Ramirez',
        'codigo_universitario' => '202600789',
        'correo' => 'pedro.prueba@example.com',
        'estado' => 'ACTIVO',
    ]);

    DB::transaction(function () use ($estudiante) {
        app(CuentaEstudianteService::class)
            ->crearCuenta($estudiante);
    });

    $response = $this->postJson('/api/login', [
        'username' => '202600789',
        'password' => 'CI_INCORRECTO',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['username']);

    $this->assertEmpty($response->json('token'));
}


public function test_no_crea_cuentas_duplicadas_para_estudiante(): void
{
    Rol::firstOrCreate(
        ['nombre' => 'ESTUDIANTE'],
        ['descripcion' => 'Estudiante del sistema']
    );

    $estudiante = Estudiante::create([
        'ci' => '65432109',
        'nombre' => 'Ana',
        'apellido' => 'Flores',
        'codigo_universitario' => '202600999',
        'correo' => 'ana.prueba@example.com',
        'estado' => 'ACTIVO',
    ]);

    $servicio = app(CuentaEstudianteService::class);

    $primeraCuenta = DB::transaction(function () use ($servicio, $estudiante) {
        return $servicio->crearCuenta($estudiante);
    });

    $segundaCuenta = DB::transaction(function () use ($servicio, $estudiante) {
        return $servicio->crearCuenta($estudiante);
    });

    $this->assertInstanceOf(Usuario::class, $primeraCuenta);

    $this->assertNull($segundaCuenta);

    $this->assertSame(
        1,
        Usuario::where('username', '202600999')->count()
    );

    $this->assertSame(
        $primeraCuenta->id_usuario,
        $estudiante->fresh()->id_usuario
    );
}


public function test_no_crea_cuenta_para_estudiante_inactivo(): void
{
    Rol::firstOrCreate(
        ['nombre' => 'ESTUDIANTE'],
        ['descripcion' => 'Estudiante del sistema']
    );

    $estudiante = Estudiante::create([
        'ci' => '54321098',
        'nombre' => 'Luis',
        'apellido' => 'Vargas',
        'codigo_universitario' => '202600888',
        'correo' => 'luis.prueba@example.com',
        'estado' => 'INACTIVO',
    ]);

    $usuario = DB::transaction(function () use ($estudiante) {
        return app(CuentaEstudianteService::class)
            ->crearCuenta($estudiante);
    });

    $this->assertNull($usuario);

    $this->assertNull(
        $estudiante->fresh()->id_usuario
    );

    $this->assertSame(
        0,
        Usuario::where('username', '202600888')->count()
    );
}


public function test_registro_manual_crea_cuenta_y_envia_correo(): void
{
    Mail::fake();

    $rolAdministrador = Rol::firstOrCreate(
        ['nombre' => 'ADMINISTRADOR'],
        ['descripcion' => 'Administrador del sistema']
    );

    Rol::firstOrCreate(
        ['nombre' => 'ESTUDIANTE'],
        ['descripcion' => 'Estudiante del sistema']
    );

    $administrador = Usuario::create([
        'nombre' => 'Admin',
        'apellido' => 'Prueba',
        'correo' => 'admin.prueba@example.com',
        'username' => 'admin_prueba',
        'password_hash' => PasswordHasher::hash('Admin12345'),
        'id_rol' => $rolAdministrador->id_rol,
        'estado' => 'ACTIVO',
    ]);

    $facultad = Facultad::create([
        'codigo' => 'FTEST',
        'nombre' => 'Facultad de Prueba',
    ]);

    $carrera = Carrera::create([
        'id_facultad' => $facultad->id_facultad,
        'codigo' => 'CTEST',
        'nombre' => 'Carrera de Prueba',
    ]);

    Sanctum::actingAs($administrador);

    $response = $this->postJson('/api/estudiantes', [
        'ci' => '45678912',
        'nombre' => 'Laura',
        'apellido' => 'Mendoza',
        'codigo_universitario' => '202600555',
        'correo' => 'laura.prueba@example.com',
        'id_carrera' => $carrera->id_carrera,
        'asignaturas' => [],
        'estado' => 'ACTIVO',
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'cuenta_creada' => true,
            'correo_enviado' => true,
        ]);

    $estudiante = Estudiante::where(
        'codigo_universitario',
        '202600555'
    )->firstOrFail();

    $usuario = Usuario::where(
        'username',
        '202600555'
    )->firstOrFail();

    $this->assertSame(
        $usuario->id_usuario,
        $estudiante->id_usuario
    );

    $this->assertTrue(
        PasswordHasher::verify('45678912', $usuario->password_hash)
    );

    $this->assertSame(
        'ESTUDIANTE',
        $usuario->rol->nombre
    );

    Mail::assertSent(
        BienvenidaEstudianteMail::class,
        function ($mail) {
            return $mail->hasTo('laura.prueba@example.com');
        }
    );

    Mail::assertSent(BienvenidaEstudianteMail::class, 1);
}


public function test_importacion_csv_crea_cuenta_y_envia_correo(): void
{
    Mail::fake();

    $rolAdministrador = Rol::firstOrCreate(
        ['nombre' => 'ADMINISTRADOR'],
        ['descripcion' => 'Administrador del sistema']
    );

    Rol::firstOrCreate(
        ['nombre' => 'ESTUDIANTE'],
        ['descripcion' => 'Estudiante del sistema']
    );

    $administrador = Usuario::create([
        'nombre' => 'Admin',
        'apellido' => 'CSV',
        'correo' => 'admin.csv@example.com',
        'username' => 'admin_csv',
        'password_hash' => PasswordHasher::hash('Admin12345'),
        'id_rol' => $rolAdministrador->id_rol,
        'estado' => 'ACTIVO',
    ]);

    $facultad = Facultad::create([
        'codigo' => 'FCSV',
        'nombre' => 'Facultad CSV',
    ]);

    $carrera = Carrera::create([
        'id_facultad' => $facultad->id_facultad,
        'codigo' => 'CCSV',
        'nombre' => 'Carrera CSV',
    ]);

    $asignatura = Asignatura::create([
        'codigo' => 'MATCSV',
        'nombre' => 'Matematica CSV',
        'descripcion' => 'Asignatura para prueba CSV',
    ]);

    $asignatura->carreras()->attach($carrera->id_carrera);

    Sanctum::actingAs($administrador);

    $contenidoCsv = implode("\n", [
        'ci,codigo_universitario,nombre,apellido,correo,estado,carrera,asignaturas',
        '78945612,202600777,Pedro,Rojas,pedro.csv@example.com,ACTIVO,CCSV,MATCSV',
    ]) . "\n";

    $rutaTemporal = tempnam(sys_get_temp_dir(), 'csv_');

    try {
        file_put_contents($rutaTemporal, $contenidoCsv);

        $archivo = new UploadedFile(
            $rutaTemporal,
            'estudiantes.csv',
            'text/csv',
            null,
            true
        );

        $response = $this->postJson(
            '/api/estudiantes/importar',
            ['archivo' => $archivo]
        );

        $response->assertOk()
            ->assertJson([
                'importados' => 1,
                'errores' => [],
            ]);

        $estudiante = Estudiante::where(
            'codigo_universitario',
            '202600777'
        )->firstOrFail();

        $usuario = Usuario::where(
            'username',
            '202600777'
        )->firstOrFail();

        $this->assertSame(
            $usuario->id_usuario,
            $estudiante->id_usuario
        );

        $this->assertSame(
            'ESTUDIANTE',
            $usuario->rol->nombre
        );

        $this->assertTrue(
            PasswordHasher::verify(
                '78945612',
                $usuario->password_hash
            )
        );

        $this->assertTrue(
            $estudiante->asignaturas()
                ->where('asignatura.id_asignatura', $asignatura->id_asignatura)
                ->exists()
        );

        Mail::assertSent(
            BienvenidaEstudianteMail::class,
            fn ($mail) => $mail->hasTo('pedro.csv@example.com')
        );

        Mail::assertSent(BienvenidaEstudianteMail::class, 1);
    } finally {
        if (file_exists($rutaTemporal)) {
            unlink($rutaTemporal);
        }
    }
}


public function test_importar_csv_dos_veces_no_duplica_cuenta_ni_correo(): void
{
    Mail::fake();

    $rolAdministrador = Rol::firstOrCreate(
        ['nombre' => 'ADMINISTRADOR'],
        ['descripcion' => 'Administrador del sistema']
    );

    Rol::firstOrCreate(
        ['nombre' => 'ESTUDIANTE'],
        ['descripcion' => 'Estudiante del sistema']
    );

    $administrador = Usuario::create([
        'nombre' => 'Admin',
        'apellido' => 'Duplicados',
        'correo' => 'admin.duplicados@example.com',
        'username' => 'admin_duplicados',
        'password_hash' => PasswordHasher::hash('Admin12345'),
        'id_rol' => $rolAdministrador->id_rol,
        'estado' => 'ACTIVO',
    ]);

    $facultad = Facultad::create([
        'codigo' => 'FDUP',
        'nombre' => 'Facultad Duplicados',
    ]);

    $carrera = Carrera::create([
        'id_facultad' => $facultad->id_facultad,
        'codigo' => 'CDUP',
        'nombre' => 'Carrera Duplicados',
    ]);

    $asignatura = Asignatura::create([
        'codigo' => 'MATDUP',
        'nombre' => 'Matematica Duplicados',
        'descripcion' => 'Prueba de importacion repetida',
    ]);

    $asignatura->carreras()->attach($carrera->id_carrera);

    Sanctum::actingAs($administrador);

    $contenidoCsv = implode("\n", [
        'ci,codigo_universitario,nombre,apellido,correo,estado,carrera,asignaturas',
        '98765432,2026008888,Ana,Lopez,ana.duplicados@example.com,ACTIVO,CDUP,MATDUP',
    ]) . "\n";

    $rutaTemporal = tempnam(sys_get_temp_dir(), 'csv_dup_');

    try {
        file_put_contents($rutaTemporal, $contenidoCsv);

        // Primera importacion
        $primeraRespuesta = $this->postJson(
            '/api/estudiantes/importar',
            [
                'archivo' => new UploadedFile(
                    $rutaTemporal,
                    'estudiantes.csv',
                    'text/csv',
                    null,
                    true
                ),
            ]
        );

        $primeraRespuesta->assertOk()->assertJson([
            'importados' => 1,
            'errores' => [],
        ]);

        // Segunda importacion del mismo estudiante
        $segundaRespuesta = $this->postJson(
            '/api/estudiantes/importar',
            [
                'archivo' => new UploadedFile(
                    $rutaTemporal,
                    'estudiantes.csv',
                    'text/csv',
                    null,
                    true
                ),
            ]
        );

        $segundaRespuesta->assertOk()->assertJson([
            'importados' => 1,
            'errores' => [],
        ]);

        $this->assertSame(
            1,
            Estudiante::where(
                'codigo_universitario',
                '2026008888'
            )->count()
        );

        $this->assertSame(
            1,
            Usuario::where(
                'username',
                '2026008888'
            )->count()
        );

        $estudiante = Estudiante::where(
            'codigo_universitario',
            '2026008888'
        )->firstOrFail();

        $usuario = Usuario::where(
            'username',
            '2026008888'
        )->firstOrFail();

        $this->assertSame(
            $usuario->id_usuario,
            $estudiante->id_usuario
        );

        $this->assertSame(
            1,
            $estudiante->asignaturas()->count()
        );

        Mail::assertSent(
            BienvenidaEstudianteMail::class,
            fn ($mail) => $mail->hasTo(
                'ana.duplicados@example.com'
            )
        );

        // Solo un correo entre ambas importaciones
        Mail::assertSent(BienvenidaEstudianteMail::class, 1);
    } finally {
        if (file_exists($rutaTemporal)) {
            unlink($rutaTemporal);
        }
    }
}


public function test_importar_csv_inactivo_no_crea_cuenta_ni_correo(): void
{
    Mail::fake();

    $rolAdministrador = Rol::firstOrCreate(
        ['nombre' => 'ADMINISTRADOR'],
        ['descripcion' => 'Administrador del sistema']
    );

    Rol::firstOrCreate(
        ['nombre' => 'ESTUDIANTE'],
        ['descripcion' => 'Estudiante del sistema']
    );

    $administrador = Usuario::create([
        'nombre' => 'Admin',
        'apellido' => 'Inactivos',
        'correo' => 'admin.inactivos@example.com',
        'username' => 'admin_inactivos',
        'password_hash' => PasswordHasher::hash('Admin12345'),
        'id_rol' => $rolAdministrador->id_rol,
        'estado' => 'ACTIVO',
    ]);

    $facultad = Facultad::create([
        'codigo' => 'FINAC',
        'nombre' => 'Facultad Inactivos',
    ]);

    $carrera = Carrera::create([
        'id_facultad' => $facultad->id_facultad,
        'codigo' => 'CINAC',
        'nombre' => 'Carrera Inactivos',
    ]);

    $asignatura = Asignatura::create([
        'codigo' => 'MATINAC',
        'nombre' => 'Matematica Inactivos',
        'descripcion' => 'Prueba CSV de estudiantes inactivos',
    ]);

    $asignatura->carreras()->attach($carrera->id_carrera);

    Sanctum::actingAs($administrador);

    $contenidoCsv = implode("\n", [
        'ci,codigo_universitario,nombre,apellido,correo,estado,carrera,asignaturas',
        '65432198,2026009999,Juan,Perez,juan.inactivo@example.com,INACTIVO,CINAC,MATINAC',
    ]) . "\n";

    $rutaTemporal = tempnam(sys_get_temp_dir(), 'csv_inactivo_');

    try {
        file_put_contents($rutaTemporal, $contenidoCsv);

        $archivo = new UploadedFile(
            $rutaTemporal,
            'estudiantes.csv',
            'text/csv',
            null,
            true
        );

        $response = $this->postJson(
            '/api/estudiantes/importar',
            ['archivo' => $archivo]
        );

        $response->assertOk()->assertJson([
            'importados' => 1,
            'errores' => [],
        ]);

        $estudiante = Estudiante::where(
            'codigo_universitario',
            '2026009999'
        )->firstOrFail();

        $this->assertSame(
            'INACTIVO',
            $estudiante->estado
        );

        $this->assertNull($estudiante->id_usuario);

        $this->assertSame(
            0,
            Usuario::where(
                'username',
                '2026009999'
            )->count()
        );

        $this->assertTrue(
            $estudiante->asignaturas()
                ->where(
                    'asignatura.id_asignatura',
                    $asignatura->id_asignatura
                )
                ->exists()
        );

        Mail::assertNothingSent();
    } finally {
        if (file_exists($rutaTemporal)) {
            unlink($rutaTemporal);
        }
    }
}


public function test_csv_con_correo_invalido_no_crea_estudiante_ni_cuenta(): void
{
    Mail::fake();

    $rolAdministrador = Rol::firstOrCreate(
        ['nombre' => 'ADMINISTRADOR'],
        ['descripcion' => 'Administrador del sistema']
    );

    $administrador = Usuario::create([
        'nombre' => 'Admin',
        'apellido' => 'Validacion',
        'correo' => 'admin.validacion@example.com',
        'username' => 'admin_validacion',
        'password_hash' => PasswordHasher::hash('Admin12345'),
        'id_rol' => $rolAdministrador->id_rol,
        'estado' => 'ACTIVO',
    ]);

    Sanctum::actingAs($administrador);

    $contenidoCsv = implode("\n", [
        'ci,codigo_universitario,nombre,apellido,correo,estado,carrera,asignaturas',
        '12345678,202600111,Pedro,Lopez,correo-invalido,ACTIVO,CCSV,MATCSV',
    ]) . "\n";

    $rutaTemporal = tempnam(sys_get_temp_dir(), 'csv_error_');

    try {
        file_put_contents($rutaTemporal, $contenidoCsv);

        $archivo = new UploadedFile(
            $rutaTemporal,
            'estudiantes.csv',
            'text/csv',
            null,
            true
        );

        $response = $this->postJson(
            '/api/estudiantes/importar',
            ['archivo' => $archivo]
        );

        $response->assertOk()
            ->assertJsonPath('importados', 0)
            ->assertJsonCount(1, 'errores')
            ->assertJsonPath('errores.0.fila', 2);

        $this->assertSame(
            0,
            Estudiante::where(
                'codigo_universitario',
                '202600111'
            )->count()
        );

        $this->assertSame(
            0,
            Usuario::where(
                'username',
                '202600111'
            )->count()
        );

        Mail::assertNothingSent();
    } finally {
        if (file_exists($rutaTemporal)) {
            unlink($rutaTemporal);
        }
    }
}

}
