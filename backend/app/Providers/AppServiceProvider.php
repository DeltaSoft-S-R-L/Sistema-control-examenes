<?php

namespace App\Providers;

use App\Observers\AuditObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
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
}
}
