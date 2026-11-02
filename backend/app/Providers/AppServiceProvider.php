<?php

namespace App\Providers;

use App\Models\NormaExamen;
use App\Observers\AuditObserver;
use App\Observers\NormaExamenObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        $modelosAuditables = [
            \App\Models\Usuario::class,
            \App\Models\Estudiante::class,
            \App\Models\Asignatura::class,
            \App\Models\Examen::class,
            \App\Models\Ambiente::class,
            \App\Models\Habilitacion::class,
            \App\Models\Ingreso::class,
            \App\Models\Incidencia::class,
        ];

        foreach ($modelosAuditables as $modelo) {
            if (class_exists($modelo)) {
                $modelo::observe(AuditObserver::class);
            }
        }

        if (class_exists(NormaExamen::class)) {
            NormaExamen::observe(NormaExamenObserver::class);
        }
    }
}
