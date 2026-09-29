<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regla_individual', function (Blueprint $table) {
            $table->id('id_regla_individual');
            $table->unsignedBigInteger('id_habilitacion');
            $table->text('descripcion');
            $table->string('estado', 20);

            $table->index('estado');

            $table->foreign('id_habilitacion')
                ->references('id_habilitacion')
                ->on('habilitacion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regla_individual');
    }
};