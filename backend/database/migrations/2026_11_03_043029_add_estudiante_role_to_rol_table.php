
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $existe = DB::table('rol')
            ->where('nombre', 'ESTUDIANTE')
            ->exists();

        if (! $existe) {
            DB::table('rol')->insert([
                'nombre' => 'ESTUDIANTE',
                'descripcion' => 'Acceso al dashboard del estudiante',
            ]);
        }
    }

    public function down(): void
    {
        // No eliminamos el rol automáticamente porque podría
        // tener estudiantes asociados y datos relacionados.
    }
};
