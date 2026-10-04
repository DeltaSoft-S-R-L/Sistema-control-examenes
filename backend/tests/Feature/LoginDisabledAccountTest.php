<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\Usuario;
use App\Utilities\PasswordHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginDisabledAccountTest extends TestCase
{
    use RefreshDatabase;

    private Rol $rolDocente;
    private Usuario $usuarioActivo;
    private Usuario $usuarioRevocado;

    protected function setUp(): void
    {
        parent::setUp();

        // Rol base
        $this->rolDocente = Rol::create([
            'nombre'      => 'DOCENTE',
            'descripcion' => 'Docente del sistema',
        ]);

        // Usuario ACTIVO
        $this->usuarioActivo = Usuario::create([
            'nombre'        => 'Juan',
            'apellido'      => 'Pérez',
            'correo'        => 'juan.perez@universidad.edu',
            'username'      => 'juan_activo',
            'password_hash' => PasswordHasher::hash('Password123'),
            'id_rol'        => $this->rolDocente->id_rol,
            'estado'        => 'ACTIVO',
        ]);

        // Usuario REVOCADO / INACTIVO
        $this->usuarioRevocado = Usuario::create([
            'nombre'        => 'Mario',
            'apellido'      => 'Sánchez',
            'correo'        => 'mario.sanchez@universidad.edu',
            'username'      => 'mario_revocado',
            'password_hash' => PasswordHasher::hash('Password123'),
            'id_rol'        => $this->rolDocente->id_rol,
            'estado'        => 'REVOCADO',
        ]);
    }

    /**
     * 1. Login exitoso: Usuario ACTIVO con credenciales correctas recibe token y status 200.
     */
    public function test_login_exitoso_usuario_activo(): void
    {
        $response = $this->postJson('/api/login', [
            'username' => 'juan_activo',
            'password' => 'Password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'token',
                'usuario' => [
                    'id',
                    'nombre',
                    'apellido',
                    'username',
                    'correo',
                    'rol',
                ],
            ])
            ->assertJson([
                'usuario' => [
                    'username' => 'juan_activo',
                    'correo'   => 'juan.perez@universidad.edu',
                    'rol'      => 'DOCENTE',
                ],
            ]);

        $this->assertNotEmpty($response->json('token'));
    }

    /**
     * 2. Login rechazado por cuenta inactiva/revocada: Contraseña correcta pero estado no activo.
     * Retorna 422 con mensaje explícito en 'username'.
     */
    public function test_login_rechazado_por_cuenta_inactiva_o_revocada(): void
    {
        $response = $this->postJson('/api/login', [
            'username' => 'mario_revocado',
            'password' => 'Password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username'])
            ->assertJsonFragment([
                'username' => [
                    'Su cuenta se encuentra inactiva o revocada. Contacte al administrador.',
                ],
            ]);
    }

    /**
     * 3. Login rechazado por credenciales inválidas (usuario activo con contraseña incorrecta).
     * Retorna error estándar sin revelar detalles del sistema.
     */
    public function test_login_rechazado_por_password_incorrecta_usuario_activo(): void
    {
        $response = $this->postJson('/api/login', [
            'username' => 'juan_activo',
            'password' => 'PasswordIncorrecta999',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username'])
            ->assertJsonFragment([
                'username' => [
                    'Las credenciales son incorrectas.',
                ],
            ]);
    }

    /**
     * 4. Login con cuenta revocada pero contraseña incorrecta:
     * Debe retornar el error estándar de credenciales para no filtrar el estado de la cuenta.
     */
    public function test_login_rechazado_cuenta_revocada_con_password_incorrecta_no_revela_estado(): void
    {
        $response = $this->postJson('/api/login', [
            'username' => 'mario_revocado',
            'password' => 'ClaveErronea123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username'])
            ->assertJsonFragment([
                'username' => [
                    'Las credenciales son incorrectas.',
                ],
            ]);
    }

    /**
     * 5. Login rechazado por usuario inexistente.
     * Retorna error estándar de credenciales.
     */
    public function test_login_rechazado_por_usuario_inexistente(): void
    {
        $response = $this->postJson('/api/login', [
            'username' => 'usuario_inexistente',
            'password' => 'CualquierClave123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username'])
            ->assertJsonFragment([
                'username' => [
                    'Las credenciales son incorrectas.',
                ],
            ]);
    }
}
