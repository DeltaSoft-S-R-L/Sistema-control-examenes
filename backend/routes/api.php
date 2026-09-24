<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Rutas del Sistema de Control de Exámenes bajo el prefijo /api
|
*/

// Tarea #6: Registro de usuarios
Route::post('/auth/register', [AuthController::class, 'register']);

// Tarea #9: Actualización parcial y total de usuarios (soporta PATCH y PUT)
Route::match(['put', 'patch'], '/users/{id}', [UserController::class, 'update']);
