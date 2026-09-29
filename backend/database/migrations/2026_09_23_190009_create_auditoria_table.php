<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditoria', function (Blueprint $table) {
            $table->id('id_auditoria');
            $table->unsignedBigInteger('id_usuario');
            $table->string('accion', 100);
            $table->string('entidad', 100);
            $table->unsignedBigInteger('id_registro')->nullable();
            $table->timestampTz('fecha_hora');
            $table->string('resultado', 20)->nullable();
            $table->text('descripcion')->nullable();

            $table->foreign('id_usuario')
                ->references('id_usuario')
                ->on('usuario');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditoria');
    }
};