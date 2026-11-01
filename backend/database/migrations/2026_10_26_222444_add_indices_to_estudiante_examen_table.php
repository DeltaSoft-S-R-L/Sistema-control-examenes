<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('habilitacion', function (Blueprint $table) {
            // Reemplazo del unique() por un index() normal
            $table->index(['id_examen', 'id_estudiante'], 'idx_habilitacion_examen_estudiante');
        });

        // Índice parcial corregido incluyendo las columnas de búsqueda
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("
                CREATE INDEX IF NOT EXISTS idx_habilitacion_inhabilitados 
                ON habilitacion (id_examen, id_estudiante) 
                WHERE estado = 'NO_HABILITADO'
            ");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS idx_habilitacion_inhabilitados');
        }

        Schema::table('habilitacion', function (Blueprint $table) {
            $table->dropIndex('idx_habilitacion_examen_estudiante');
        });
    }
};