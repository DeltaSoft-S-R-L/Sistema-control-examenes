<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estudiante', function (Blueprint $table) {
            $table->unsignedBigInteger('id_usuario')->nullable();

            $table->unique('id_usuario');

            $table->foreign('id_usuario')
                ->references('id_usuario')
                ->on('usuario')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('estudiante', function (Blueprint $table) {
            $table->dropForeign(['id_usuario']);
            $table->dropUnique(['id_usuario']);
            $table->dropColumn('id_usuario');
        });
    }
};