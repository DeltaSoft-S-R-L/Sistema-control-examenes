<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') return;
        DB::statement('ALTER TABLE habilitacion DROP CONSTRAINT IF EXISTS habilitacion_estado_check');
        DB::statement("ALTER TABLE habilitacion ADD CONSTRAINT habilitacion_estado_check CHECK (estado IN ('HABILITADO', 'NO_HABILITADO', 'EXPULSADO'))");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') return;
        DB::statement('ALTER TABLE habilitacion DROP CONSTRAINT IF EXISTS habilitacion_estado_check');
        DB::statement("ALTER TABLE habilitacion ADD CONSTRAINT habilitacion_estado_check CHECK (estado IN ('HABILITADO', 'NO_HABILITADO'))");
    }
};
