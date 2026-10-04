<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ambiente', function (Blueprint $table) {
            $table->id('id_ambiente');
            $table->string('codigo', 30)->unique();
            $table->string('nombre', 100);
            $table->string('ubicacion', 150)->nullable();
            $table->integer('capacidad');
            $table->string('estado', 20);

            $table->index('estado');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement(
                'ALTER TABLE ambiente ADD CONSTRAINT ambiente_capacidad_check CHECK (capacidad > 0)'
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ambiente');
    }
};