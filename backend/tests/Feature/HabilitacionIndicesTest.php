<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HabilitacionIndicesTest extends TestCase
{
    public function test_indices_creados_correctamente()
    {
        // Verifica que la tabla tiene el índice compuesto normal
        $this->assertTrue(
            collect(Schema::getIndexes('habilitacion'))
                ->contains('name', 'idx_habilitacion_examen_estudiante')
        );
        
        // Verifica el índice parcial solo si no es SQLite
        if (DB::getDriverName() !== 'sqlite') {
            $indices = DB::select("SELECT indexname FROM pg_indexes WHERE tablename = 'habilitacion'");
            $nombresIndices = array_column($indices, 'indexname');
            $this->assertContains('idx_habilitacion_inhabilitados', $nombresIndices);
        }
    }
}