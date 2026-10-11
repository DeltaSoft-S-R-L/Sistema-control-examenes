<?php

namespace Tests\Feature;

use App\Models\Permiso;
use App\Models\Rol;
use App\Models\Usuario;
use App\Utilities\PasswordHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Suite de pruebas para verificar el cumplimiento del requerimiento USR-03:
 * Contrato de API con atributo booleano 'is_active' (Accessor/Mutator en Usuario).
 */
class UsuarioIsActiveTest extends TestCase
{
    use RefreshDatabase;

    private Rol $rolAdmin;
    private Rol $rolDocente;
    private Usuario $admin;
    private Usuario $docenteActivo;
    private Usuario $docenteRevocado;
    private string $adminToken;
    private string $docenteToken;

    protected function setUp(): void
    {
        parent::setUp();

        $permisoUsuarios = Permiso::create([
            'nombre'      => 'GESTIONAR_USUARIOS',
            'descripcion' => 'Permiso para gestionar usuarios',
        ]);

        $this->rolAdmin = Rol::create([
            'nombre'      => 'ADMINISTRADOR',
            'descripcion' => 'Administrador del sistema',
        ]);
        $this->rolAdmin->permisos()->attach($permisoUsuarios->id_permiso);

        $this->rolDocente = Rol::create([
            'nombre'      => 'DOCENTE',
            'descripcion' => 'Docente del sistema',
        ]);

        $this->admin = Usuario::create([
            'nombre'        => 'Admin',
            'apellido'      => 'Root',
            'correo'        => 'admin@umss.edu.bo',
            'username'      => 'admin_root',
            'password_hash' => PasswordHasher::hash('Secret123'),
            'id_rol'        => $this->rolAdmin->id_rol,
            'estado'        => 'ACTIVO',
        ]);
        $this->adminToken = $this->admin->createToken('admin-token')->plainTextToken;

        $this->docenteActivo = Usuario::create([
            'nombre'        => 'Carlos',
            'apellido'      => 'Activo',
            'correo'        => 'carlos.activo@umss.edu.bo',
            'username'      => 'carlos_activo',
            'password_hash' => PasswordHasher::hash('Docente123'),
            'id_rol'        => $this->rolDocente->id_rol,
            'estado'        => 'ACTIVO',
        ]);
        $this->docenteToken = $this->docenteActivo->createToken('docente-token')->plainTextToken;

        $this->docenteRevocado = Usuario::create([
            'nombre'        => 'Mario',
            'apellido'      => 'Inactivo',
            'correo'        => 'mario.inactivo@umss.edu.bo',
            'username'      => 'mario_inactivo',
            'password_hash' => PasswordHasher::hash('Docente123'),
            'id_rol'        => $this->rolDocente->id_rol,
            'estado'        => 'REVOCADO',
        ]);
    }

    private function adminHeaders(): array
    {
        return [
            'Authorization' => "Bearer {$this->adminToken}",
            'Accept'        => 'application/json',
        ];
    }

    /**
     * 1. Accessor: is_active es true cuando estado es 'ACTIVO'.
     */
    public function test_accessor_is_active_es_true_cuando_estado_es_activo(): void
    {
        $this->assertTrue($this->docenteActivo->is_active);
    }

    /**
     * 2. Accessor: is_active es false cuando estado es 'REVOCADO' o distinto a ACTIVO.
     */
    public function test_accessor_is_active_es_false_cuando_estado_es_revocado(): void
    {
        $this->assertFalse($this->docenteRevocado->is_active);
    }

    /**
     * 3. Mutator: asignar is_active modifica la columna 'estado' a 'ACTIVO' o 'REVOCADO'.
     */
    public function test_mutator_is_active_asigna_estado_correctamente(): void
    {
        $usuario = new Usuario();
        $usuario->is_active = false;
        $this->assertEquals('REVOCADO', $usuario->estado);

        $usuario->is_active = true;
        $this->assertEquals('ACTIVO', $usuario->estado);
    }

    /**
     * 4. Serialización: toArray() y JSON incluyen 'is_active' como booleano (via $appends).
     */
    public function test_serializacion_incluye_is_active_en_array_y_json(): void
    {
        $arrayActivo = $this->docenteActivo->toArray();
        $this->assertArrayHasKey('is_active', $arrayActivo);
        $this->assertSame(true, $arrayActivo['is_active']);

        $arrayRevocado = $this->docenteRevocado->toArray();
        $this->assertArrayHasKey('is_active', $arrayRevocado);
        $this->assertSame(false, $arrayRevocado['is_active']);
    }

    /**
     * 5. Endpoint /api/me expone 'is_active' booleano en la sesión del usuario.
     */
    public function test_endpoint_me_expone_is_active(): void
    {
        $response = $this->getJson('/api/me', [
            'Authorization' => "Bearer {$this->docenteToken}",
            'Accept'        => 'application/json',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('is_active', true)
            ->assertJsonPath('estado', 'ACTIVO');
    }

    /**
     * 6. Endpoint /api/usuarios/{id} expone 'is_active' en consulta individual.
     */
    public function test_endpoint_usuarios_show_expone_is_active(): void
    {
        $response = $this->getJson(
            "/api/usuarios/{$this->docenteActivo->id_usuario}",
            $this->adminHeaders()
        );

        $response->assertStatus(200)
            ->assertJsonPath('is_active', true);

        $responseRevocado = $this->getJson(
            "/api/usuarios/{$this->docenteRevocado->id_usuario}",
            $this->adminHeaders()
        );

        $responseRevocado->assertStatus(200)
            ->assertJsonPath('is_active', false);
    }

    /**
     * 7. Endpoint /api/usuarios expone 'is_active' en listado paginado.
     */
    public function test_endpoint_usuarios_index_expone_is_active(): void
    {
        $response = $this->getJson('/api/usuarios', $this->adminHeaders());

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id_usuario',
                        'nombre',
                        'apellido',
                        'username',
                        'estado',
                        'is_active',
                    ],
                ],
            ]);
    }

    /**
     * 8. Endpoint /api/usuarios/{id}/revocar retorna is_active = false tras revocar.
     */
    public function test_endpoint_revocar_retorna_is_active_false(): void
    {
        $response = $this->patchJson(
            "/api/usuarios/{$this->docenteActivo->id_usuario}/revocar",
            [],
            $this->adminHeaders()
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.is_active', false)
            ->assertJsonPath('data.estado', 'REVOCADO');

        $this->docenteActivo->refresh();
        $this->assertFalse($this->docenteActivo->is_active);
    }

    /**
     * 9. Endpoint PUT /api/usuarios/{id} permite enviar is_active y actualiza estado y sesiones.
     */
    public function test_endpoint_update_acepta_is_active_y_persiste(): void
    {
        $response = $this->putJson(
            "/api/usuarios/{$this->docenteActivo->id_usuario}",
            [
                'nombre'    => 'Carlos',
                'apellido'  => 'Modificado',
                'correo'    => 'carlos.activo@umss.edu.bo',
                'username'  => 'carlos_activo',
                'id_rol'    => $this->rolDocente->id_rol,
                'is_active' => false,
            ],
            $this->adminHeaders()
        );

        $response->assertStatus(200)
            ->assertJsonPath('is_active', false)
            ->assertJsonPath('estado', 'REVOCADO');

        $this->docenteActivo->refresh();
        $this->assertFalse($this->docenteActivo->is_active);
        $this->assertEquals('REVOCADO', $this->docenteActivo->estado);
        $this->assertEquals(0, $this->docenteActivo->tokens()->count());
    }
}
