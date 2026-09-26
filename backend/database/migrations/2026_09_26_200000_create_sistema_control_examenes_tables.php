<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('rol')) {
            Schema::create('rol', function (Blueprint $table) {
                $table->id('id_rol');
                $table->string('nombre', 50)->unique();
                $table->string('descripcion', 255)->nullable();
            });
        }

        if (!Schema::hasTable('usuario')) {
            Schema::create('usuario', function (Blueprint $table) {
                $table->id('id_usuario');
                $table->string('nombre', 100);
                $table->string('apellido', 100);
                $table->string('correo', 150)->unique();
                $table->string('username', 50)->unique();
                $table->string('password_hash', 255);
                $table->foreignId('id_rol')->constrained('rol', 'id_rol');
                $table->string('estado', 20)->default('ACTIVO');
            });
        }

        if (!Schema::hasTable('estudiante')) {
            Schema::create('estudiante', function (Blueprint $table) {
                $table->id('id_estudiante');
                $table->string('ci', 20)->unique();
                $table->string('nombre', 100);
                $table->string('apellido', 100);
                $table->string('codigo_universitario', 50)->unique();
                $table->string('correo', 150)->nullable();
                $table->string('estado', 20)->default('ACTIVO');
            });
        }

        if (!Schema::hasTable('habilitacion')) {
            Schema::create('habilitacion', function (Blueprint $table) {
                $table->id('id_habilitacion');
                $table->foreignId('id_estudiante')->constrained('estudiante', 'id_estudiante')->onDelete('cascade');
                $table->bigInteger('id_examen')->nullable();
                $table->string('estado', 20);
                $table->text('motivo')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('habilitacion');
        Schema::dropIfExists('estudiante');
        Schema::dropIfExists('usuario');
        Schema::dropIfExists('rol');
    }
};
