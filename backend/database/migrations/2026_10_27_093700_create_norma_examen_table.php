<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('norma_examen', function (Blueprint $table) {
            $table->id('id_norma_examen');
            $table->unsignedBigInteger('id_examen');
            $table->unsignedBigInteger('id_estudiante')->nullable();
            $table->string('tipo', 20);
            $table->text('descripcion');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->index('id_examen');
            $table->index(['id_examen', 'id_estudiante']);

            $table->foreign('id_examen')
                ->references('id_examen')
                ->on('examen')
                ->onDelete('cascade');

            $table->foreign('id_estudiante')
                ->references('id_estudiante')
                ->on('estudiante')
                ->onDelete('cascade');

            $table->foreign('created_by')
                ->references('id_usuario')
                ->on('usuario');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement(
                "ALTER TABLE norma_examen ADD CONSTRAINT norma_examen_tipo_check CHECK (tipo IN ('GENERAL', 'PARTICULAR'))"
            );

            DB::statement(
                "ALTER TABLE norma_examen ADD CONSTRAINT norma_examen_tipo_estudiante_check CHECK (
                    (tipo = 'GENERAL' AND id_estudiante IS NULL) OR
                    (tipo = 'PARTICULAR' AND id_estudiante IS NOT NULL)
                )"
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('norma_examen');
    }
};
