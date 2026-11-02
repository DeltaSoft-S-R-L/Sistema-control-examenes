<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('docente_asignatura')) {
            return;
        }

        Schema::create('docente_asignatura', function (Blueprint $table) {
            $table->unsignedBigInteger('id_usuario');
            $table->unsignedBigInteger('id_carrera');
            $table->unsignedBigInteger('id_asignatura');
            $table->timestamp('fecha_asignacion')->useCurrent();

            $table->primary(['id_usuario', 'id_carrera', 'id_asignatura']);

            $table->foreign('id_usuario')
                ->references('id_usuario')
                ->on('usuario')
                ->cascadeOnDelete();

            $table->foreign(['id_carrera', 'id_asignatura'])
                ->references(['id_carrera', 'id_asignatura'])
                ->on('carrera_asignatura')
                ->cascadeOnDelete();

            $table->index('id_carrera');
            $table->index('id_asignatura');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('docente_asignatura');
    }
};
