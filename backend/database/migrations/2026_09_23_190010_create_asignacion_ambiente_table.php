<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asignacion_ambiente', function (Blueprint $table) {
            $table->id('id_asignacion_ambiente');
            $table->unsignedBigInteger('id_examen');
            $table->unsignedBigInteger('id_ambiente');

            $table->unique(['id_examen', 'id_ambiente']);

            $table->unique([
                'id_asignacion_ambiente',
                'id_examen'
            ]);

            $table->index([
                'id_ambiente',
                'id_examen'
            ]);

            $table->foreign('id_examen')
                ->references('id_examen')
                ->on('examen');

            $table->foreign('id_ambiente')
                ->references('id_ambiente')
                ->on('ambiente');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asignacion_ambiente');
    }
};