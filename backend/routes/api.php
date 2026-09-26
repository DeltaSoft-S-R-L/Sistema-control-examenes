<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AmbienteController;
use App\Http\Controllers\Api\AsignaturaController;
use App\Http\Controllers\Api\EstudianteController;
use App\Http\Controllers\Api\ExamenController;
use App\Http\Controllers\Api\HabilitacionController;
use App\Http\Controllers\Api\IngresoController;
use App\Http\Controllers\Api\IncidenciaController;
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

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Estudiantes — rutas específicas antes del apiResource
    Route::get('/estudiantes/buscar',      [EstudianteController::class, 'buscarPorIdentificador']);
    Route::post('/estudiantes/carga-masiva', [EstudianteController::class, 'cargaMasiva']);

    // Recursos principales
    Route::apiResource('estudiantes',    EstudianteController::class);
    Route::apiResource('examenes',       ExamenController::class);
    Route::apiResource('ambientes',      AmbienteController::class);
    Route::apiResource('asignaturas',    AsignaturaController::class);
    Route::apiResource('habilitaciones', HabilitacionController::class);
    Route::apiResource('ingresos',       IngresoController::class);
    Route::apiResource('incidencias',    IncidenciaController::class);
});
