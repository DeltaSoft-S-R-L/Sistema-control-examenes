<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id('id_audit_log');

            $table->unsignedBigInteger('id_usuario')->nullable();
            $table->string('accion', 100);
            $table->string('entidad', 100);
            $table->string('id_registro', 100)->nullable();
            $table->timestampTz('fecha_hora')->useCurrent();
            $table->json('informacion')->nullable();

            $table->foreign('id_usuario')
                ->references('id_usuario')
                ->on('usuario')
                ->nullOnDelete();

            $table->index('fecha_hora');
            $table->index(['entidad', 'accion']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};