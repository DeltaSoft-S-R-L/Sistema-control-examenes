<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // Unificar restricción de estado permitiendo EXPULSADO tanto en esquemas
        // creados desde 01_schema.sql (ck_*) como desde migraciones Laravel.
        DB::statement('ALTER TABLE habilitacion DROP CONSTRAINT IF EXISTS ck_habilitacion_estado');
        DB::statement('ALTER TABLE habilitacion DROP CONSTRAINT IF EXISTS habilitacion_estado_check');
        DB::statement("ALTER TABLE habilitacion ADD CONSTRAINT habilitacion_estado_check CHECK (estado IN ('HABILITADO', 'NO_HABILITADO', 'EXPULSADO'))");

        // Las restricciones de motivo aplican a NO_HABILITADO; EXPULSADO guarda su detalle en expulsion/incidencia.
        DB::statement('ALTER TABLE habilitacion DROP CONSTRAINT IF EXISTS ck_habilitacion_motivo');
        DB::statement("ALTER TABLE habilitacion ADD CONSTRAINT ck_habilitacion_motivo CHECK (estado IN ('HABILITADO', 'EXPULSADO') OR motivo_detalle IS NOT NULL)");

        DB::statement('ALTER TABLE habilitacion DROP CONSTRAINT IF EXISTS check_motivo_tipo_not_null');
        DB::statement('ALTER TABLE habilitacion DROP CONSTRAINT IF EXISTS ck_habilitacion_motivo_tipo');
        DB::statement("ALTER TABLE habilitacion ADD CONSTRAINT check_motivo_tipo_not_null CHECK (estado IN ('HABILITADO', 'EXPULSADO') OR motivo_tipo IN ('ACADEMICO', 'MORA', 'DOCUMENTACION', 'OTRO'))");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE habilitacion DROP CONSTRAINT IF EXISTS habilitacion_estado_check');
        DB::statement("ALTER TABLE habilitacion ADD CONSTRAINT habilitacion_estado_check CHECK (estado IN ('HABILITADO', 'NO_HABILITADO'))");

        DB::statement('ALTER TABLE habilitacion DROP CONSTRAINT IF EXISTS ck_habilitacion_motivo');
        DB::statement("ALTER TABLE habilitacion ADD CONSTRAINT ck_habilitacion_motivo CHECK (estado = 'HABILITADO' OR motivo_detalle IS NOT NULL)");

        DB::statement('ALTER TABLE habilitacion DROP CONSTRAINT IF EXISTS check_motivo_tipo_not_null');
        DB::statement("ALTER TABLE habilitacion ADD CONSTRAINT check_motivo_tipo_not_null CHECK (estado = 'HABILITADO' OR motivo_tipo IN ('ACADEMICO', 'MORA', 'DOCUMENTACION', 'OTRO'))");
    }
};
