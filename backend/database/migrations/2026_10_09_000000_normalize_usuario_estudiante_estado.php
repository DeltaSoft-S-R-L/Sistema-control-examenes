<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Corrige los datos existentes antes de que los modelos comiencen a
     * mantener los estados en su representación canónica.
     */
    public function up(): void
    {
        DB::table('usuario')->update([
            'estado' => DB::raw('UPPER(TRIM(estado))'),
        ]);

        DB::table('estudiante')->update([
            'estado' => DB::raw('UPPER(TRIM(estado))'),
        ]);
    }

    public function down(): void
    {
        // La normalización es intencionalmente irreversible.
    }
};
