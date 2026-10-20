<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('habilitacion', function (Blueprint $table) {
            $table->renameColumn('motivo', 'motivo_detalle');
            $table->string('motivo_tipo')->nullable();
            $table->foreignId('inhabilitado_por')->nullable()->constrained('users');
            $table->timestamp('inhabilitado_at')->nullable();
        });

        DB::table('habilitacion')
            ->where('estado', 'NO_HABILITADO')
            ->update([
                'motivo_tipo' => 'OTRO',
                'motivo_detalle' => DB::raw("COALESCE(motivo_detalle, 'Sin motivo registrado')")
            ]);

        DB::statement("
            ALTER TABLE habilitacion 
            ADD CONSTRAINT check_motivo_tipo_not_null 
            CHECK (estado = 'HABILITADO' OR motivo_tipo IS NOT NULL)
        ");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE habilitacion DROP CONSTRAINT IF EXISTS check_motivo_tipo_not_null');

        Schema::table('habilitacion', function (Blueprint $table) {
            $table->dropForeign(['inhabilitado_por']);
            $table->dropColumn([
                'motivo_tipo',
                'inhabilitado_por',
                'inhabilitado_at'
            ]);
            $table->renameColumn('motivo_detalle', 'motivo');
        });
    }
};