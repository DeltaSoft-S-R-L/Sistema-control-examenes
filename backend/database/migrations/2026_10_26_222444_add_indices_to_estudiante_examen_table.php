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
            // 1. Índice compuesto (acelera la búsqueda exacta de id_examen + id_estudiante)
            $table->unique(['id_examen', 'id_estudiante'], 'idx_habilitacion_examen_estudiante');
        });

        // 2. Índice parcial nativo de PostgreSQL (solo indexa los rechazados para listados ultra rápidos)
        DB::statement("CREATE INDEX idx_habilitacion_inhabilitados ON habilitacion (estado) WHERE estado = 'NO_HABILITADO'");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_habilitacion_inhabilitados');
        Schema::table('habilitacion', function (Blueprint $table) {
            $table->dropUnique('idx_habilitacion_examen_estudiante');
        });
    }
};