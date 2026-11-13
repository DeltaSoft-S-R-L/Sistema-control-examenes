<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Agregar id_asignacion_ambiente a la tabla habilitacion (ING-04)
        Schema::table('habilitacion', function (Blueprint $table) {
            $table->unsignedBigInteger('id_asignacion_ambiente')->nullable()->after('id_examen');

            $table->foreign('id_asignacion_ambiente')
                ->references('id_asignacion_ambiente')
                ->on('asignacion_ambiente')
                ->nullOnDelete();
        });

        // 2. Permitir que id_asignacion_ambiente sea nullable en intento_ingreso (VER-03 / ING-04)
        Schema::table('intento_ingreso', function (Blueprint $table) {
            $table->unsignedBigInteger('id_asignacion_ambiente')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('habilitacion', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['id_asignacion_ambiente']);
            }
            $table->dropColumn('id_asignacion_ambiente');
        });

        Schema::table('intento_ingreso', function (Blueprint $table) {
            $table->unsignedBigInteger('id_asignacion_ambiente')->nullable(false)->change();
        });
    }
};
