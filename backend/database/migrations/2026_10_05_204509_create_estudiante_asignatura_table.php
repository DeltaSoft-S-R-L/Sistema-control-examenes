<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estudiante_asignatura', function (Blueprint $table) {
            $table->unsignedBigInteger('id_estudiante');
            $table->unsignedBigInteger('id_asignatura');

            $table->primary(['id_estudiante', 'id_asignatura']);

            $table->foreign('id_estudiante')
                ->references('id_estudiante')
                ->on('estudiante')
                ->cascadeOnDelete();

            $table->foreign('id_asignatura')
                ->references('id_asignatura')
                ->on('asignatura')
                ->cascadeOnDelete();

            $table->index('id_asignatura');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estudiante_asignatura');
    }
};