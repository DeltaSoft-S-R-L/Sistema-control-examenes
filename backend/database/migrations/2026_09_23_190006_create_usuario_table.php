<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuario', function (Blueprint $table) {
            $table->id('id_usuario');
            $table->string('nombre', 100);
            $table->string('apellido', 100);
            $table->string('correo', 150)->unique();
            $table->string('username', 50)->unique();
            $table->string('password_hash', 255);
            $table->unsignedBigInteger('id_rol');
            $table->string('estado', 20);

            $table->index('estado');

            $table->foreign('id_rol')
                ->references('id_rol')
                ->on('rol');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement(
                "ALTER TABLE usuario ADD CONSTRAINT usuario_estado_check CHECK (estado IN ('ACTIVO', 'REVOCADO'))"
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('usuario');
    }
};