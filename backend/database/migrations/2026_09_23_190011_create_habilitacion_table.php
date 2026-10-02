<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('habilitacion', function (Blueprint $table) {
            $table->id('id_habilitacion');
            $table->unsignedBigInteger('id_estudiante');
            $table->unsignedBigInteger('id_examen');
            $table->string('estado', 20);
            $table->text('motivo')->nullable();

            $table->unique(['id_estudiante', 'id_examen']);

            $table->unique([
                'id_habilitacion',
                'id_examen'
            ]);

            $table->foreign('id_estudiante')
                ->references('id_estudiante')
                ->on('estudiante');

            $table->foreign('id_examen')
                ->references('id_examen')
                ->on('examen');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement(
                "ALTER TABLE habilitacion ADD CONSTRAINT habilitacion_estado_check CHECK (estado IN ('HABILITADO', 'NO_HABILITADO'))"
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('habilitacion');
    }
};