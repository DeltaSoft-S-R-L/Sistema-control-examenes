<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asignatura', function (Blueprint $table) {
            $table->id('id_asignatura');
            $table->string('codigo', 30)->unique();
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asignatura');
    }
};