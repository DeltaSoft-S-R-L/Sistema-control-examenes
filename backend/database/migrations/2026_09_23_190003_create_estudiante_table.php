<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estudiante', function (Blueprint $table) {
            $table->id('id_estudiante');
            $table->string('ci', 20)->unique();
            $table->string('nombre', 100);
            $table->string('apellido', 100);
            $table->string('codigo_universitario', 50)->unique();
            $table->string('correo', 150)->nullable();
            $table->string('estado', 20);
            
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estudiante');
    }
};