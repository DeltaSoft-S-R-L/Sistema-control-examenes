<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('examen', function (Blueprint $table) {
            $table->id('id_examen');
            $table->unsignedBigInteger('id_asignatura');
            $table->string('nombre', 150);
            $table->date('fecha');
            $table->time('hora_inicio');
            $table->integer('duracion_minutos');
            $table->text('descripcion')->nullable();
            $table->string('estado', 20);

            $table->index(['fecha', 'hora_inicio']);
            $table->index('estado');

            $table->foreign('id_asignatura')
                ->references('id_asignatura')
                ->on('asignatura');
        });

        DB::statement(
            'ALTER TABLE examen ADD CONSTRAINT examen_duracion_check CHECK (duracion_minutos > 0)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('examen');
    }
};