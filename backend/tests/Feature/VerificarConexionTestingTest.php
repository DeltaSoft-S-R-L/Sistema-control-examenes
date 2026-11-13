<?php

namespace Tests\Feature;

use Tests\TestCase;

class VerificarConexionTestingTest extends TestCase
{
    public function test_utiliza_sqlite_en_memoria(): void
    {
        $this->assertSame(
            'testing',
            app()->environment(),
            'Laravel no está utilizando el entorno testing.'
        );

        $this->assertSame(
            'sqlite',
            config('database.default'),
            'PELIGRO: la conexión predeterminada no es SQLite.'
        );

        $this->assertSame(
            ':memory:',
            config('database.connections.sqlite.database'),
            'La base de datos SQLite no está configurada en memoria.'
        );

        $this->assertSame(
            'sqlite',
            \Illuminate\Support\Facades\DB::connection()->getDriverName(),
            'PELIGRO: la conexión activa no es SQLite.'
        );

        $this->assertSame(
            ':memory:',
            \Illuminate\Support\Facades\DB::connection()->getDatabaseName(),
            'La conexión activa no utiliza SQLite en memoria.'
        );
    }
}