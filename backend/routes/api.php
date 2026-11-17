<?php

use App\Http\Controllers\Api\AmbienteController;
use App\Http\Controllers\Api\AsignaturaController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CarreraController;
use App\Http\Controllers\Api\EstudianteController;
use App\Http\Controllers\Api\ExamenController;
use App\Http\Controllers\Api\FacultadController;
use App\Http\Controllers\Api\HabilitacionController;
use App\Http\Controllers\Api\IncidenciaController;
use App\Http\Controllers\Api\IngresoController;
use App\Http\Controllers\Api\UsuarioController;
use App\Http\Controllers\Api\VerificacionController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DocenteAsignaturaController;
use App\Models\Rol;
use App\Http\Controllers\Api\NormaController;
use App\Http\Controllers\Api\PasswordSetupController;
use App\Http\Controllers\Api\PerfilEstudianteController;
use App\Http\Controllers\Api\EstudianteFotoController;
use App\Http\Controllers\Api\ReporteController;

/*
|--------------------------------------------------------------------------
| API Routes — Sistema de Control de Exámenes
|--------------------------------------------------------------------------
*/

// Health check (público)
Route::get('/health', fn () => response()->json([
    'status'  => 'ok',
    'service' => 'sistema-control-examenes',
    'version' => '1.0.0',
]));

// Autenticación (público)
Route::post('/login', [AuthController::class, 'login']);

// Configuración de contraseña mediante enlace de correo (público)
Route::middleware('throttle:6,1')->group(function () {
    Route::post('/configurar-contrasena/verificar', [
        PasswordSetupController::class,
        'verificar'
    ]);

    Route::post('/configurar-contrasena', [
        PasswordSetupController::class,
        'configurar'
    ]);
});

// Las fotos locales se entregan con enlaces temporales firmados; el archivo
// permanece en almacenamiento privado y no requiere exponerlo como recurso público.
Route::get('/estudiante/foto/{id_estudiante}/archivo', [EstudianteFotoController::class, 'archivo'])
    ->middleware('signed')
    ->name('estudiante.foto.archivo');

