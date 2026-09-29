<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingreso', function (Blueprint $table) {
            $table->id('id_ingreso');
            $table->unsignedBigInteger('id_examen');
            $table->unsignedBigInteger('id_habilitacion');
            $table->unsignedBigInteger('id_asignacion_ambiente');
            $table->unsignedBigInteger('id_usuario');
            $table->timestampTz('fecha_hora');

            $table->unique('id_habilitacion');

            $table->foreign('id_examen')
                ->references('id_examen')
                ->on('examen');

            $table->foreign(['id_habilitacion', 'id_examen'])
                ->references(['id_habilitacion', 'id_examen'])
                ->on('habilitacion');

            $table->foreign(['id_asignacion_ambiente', 'id_examen'])
                ->references(['id_asignacion_ambiente', 'id_examen'])
                ->on('asignacion_ambiente');

            $table->foreign('id_usuario')
                ->references('id_usuario')
                ->on('usuario');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingreso');
    }
};