<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\Usuario;
use App\Utilities\PasswordHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterUserTest extends TestCase
{
    use RefreshDatabase;

    private Rol $rolAdmin;
    private Rol $rolDocente;
    private Usuario $adminUser;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear roles base
        $this->rolAdmin = Rol::create([
            'nombre'      => 'ADMINISTRADOR',
            'descripcion' => 'Administrador del sistema',
        ]);

        $this->rolDocente = Rol::create([
            'nombre'      => 'DOCENTE',
            'descripcion' => 'Docente del sistema',
        ]);

        // Crear usuario administrador para autenticación
        $this->adminUser = Usuario::create([
            'nombre'        => 'Admin',
            'apellido'      => 'Test',
            'correo'        => 'admin@test.com',
            'username'      => 'admin_test',
            'password_hash' => PasswordHasher::hash('Admin1234'),
            'id_rol'        => $this->rolAdmin->id_rol,
            'estado'        => 'ACTIVO',
        ]);

        // Generar token Sanctum para las peticiones autenticadas
        $this->token = $this->adminUser->createToken('test-token')->plainTextToken;
    }

    /**
     * Helper para hacer peticiones autenticadas.
     */
    private function authHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->token,
            'Accept'        => 'application/json',
        ];
    }

    /**
     * Payload válido base para crear un usuario.
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'nombre'   => 'Carlos',
            'apellido' => 'González',
            'correo'   => 'carlos.gonzalez@universidad.edu',
            'username' => 'carlos_g',
            'password' => 'Segura123',
            'id_rol'   => $this->rolDocente->id_rol,
        ], $overrides);
    }

    // ─────────────────────────────────────────────────────────────
    // Test 1: Creación exitosa y disponibilidad inmediata
    // ─────────────────────────────────────────────────────────────

    public function test_crea_usuario_valido_y_queda_disponible_para_consulta(): void
    {
        $payload = $this->validPayload();

        $response = $this->postJson('/api/usuarios', $payload, $this->authHeaders());

        // 1. Verificar código 201 Created
        $response->assertStatus(201);

        // 2. Verificar estructura de la respuesta
        $response->assertJsonStructure([
            'message',
            'usuario' => [
                'id_usuario',
                'nombre',
                'apellido',
                'correo',
                'username',
                'id_rol',
                'estado',
                'rol' => ['id_rol', 'nombre'],
            ],
        ]);

        // 3. Verificar que password_hash NO se expone en la respuesta
        $response->assertJsonMissing(['password_hash']);

        // 4. Verificar valores retornados
        $response->assertJson([
            'message' => 'Usuario registrado correctamente.',
            'usuario' => [
                'nombre'   => 'Carlos',
                'apellido' => 'González',
                'correo'   => 'carlos.gonzalez@universidad.edu',
                'username' => 'carlos_g',
                'estado'   => 'ACTIVO',
            ],
        ]);

        // 5. Verificar que existe en la base de datos
        $this->assertDatabaseHas('usuario', [
            'correo'   => 'carlos.gonzalez@universidad.edu',
            'username' => 'carlos_g',
            'estado'   => 'ACTIVO',
        ]);

        // 6. Verificar disponibilidad inmediata vía GET
        $userId = $response->json('usuario.id_usuario');
        $showResponse = $this->getJson("/api/usuarios/{$userId}", $this->authHeaders());

        $showResponse->assertStatus(200)
            ->assertJson([
                'username' => 'carlos_g',
                'correo'   => 'carlos.gonzalez@universidad.edu',
            ]);
    }

    public function test_password_se_hashea_correctamente_y_no_se_guarda_en_texto_plano(): void
    {
        $payload = $this->validPayload(['password' => 'MiClave99']);

        $this->postJson('/api/usuarios', $payload, $this->authHeaders())
            ->assertStatus(201);

        $usuario = Usuario::where('username', 'carlos_g')->first();

        // El password_hash no debe ser el texto plano
        $this->assertNotEquals('MiClave99', $usuario->password_hash);

        // El hash debe ser verificable con PasswordHasher
        $this->assertTrue(PasswordHasher::verify('MiClave99', $usuario->password_hash));
    }

    public function test_estado_por_defecto_es_activo(): void
    {
        $payload = $this->validPayload();
        unset($payload['estado']); // No enviar estado

        $response = $this->postJson('/api/usuarios', $payload, $this->authHeaders());

        $response->assertStatus(201)
            ->assertJsonPath('usuario.estado', 'ACTIVO');
    }

    public function test_normaliza_estado_en_minusculas_a_mayusculas(): void
    {
        $response = $this->postJson('/api/usuarios', $this->validPayload([
            'estado' => 'activo',
        ]), $this->authHeaders());

        $response->assertCreated()
            ->assertJsonPath('usuario.estado', 'ACTIVO');

        $this->assertDatabaseHas('usuario', [
            'username' => 'carlos_g',
            'estado' => 'ACTIVO',
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // Test 2: Validación de unicidad (correo y username)
    // ─────────────────────────────────────────────────────────────

    public function test_rechaza_correo_duplicado_con_422(): void
    {
        // Crear primer usuario
        $this->postJson('/api/usuarios', $this->validPayload(), $this->authHeaders())
            ->assertStatus(201);

        // Intentar crear segundo usuario con el mismo correo
        $response = $this->postJson('/api/usuarios', $this->validPayload([
            'username' => 'otro_user',
        ]), $this->authHeaders());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['correo']);
    }

    public function test_rechaza_username_duplicado_con_422(): void
    {
        // Crear primer usuario
        $this->postJson('/api/usuarios', $this->validPayload(), $this->authHeaders())
            ->assertStatus(201);

        // Intentar crear segundo usuario con el mismo username
        $response = $this->postJson('/api/usuarios', $this->validPayload([
            'correo' => 'otro@universidad.edu',
        ]), $this->authHeaders());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    }

    // ─────────────────────────────────────────────────────────────
    // Test 3: Validación de contraseña
    // ─────────────────────────────────────────────────────────────

    public function test_rechaza_password_corta(): void
    {
        $response = $this->postJson('/api/usuarios', $this->validPayload([
            'password' => 'Abc1',
        ]), $this->authHeaders());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_rechaza_password_solo_letras(): void
    {
        $response = $this->postJson('/api/usuarios', $this->validPayload([
            'password' => 'SoloLetras',
        ]), $this->authHeaders());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_rechaza_password_solo_numeros(): void
    {
        $response = $this->postJson('/api/usuarios', $this->validPayload([
            'password' => '12345678',
        ]), $this->authHeaders());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    // ─────────────────────────────────────────────────────────────
    // Test 4: Validación de rol
    // ─────────────────────────────────────────────────────────────

    public function test_rechaza_rol_inexistente(): void
    {
        $response = $this->postJson('/api/usuarios', $this->validPayload([
            'id_rol' => 99999,
        ]), $this->authHeaders());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['id_rol']);
    }

    public function test_rechaza_id_rol_no_numerico(): void
    {
        $response = $this->postJson('/api/usuarios', $this->validPayload([
            'id_rol' => 'ADMINISTRADOR',
        ]), $this->authHeaders());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['id_rol']);
    }

    // ─────────────────────────────────────────────────────────────
    // Validación adicional de campos
    // ─────────────────────────────────────────────────────────────

    public function test_rechaza_username_con_caracteres_especiales(): void
    {
        $response = $this->postJson('/api/usuarios', $this->validPayload([
            'username' => 'user@name!',
        ]), $this->authHeaders());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    }

    public function test_rechaza_nombre_demasiado_corto(): void
    {
        $response = $this->postJson('/api/usuarios', $this->validPayload([
            'nombre' => 'A',
        ]), $this->authHeaders());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nombre']);
    }

    public function test_rechaza_peticion_no_autenticada(): void
    {
        $response = $this->postJson('/api/usuarios', $this->validPayload(), [
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(401);
    }

    // ─────────────────────────────────────────────────────────────
    // Autorización por Rol (ADMINISTRADOR requerido)
    // ─────────────────────────────────────────────────────────────

    public function test_usuario_autenticado_con_rol_docente_es_rechazado_con_403(): void
    {
        $docenteUser = Usuario::create([
            'nombre'        => 'Docente',
            'apellido'      => 'Test',
            'correo'        => 'docente@test.com',
            'username'      => 'docente_test',
            'password_hash' => PasswordHasher::hash('Docente1234'),
            'id_rol'        => $this->rolDocente->id_rol,
            'estado'        => 'ACTIVO',
        ]);
        $docenteToken = $docenteUser->createToken('docente-token')->plainTextToken;

        $response = $this->postJson('/api/usuarios', $this->validPayload(), [
            'Authorization' => 'Bearer ' . $docenteToken,
            'Accept'        => 'application/json',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'No tiene permisos para registrar usuarios. Se requiere rol de ADMINISTRADOR.',
            ]);
    }

    public function test_usuario_autenticado_con_rol_control_ingreso_es_rechazado_con_403(): void
    {
        $rolControl = Rol::create([
            'nombre'      => 'CONTROL_INGRESO',
            'descripcion' => 'Personal de control de ingreso',
        ]);

        $controlUser = Usuario::create([
            'nombre'        => 'Control',
            'apellido'      => 'Test',
            'correo'        => 'control@test.com',
            'username'      => 'control_test',
            'password_hash' => PasswordHasher::hash('Control1234'),
            'id_rol'        => $rolControl->id_rol,
            'estado'        => 'ACTIVO',
        ]);
        $controlToken = $controlUser->createToken('control-token')->plainTextToken;

        $response = $this->postJson('/api/usuarios', $this->validPayload(), [
            'Authorization' => 'Bearer ' . $controlToken,
            'Accept'        => 'application/json',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'No tiene permisos para registrar usuarios. Se requiere rol de ADMINISTRADOR.',
            ]);
    }
}
