<?php

namespace Tests\Feature;

use App\Models\Permiso;
use App\Models\Rol;
use App\Models\Usuario;
use App\Utilities\PasswordHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevokeUserAccountTest extends TestCase
{
    use RefreshDatabase;

    private Rol $rolAdmin;
    private Rol $rolDocente;
    private Usuario $adminUser;
    private Usuario $docenteUser;
    private string $adminToken;
    private string $docenteToken;

    protected function setUp(): void
    {
        parent::setUp();

        // Permiso de gestión de usuarios (requerido por PermissionMiddleware)
        $permisoUsuarios = Permiso::create([
            'nombre'      => 'GESTIONAR_USUARIOS',
            'descripcion' => 'Permiso para gestionar usuarios',
        ]);

        // Roles del sistema
        $this->rolAdmin = Rol::create([
            'nombre'      => 'ADMINISTRADOR',
            'descripcion' => 'Administrador del sistema',
        ]);
        $this->rolAdmin->permisos()->attach($permisoUsuarios->id_permiso);

        $this->rolDocente = Rol::create([
            'nombre'      => 'DOCENTE',
            'descripcion' => 'Docente del sistema',
        ]);

        // Usuario ADMINISTRADOR
        $this->adminUser = Usuario::create([
            'nombre'        => 'Admin',
            'apellido'      => 'Sistema',
            'correo'        => 'admin@universidad.edu',
            'username'      => 'admin_user',
            'password_hash' => PasswordHasher::hash('AdminPass123'),
            'id_rol'        => $this->rolAdmin->id_rol,
            'estado'        => 'ACTIVO',
        ]);
        $this->adminToken = $this->adminUser->createToken('admin-token')->plainTextToken;

        // Usuario DOCENTE (objetivo de revocación)
        $this->docenteUser = Usuario::create([
            'nombre'        => 'Carlos',
            'apellido'      => 'Docente',
            'correo'        => 'carlos.docente@universidad.edu',
            'username'      => 'cdocente',
            'password_hash' => PasswordHasher::hash('DocentePass123'),
            'id_rol'        => $this->rolDocente->id_rol,
            'estado'        => 'ACTIVO',
        ]);
        $this->docenteToken = $this->docenteUser->createToken('docente-token')->plainTextToken;
    }

    /**
     * 1. Escenario 1: Administrador revoca cuenta de usuario exitosamente (PATCH).
     * Comprueba status 200, mensaje, estado REVOCADO en BD e invalidación de tokens Sanctum.
     */
    public function test_administrador_revoca_cuenta_de_usuario_exitosamente_con_patch(): void
    {
        // Verificar que el usuario objetivo tiene al menos un token activo antes de revocar
        $this->assertGreaterThan(0, $this->docenteUser->tokens()->count());

        $response = $this->patchJson(
            "/api/usuarios/{$this->docenteUser->id_usuario}/revocar",
            [],
            [
                'Authorization' => "Bearer {$this->adminToken}",
                'Accept'        => 'application/json',
            ]
        );

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Cuenta revocada exitosamente',
                'data'    => [
                    'id_usuario' => $this->docenteUser->id_usuario,
                    'username'   => 'cdocente',
                    'estado'     => 'REVOCADO',
                ],
            ]);

        // Verificar que en la base de datos el estado cambió a REVOCADO
        $this->assertDatabaseHas('usuario', [
            'id_usuario' => $this->docenteUser->id_usuario,
            'estado'     => 'REVOCADO',
        ]);

        // Verificar que los tokens de sesión de Sanctum del usuario revocado fueron eliminados
        $this->assertEquals(0, $this->docenteUser->tokens()->count());
    }

    /**
     * 1b. También soporta verbo POST para clientes o integraciones que lo requieran.
     */
    public function test_administrador_revoca_cuenta_de_usuario_exitosamente_con_post(): void
    {
        // Verificar que el usuario objetivo tiene tokens activos antes de revocar
        $this->assertGreaterThan(0, $this->docenteUser->tokens()->count());

        $response = $this->postJson(
            "/api/usuarios/{$this->docenteUser->id_usuario}/revocar",
            [],
            [
                'Authorization' => "Bearer {$this->adminToken}",
                'Accept'        => 'application/json',
            ]
        );

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Cuenta revocada exitosamente',
                'data'    => [
                    'id_usuario' => $this->docenteUser->id_usuario,
                    'estado'     => 'REVOCADO',
                ],
            ]);

        $this->assertDatabaseHas('usuario', [
            'id_usuario' => $this->docenteUser->id_usuario,
            'estado'     => 'REVOCADO',
        ]);

        // Verificar que los tokens de sesión de Sanctum también fueron eliminados vía POST
        $this->assertEquals(0, $this->docenteUser->tokens()->count());
    }

    /**
     * 2. Escenario: Petición sin autenticación recibe 401 Unauthorized.
     */
    public function test_peticion_sin_autenticacion_retorna_401(): void
    {
        $response = $this->patchJson(
            "/api/usuarios/{$this->docenteUser->id_usuario}/revocar",
            [],
            [
                'Accept' => 'application/json',
            ]
        );

        $response->assertStatus(401);
    }

    /**
     * 3. Escenario: Usuario sin rol de Administrador recibe 403 Forbidden.
     */
    public function test_usuario_sin_rol_administrador_retorna_403(): void
    {
        $response = $this->patchJson(
            "/api/usuarios/{$this->docenteUser->id_usuario}/revocar",
            [],
            [
                'Authorization' => "Bearer {$this->docenteToken}",
                'Accept'        => 'application/json',
            ]
        );

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'No tiene permisos para realizar esta acción.',
            ]);

        // Asegurar que el estado NO fue modificado
        $this->assertDatabaseHas('usuario', [
            'id_usuario' => $this->docenteUser->id_usuario,
            'estado'     => 'ACTIVO',
        ]);
    }

    /**
     * 4. Escenario: Usuario objetivo no existe, retorna 404 Not Found.
     */
    public function test_usuario_inexistente_retorna_404(): void
    {
        $response = $this->patchJson(
            '/api/usuarios/99999/revocar',
            [],
            [
                'Authorization' => "Bearer {$this->adminToken}",
                'Accept'        => 'application/json',
            ]
        );

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Usuario no encontrado.',
            ]);
    }

    /**
     * 5. Edge case: Administrador intenta revocar su propia cuenta, retorna 422 Unprocessable Content.
     */
    public function test_administrador_no_puede_revocar_su_propia_cuenta(): void
    {
        $response = $this->patchJson(
            "/api/usuarios/{$this->adminUser->id_usuario}/revocar",
            [],
            [
                'Authorization' => "Bearer {$this->adminToken}",
                'Accept'        => 'application/json',
            ]
        );

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'No puede revocar su propia cuenta de administrador.',
            ]);

        // Asegurar que la cuenta del admin sigue ACTIVA
        $this->assertDatabaseHas('usuario', [
            'id_usuario' => $this->adminUser->id_usuario,
            'estado'     => 'ACTIVO',
        ]);
    }
}
