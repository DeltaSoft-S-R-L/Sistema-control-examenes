<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permiso', function (Blueprint $table) {
            $table->id('id_permiso');
            $table->string('nombre', 100)->unique();
            $table->string('descripcion', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permiso');
    }
};