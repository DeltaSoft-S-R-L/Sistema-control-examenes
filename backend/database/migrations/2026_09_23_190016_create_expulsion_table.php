<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expulsion', function (Blueprint $table) {
            $table->id('id_expulsion');
            $table->unsignedBigInteger('id_ingreso');
            $table->unsignedBigInteger('id_usuario');
            $table->text('motivo');
            $table->timestampTz('fecha_hora');

            $table->unique('id_ingreso');

            $table->foreign('id_ingreso')
                ->references('id_ingreso')
                ->on('ingreso');

            $table->foreign('id_usuario')
                ->references('id_usuario')
                ->on('usuario');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expulsion');
    }
};