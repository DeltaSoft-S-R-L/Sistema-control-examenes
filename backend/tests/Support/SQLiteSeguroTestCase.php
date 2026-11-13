<?php

namespace Tests\Support;

use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

abstract class SQLiteSeguroTestCase extends TestCase
{
    use RefreshDatabase {
        refreshDatabase as protected refreshDatabaseOriginal;
    }

    public function refreshDatabase(): void
    {
        $this->verificarConexionSegura();

        $this->refreshDatabaseOriginal();
    }

    private function verificarConexionSegura(): void
    {
        if (
            !app()->environment('testing') ||
            config('database.default') !== 'sqlite' ||
            config('database.connections.sqlite.database') !== ':memory:'
        ) {
            throw new RuntimeException(
                'PRUEBA BLOQUEADA: solo se permite SQLite en memoria.'
            );
        }
    }
}