// Rutas protegidas con Sanctum y verificación de cuenta activa
Route::middleware(['auth:sanctum', 'active'])->group(function () {

    // Sesión
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    // Perfil académico del estudiante autenticado
Route::get('/estudiante/perfil', [
    PerfilEstudianteController::class,
    'mostrar'
])->middleware('role:ESTUDIANTE');
Route::get('/estudiante/qr', [
    PerfilEstudianteController::class,
    'generarQr'
])->middleware('role:ESTUDIANTE');
// Estado de la fotografía del estudiante autenticado
Route::get('/estudiante/foto', [
    EstudianteFotoController::class,
    'mostrar'
])->middleware('role:ESTUDIANTE');
Route::post('/estudiante/foto', [EstudianteFotoController::class, 'subir'])
    ->middleware('role:ESTUDIANTE');

    // Materias del docente autenticado
Route::get('/docente/mis-materias', [
    DocenteAsignaturaController::class,
    'misMaterias'
]);

    /*
    |--------------------------------------------------------------------------
    | Usuarios
    |--------------------------------------------------------------------------
    */
    Route::middleware('permission:GESTIONAR_USUARIOS')->group(function () {
        Route::get('/roles', fn () => response()->json(
            Rol::query()->where('nombre', '<>', 'ESTUDIANTE')->orderBy('id_rol')->get(['id_rol', 'nombre'])
        ));
        Route::get('/usuarios', [UsuarioController::class, 'index']);
        Route::post('/usuarios', [UsuarioController::class, 'store']);
        Route::get('/usuarios/{id}', [UsuarioController::class, 'show']);
        Route::put('/usuarios/{usuario}', [UsuarioController::class, 'update']);
        Route::delete('/usuarios/{id}', [UsuarioController::class, 'destroy']);

        // Asignación de materias a docentes
        Route::get('/usuarios/{usuario}/materias', [DocenteAsignaturaController::class, 'index']);
        Route::put('/usuarios/{usuario}/materias', [DocenteAsignaturaController::class, 'update']);

        // Revocación de acceso de usuarios
        Route::match(['patch', 'post'], '/usuarios/{id}/revocar', [UsuarioController::class, 'revocar']);
    });

    /*
    |--------------------------------------------------------------------------
    | Estudiantes
    |--------------------------------------------------------------------------
    */
    Route::get('/estudiantes', [EstudianteController::class, 'index']);
    Route::get('/estudiantes/{id}/qr', [EstudianteController::class, 'generarQr'])
        ->middleware('role:ADMINISTRADOR');
    Route::get('/estudiantes/{estudiante}', [EstudianteController::class, 'show']);

    // El controlador autoriza ADMINISTRADOR o DOCENTE y limita al docente
    // a las materias que tiene asignadas.
    Route::post('/estudiantes/importar', [EstudianteController::class, 'importar']);

    Route::middleware('permission:GESTIONAR_ESTUDIANTES')->group(function () {
        Route::post('/estudiantes', [EstudianteController::class, 'store']);
        Route::put('/estudiantes/{estudiante}', [EstudianteController::class, 'update']);
        Route::patch('/estudiantes/{estudiante}', [EstudianteController::class, 'update']);
        Route::delete('/estudiantes/{estudiante}', [EstudianteController::class, 'destroy']);
    });

    /*
    |--------------------------------------------------------------------------
    | Estructura académica
    |--------------------------------------------------------------------------
    */
    Route::get('/facultades', [FacultadController::class, 'index']);
    Route::get('/facultades/{facultade}', [FacultadController::class, 'show']);

    Route::get('/carreras', [CarreraController::class, 'index']);
    Route::get('/carreras/{carrera}', [CarreraController::class, 'show']);

    Route::get('/asignaturas', [AsignaturaController::class, 'index']);
    Route::get('/asignaturas/{asignatura}', [AsignaturaController::class, 'show']);

    Route::middleware('permission:GESTIONAR_ASIGNATURAS')->group(function () {
        Route::post('/facultades', [FacultadController::class, 'store']);
        Route::put('/facultades/{facultade}', [FacultadController::class, 'update']);
        Route::patch('/facultades/{facultade}', [FacultadController::class, 'update']);
        Route::delete('/facultades/{facultade}', [FacultadController::class, 'destroy']);

        Route::post('/carreras', [CarreraController::class, 'store']);
        Route::put('/carreras/{carrera}', [CarreraController::class, 'update']);
        Route::patch('/carreras/{carrera}', [CarreraController::class, 'update']);
        Route::delete('/carreras/{carrera}', [CarreraController::class, 'destroy']);

        Route::post('/asignaturas', [AsignaturaController::class, 'store']);
        Route::put('/asignaturas/{asignatura}', [AsignaturaController::class, 'update']);
        Route::patch('/asignaturas/{asignatura}', [AsignaturaController::class, 'update']);
        Route::delete('/asignaturas/{asignatura}', [AsignaturaController::class, 'destroy']);
    });

    /*
    |--------------------------------------------------------------------------
    | Exámenes
    |--------------------------------------------------------------------------
    */
    Route::get('/examenes', [ExamenController::class, 'index']);
    Route::get('/examenes/{id}/auditoria', [ExamenController::class, 'auditoria']);
    Route::get('/examenes/{examene}', [ExamenController::class, 'show']);

    Route::middleware('role:ADMINISTRADOR,DOCENTE,CONTROL_INGRESO')->group(function () {
        Route::get('/examenes/{id}/normas', [NormaController::class, 'index']);
    });

    Route::middleware('role:ADMINISTRADOR,DOCENTE')->group(function () {
        Route::post('/examenes/{id}/normas', [NormaController::class, 'store']);
        Route::delete('/examenes/{id}/normas/{id_norma}', [NormaController::class, 'destroy']);
    });

    Route::middleware('permission:GESTIONAR_EXAMENES')->group(function () {
        Route::post('/examenes', [ExamenController::class, 'store']);
        Route::put('/examenes/{examene}', [ExamenController::class, 'update']);
        Route::patch('/examenes/{examene}', [ExamenController::class, 'update']);
        Route::delete('/examenes/{examene}', [ExamenController::class, 'destroy']);
    });

    /*
    |--------------------------------------------------------------------------
    | Ambientes
    |--------------------------------------------------------------------------
    */
    Route::get('/ambientes', [AmbienteController::class, 'index']);
    Route::get('/ambientes/{ambiente}', [AmbienteController::class, 'show']);

    Route::middleware('permission:GESTIONAR_AMBIENTES')->group(function () {
        Route::post('/ambientes', [AmbienteController::class, 'store']);
        Route::put('/ambientes/{ambiente}', [AmbienteController::class, 'update']);
        Route::patch('/ambientes/{ambiente}', [AmbienteController::class, 'update']);
        Route::delete('/ambientes/{ambiente}', [AmbienteController::class, 'destroy']);
    });

    /*
    |--------------------------------------------------------------------------
    | Habilitaciones
    |--------------------------------------------------------------------------
    */
    Route::get('/habilitaciones', [HabilitacionController::class, 'index']);
    Route::get('/habilitaciones/{habilitacione}', [HabilitacionController::class, 'show']);
    // El controlador valida el alcance del docente tanto al revocar como al
    // reactivar, siempre dentro de sus materias y carreras asignadas.
    Route::patch('/habilitaciones/{habilitacione}', [HabilitacionController::class, 'update']);

    // Consulta del motivo de inhabilitación por estudiante y examen 
    Route::get('/examenes/{examen}/estudiantes/{estudiante}/inhabilitacion', [HabilitacionController::class, 'consultarMotivo'])
        ->middleware('role:ADMINISTRADOR,DOCENTE,CONTROL_INGRESO');
        

    // El controlador limita al docente a su materia/carrera; otros roles
    // necesitan el permiso explícito.
    Route::middleware('habilitaciones')->group(function () {
        Route::post('/habilitaciones', [HabilitacionController::class, 'store']);
        Route::post('/habilitaciones/masiva', [HabilitacionController::class, 'storeMasiva']);
    });

    Route::middleware('permission:GESTIONAR_HABILITACIONES')->group(function () {
        Route::put('/habilitaciones/{habilitacione}', [HabilitacionController::class, 'update']);
        Route::delete('/habilitaciones/{habilitacione}', [HabilitacionController::class, 'destroy']);
    });
    /*
    |--------------------------------------------------------------------------
    | Control de ingreso
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:CONTROL_INGRESO,ADMINISTRADOR,DOCENTE')->group(function () {
        Route::get('/ingresos', [IngresoController::class, 'index']);
        Route::get('/ingresos/{ingreso}', [IngresoController::class, 'show']);
        Route::post('/ingresos/confirmar', [IngresoController::class, 'store']);
        Route::post('/ingresos', [IngresoController::class, 'store']);
        Route::put('/ingresos/{ingreso}', [IngresoController::class, 'update']);
        Route::patch('/ingresos/{ingreso}', [IngresoController::class, 'update']);
        Route::delete('/ingresos/{ingreso}', [IngresoController::class, 'destroy']);
    });
    /*
    |--------------------------------------------------------------------------
    | Incidencias
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:ADMINISTRADOR,DOCENTE,CONTROL_INGRESO')->group(function () {
        Route::get('/incidencias', [IncidenciaController::class, 'index']);
        Route::get('/incidencias/{incidencia}', [IncidenciaController::class, 'show']);
    });

    Route::middleware('role:ADMINISTRADOR,DOCENTE,CONTROL_INGRESO')->group(function () {
        Route::get('/reportes/examenes', [ReporteController::class, 'examenes']);
        Route::get('/reportes/examenes/{id}/asistencia', [ReporteController::class, 'asistencia']);
        Route::get('/reportes/examenes/{id}/intentos', [ReporteController::class, 'intentos']);
    });

    Route::middleware(['auth:sanctum', 'role:ADMINISTRADOR,DOCENTE,CONTROL_INGRESO'])->group(function () {
        Route::post('/examenes/{examen}/expulsiones', [IncidenciaController::class, 'registrarExpulsion']);
        Route::post('/incidencias/expulsion', [IncidenciaController::class, 'registrarExpulsion']);
    });

    Route::middleware('incidencias')->group(function () {
        Route::post('/incidencias/cambio-ambiente', [IncidenciaController::class, 'registrarCambioAmbiente']);
        Route::post('/incidencias', [IncidenciaController::class, 'store']);
    });

    Route::middleware('permission:REGISTRAR_INCIDENCIAS')->group(function () {
        Route::put('/incidencias/{incidencia}', [IncidenciaController::class, 'update']);
        Route::patch('/incidencias/{incidencia}', [IncidenciaController::class, 'update']);
        Route::delete('/incidencias/{incidencia}', [IncidenciaController::class, 'destroy']);
    });
    /*
    |--------------------------------------------------------------------------
    | Verificación de Identidad (VER-01)
    |--------------------------------------------------------------------------
    */
    Route::post('/verificaciones/qr', [VerificacionController::class, 'verificarPorQr'])
        ->middleware(['role:CONTROL_INGRESO,ADMINISTRADOR,DOCENTE', 'throttle:60,1']);
    Route::post('/verificaciones/documento', [VerificacionController::class, 'verificarPorDocumento'])
        ->middleware(['role:CONTROL_INGRESO,ADMINISTRADOR,DOCENTE', 'throttle:60,1']);
    Route::post('/verificaciones/ambiente', [VerificacionController::class, 'verificarAmbiente'])
        ->middleware(['role:CONTROL_INGRESO,ADMINISTRADOR', 'throttle:60,1']);
});
