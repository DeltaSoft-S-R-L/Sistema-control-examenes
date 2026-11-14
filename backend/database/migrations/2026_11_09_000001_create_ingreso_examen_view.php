<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            DB::statement('CREATE VIEW IF NOT EXISTS ingreso_examen AS SELECT * FROM ingreso;');
        } else {
            DB::statement('CREATE OR REPLACE VIEW ingreso_examen AS SELECT * FROM ingreso;');
        }
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS ingreso_examen;');
    }
};
