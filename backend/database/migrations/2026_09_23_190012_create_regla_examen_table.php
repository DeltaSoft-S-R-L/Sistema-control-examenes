<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regla_examen', function (Blueprint $table) {
            $table->id('id_regla_examen');
            $table->unsignedBigInteger('id_examen');
            $table->text('descripcion');
            $table->string('estado', 20);

            $table->index('estado');

            $table->foreign('id_examen')
                ->references('id_examen')
                ->on('examen');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regla_examen');
    }
};