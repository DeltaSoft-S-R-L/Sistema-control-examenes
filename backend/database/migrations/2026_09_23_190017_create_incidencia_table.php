<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidencia', function (Blueprint $table) {
            $table->id('id_incidencia');
            $table->unsignedBigInteger('id_estudiante')->nullable();
            $table->unsignedBigInteger('id_examen');
            $table->unsignedBigInteger('id_usuario');
            $table->string('tipo', 50);
            $table->text('descripcion');
            $table->timestampTz('fecha_hora');

            $table->index('tipo');

            $table->foreign('id_estudiante')
                ->references('id_estudiante')
                ->on('estudiante');

            $table->foreign('id_examen')
                ->references('id_examen')
                ->on('examen');

            $table->foreign('id_usuario')
                ->references('id_usuario')
                ->on('usuario');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidencia');
    }
};