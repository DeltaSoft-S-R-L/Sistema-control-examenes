<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('estudiante', 'id_carrera')) {
            Schema::table('estudiante', function (Blueprint $table) {
                $table->unsignedBigInteger('id_carrera')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('estudiante', 'id_carrera')) {
            Schema::table('estudiante', function (Blueprint $table) {
                $table->unsignedBigInteger('id_carrera')->nullable(false)->change();
            });
        }
    }
};
