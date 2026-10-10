<?php

namespace Tests\Feature;

use App\Models\Permiso;
use App\Models\Rol;
use App\Models\Usuario;
use App\Utilities\PasswordHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Suite de Pruebas para USR-03 / BE-02 (#53):
 * Inmediata invalidación de sesiones y tokens activos cuando se revoca una cuenta.
 *
 * Criterios de Aceptación:
 * 1. Dado que un usuario tiene una sesión activa, cuando un administrador revoca su cuenta,
 *    entonces la sesión debe quedar invalidada.
 * 2. Dado que existen tokens activos asociados al usuario, cuando se revoca la cuenta,
 *    entonces dichos tokens no deben permitir acceso.
 */
class InvalidateSessionOnRevokeTest extends TestCase
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
            'descripcion' => 'Permiso para administrar cuentas de usuario',
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
            'correo'        => 'admin@umss.edu.bo',
            'username'      => 'admin_user',
            'password_hash' => PasswordHasher::hash('AdminPass123'),
            'id_rol'        => $this->rolAdmin->id_rol,
            'estado'        => 'ACTIVO',
        ]);
        $this->adminToken = $this->adminUser->createToken('admin-token')->plainTextToken;

        // Usuario DOCENTE con sesión activa previa
        $this->docenteUser = Usuario::create([
            'nombre'        => 'Carlos',
            'apellido'      => 'Docente',
            'correo'        => 'carlos.docente@umss.edu.bo',
            'username'      => 'cdocente',
            'password_hash' => PasswordHasher::hash('DocentePass123'),
            'id_rol'        => $this->rolDocente->id_rol,
            'estado'        => 'ACTIVO',
        ]);
        $this->docenteToken = $this->docenteUser->createToken('docente-token-web')->plainTextToken;
    }

    /**
     * Criterio 1 & 2: Sesión activa queda invalidada tras revocación y token no permite acceso.
     */
    public function test_sesion_activa_de_usuario_queda_invalidada_inmediatamente_al_revocar_cuenta(): void
    {
        // 1. Verificar que el token inicial es funcional antes de revocar
        $preResponse = $this->getJson('/api/me', [
            'Authorization' => "Bearer {$this->docenteToken}",
            'Accept'        => 'application/json',
        ]);
        $preResponse->assertStatus(200)
            ->assertJsonPath('username', 'cdocente');

        // 2. Administrador revoca la cuenta del docente vía PATCH
        $this->app['auth']->forgetGuards();
        $revokeResponse = $this->patchJson(
            "/api/usuarios/{$this->docenteUser->id_usuario}/revocar",
            [],
            [
                'Authorization' => "Bearer {$this->adminToken}",
                'Accept'        => 'application/json',
            ]
        );
        $revokeResponse->assertStatus(200)
            ->assertJsonPath('data.estado', 'REVOCADO');

        // 3. Verificar que los tokens fueron eliminados de la base de datos
        $this->assertEquals(0, $this->docenteUser->tokens()->count());

        // 4. Intentar acceder a endpoint protegido con el token revocado -> Debe responder 401 Unauthorized
        $this->app['auth']->forgetGuards();

        $postResponse = $this->getJson('/api/me', [
            'Authorization' => "Bearer {$this->docenteToken}",
            'Accept'        => 'application/json',
        ]);
        $postResponse->assertStatus(401);
    }

    /**
     * Criterio: Múltiples tokens concurrentes del usuario son todos invalidados.
     */
    public function test_multiples_tokens_activos_concurrentes_del_mismo_usuario_son_todos_invalidados(): void
    {
        // Crear tokens adicionales (ej. móvil y tablet)
        $tokenMobile = $this->docenteUser->createToken('docente-token-mobile')->plainTextToken;
        $tokenTablet = $this->docenteUser->createToken('docente-token-tablet')->plainTextToken;

        // Comprobar que existen 3 tokens activos antes de revocar
        $this->assertEquals(3, $this->docenteUser->tokens()->count());

        // Administrador revoca la cuenta
        $this->patchJson(
            "/api/usuarios/{$this->docenteUser->id_usuario}/revocar",
            [],
            [
                'Authorization' => "Bearer {$this->adminToken}",
                'Accept'        => 'application/json',
            ]
        )->assertStatus(200);

        // Verificar que todos los tokens fueron eliminados
        $this->assertEquals(0, $this->docenteUser->tokens()->count());

        // Ninguno de los 3 tokens debe permitir acceso
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/me', ['Authorization' => "Bearer {$this->docenteToken}", 'Accept' => 'application/json'])
            ->assertStatus(401);

        $this->app['auth']->forgetGuards();
        $this->getJson('/api/me', ['Authorization' => "Bearer {$tokenMobile}", 'Accept' => 'application/json'])
            ->assertStatus(401);

        $this->app['auth']->forgetGuards();
        $this->getJson('/api/me', ['Authorization' => "Bearer {$tokenTablet}", 'Accept' => 'application/json'])
            ->assertStatus(401);
    }

    /**
     * Criterio: Actualización a estado REVOCADO vía PUT /api/usuarios/{id} también invalida tokens.
     */
    public function test_actualizacion_a_estado_revocado_via_put_invalida_tokens_activos(): void
    {
        // Verificar token activo antes de actualizar
        $this->assertEquals(1, $this->docenteUser->tokens()->count());

        // Administrador actualiza usuario a estado REVOCADO vía PUT
        $updateResponse = $this->putJson(
            "/api/usuarios/{$this->docenteUser->id_usuario}",
            [
                'nombre'   => 'Carlos',
                'apellido' => 'Docente',
                'correo'   => 'carlos.docente@umss.edu.bo',
                'username' => 'cdocente',
                'id_rol'   => $this->rolDocente->id_rol,
                'estado'   => 'REVOCADO',
            ],
            [
                'Authorization' => "Bearer {$this->adminToken}",
                'Accept'        => 'application/json',
            ]
        );
        $updateResponse->assertStatus(200);

        // Verificar que los tokens fueron invalidados
        $this->assertEquals(0, $this->docenteUser->tokens()->count());

        // Petición con el token anterior es rechazada con 401
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/me', [
            'Authorization' => "Bearer {$this->docenteToken}",
            'Accept'        => 'application/json',
        ])->assertStatus(401);
    }

    /**
     * Criterio: La revocación de un usuario NO invalida las sesiones de otros usuarios activos.
     */
    public function test_revocacion_no_afecta_sesiones_ni_tokens_de_otros_usuarios_activos(): void
    {
        // Crear segundo docente activo con su propio token
        $docente2 = Usuario::create([
            'nombre'        => 'María',
            'apellido'      => 'Docente',
            'correo'        => 'maria.docente@umss.edu.bo',
            'username'      => 'mdocente',
            'password_hash' => PasswordHasher::hash('DocentePass123'),
            'id_rol'        => $this->rolDocente->id_rol,
            'estado'        => 'ACTIVO',
        ]);
        $docente2Token = $docente2->createToken('docente2-token')->plainTextToken;

        // Revocar al primer docente
        $this->patchJson(
            "/api/usuarios/{$this->docenteUser->id_usuario}/revocar",
            [],
            [
                'Authorization' => "Bearer {$this->adminToken}",
                'Accept'        => 'application/json',
            ]
        )->assertStatus(200);

        // Token del docente 1 queda invalidado
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/me', [
            'Authorization' => "Bearer {$this->docenteToken}",
            'Accept'        => 'application/json',
        ])->assertStatus(401);

        // Token del docente 2 sigue plenamente activo y funcional (200 OK)
        $this->app['auth']->forgetGuards();
        $docente2Response = $this->getJson('/api/me', [
            'Authorization' => "Bearer {$docente2Token}",
            'Accept'        => 'application/json',
        ]);
        $docente2Response->assertStatus(200)
            ->assertJsonPath('username', 'mdocente');

        // Token del administrador sigue activo y funcional (200 OK)
        $this->app['auth']->forgetGuards();
        $adminResponse = $this->getJson('/api/me', [
            'Authorization' => "Bearer {$this->adminToken}",
            'Accept'        => 'application/json',
        ]);
        $adminResponse->assertStatus(200)
            ->assertJsonPath('username', 'admin_user');
    }

    /**
     * Criterio de autenticación posterior: El usuario revocado no puede generar nuevos tokens vía login.
     */
    public function test_usuario_revocado_no_puede_iniciar_nueva_sesion(): void
    {
        // Revocar al docente
        $this->patchJson(
            "/api/usuarios/{$this->docenteUser->id_usuario}/revocar",
            [],
            [
                'Authorization' => "Bearer {$this->adminToken}",
                'Accept'        => 'application/json',
            ]
        )->assertStatus(200);

        // Intentar iniciar sesión nuevamente con credenciales válidas
        $this->app['auth']->forgetGuards();
        $loginResponse = $this->postJson('/api/login', [
            'username' => 'cdocente',
            'password' => 'DocentePass123',
        ]);

        $loginResponse->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    }
}
