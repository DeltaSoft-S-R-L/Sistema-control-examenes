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
use Illuminate\Support\Facades\Route;

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

// Rutas protegidas con Sanctum
Route::middleware('auth:sanctum')->group(function () {

    // Sesión
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    /*
    |--------------------------------------------------------------------------
    | Usuarios
    |--------------------------------------------------------------------------
    */
    Route::middleware('permission:GESTIONAR_USUARIOS')->group(function () {
        Route::get('/usuarios', [UsuarioController::class, 'index']);
        Route::post('/usuarios', [UsuarioController::class, 'store']);
        Route::get('/usuarios/{id}', [UsuarioController::class, 'show']);
        Route::put('/usuarios/{usuario}', [UsuarioController::class, 'update']);
    });

    /*
    |--------------------------------------------------------------------------
    | Estudiantes
    |--------------------------------------------------------------------------
    */
    Route::get('/estudiantes', [EstudianteController::class, 'index']);
    Route::get('/estudiantes/{estudiante}', [EstudianteController::class, 'show']);

    Route::middleware('permission:GESTIONAR_ESTUDIANTES')->group(function () {
        Route::post('/estudiantes/importar', [EstudianteController::class, 'importar']);
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

    Route::middleware('permission:GESTIONAR_HABILITACIONES')->group(function () {
        Route::post('/habilitaciones', [HabilitacionController::class, 'store']);
        Route::put('/habilitaciones/{habilitacione}', [HabilitacionController::class, 'update']);
        Route::patch('/habilitaciones/{habilitacione}', [HabilitacionController::class, 'update']);
        Route::delete('/habilitaciones/{habilitacione}', [HabilitacionController::class, 'destroy']);
    });

    /*
    |--------------------------------------------------------------------------
    | Control de ingreso
    |--------------------------------------------------------------------------
    */
    Route::get('/ingresos', [IngresoController::class, 'index']);
    Route::get('/ingresos/{ingreso}', [IngresoController::class, 'show']);

    Route::middleware('permission:REGISTRAR_INGRESOS')->group(function () {
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
    Route::get('/incidencias', [IncidenciaController::class, 'index']);
    Route::get('/incidencias/{incidencia}', [IncidenciaController::class, 'show']);

    Route::middleware('permission:REGISTRAR_INCIDENCIAS')->group(function () {
        Route::post('/incidencias', [IncidenciaController::class, 'store']);
        Route::put('/incidencias/{incidencia}', [IncidenciaController::class, 'update']);
        Route::patch('/incidencias/{incidencia}', [IncidenciaController::class, 'update']);
        Route::delete('/incidencias/{incidencia}', [IncidenciaController::class, 'destroy']);
    });
});