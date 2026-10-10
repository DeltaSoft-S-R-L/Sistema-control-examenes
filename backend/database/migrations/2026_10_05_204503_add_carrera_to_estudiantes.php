<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facultad', function (Blueprint $table) {
            $table->id('id_facultad');
            $table->string('codigo', 30)->unique();
            $table->string('nombre', 150);
        });

        Schema::create('carrera', function (Blueprint $table) {
            $table->id('id_carrera');
            $table->unsignedBigInteger('id_facultad');
            $table->string('codigo', 30)->unique();
            $table->string('nombre', 150);

            $table->foreign('id_facultad')
                ->references('id_facultad')
                ->on('facultad');

            $table->index('id_facultad');
        });

        Schema::create('carrera_asignatura', function (Blueprint $table) {
            $table->unsignedBigInteger('id_carrera');
            $table->unsignedBigInteger('id_asignatura');

            $table->primary(['id_carrera', 'id_asignatura']);

            $table->foreign('id_carrera')
                ->references('id_carrera')
                ->on('carrera');

            $table->foreign('id_asignatura')
                ->references('id_asignatura')
                ->on('asignatura');

            $table->index('id_asignatura');
        });

        Schema::table('estudiante', function (Blueprint $table) {
            $table->unsignedBigInteger('id_carrera');

            $table->foreign('id_carrera')
                ->references('id_carrera')
                ->on('carrera');

            $table->index('id_carrera');
        });
    }

    public function down(): void
    {
        Schema::table('estudiante', function (Blueprint $table) {
            $table->dropForeign(['id_carrera']);
            $table->dropIndex(['id_carrera']);
            $table->dropColumn('id_carrera');
        });

        Schema::dropIfExists('carrera_asignatura');
        Schema::dropIfExists('carrera');
        Schema::dropIfExists('facultad');
    }
};