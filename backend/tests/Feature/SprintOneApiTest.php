<?php

namespace Tests\Feature;

use App\Models\Estudiante;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class SprintOneApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Sprint 1 includes PostgreSQL-specific migrations. These API tests use
        // an isolated SQLite schema so they never touch a developer database.
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => true,
        ]);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

        Schema::connection('sqlite')->create('rol', function (Blueprint $table): void {
            $table->bigIncrements('id_rol');
            $table->string('nombre', 50)->unique();
            $table->string('descripcion')->nullable();
        });

        Schema::connection('sqlite')->create('usuario', function (Blueprint $table): void {
            $table->bigIncrements('id_usuario');
            $table->string('nombre', 100);
            $table->string('apellido', 100);
            $table->string('correo', 150)->unique();
            $table->string('username', 50)->unique();
            $table->string('password_hash');
            $table->unsignedBigInteger('id_rol');
            $table->string('estado', 20);
            $table->foreign('id_rol')->references('id_rol')->on('rol');
        });

        Schema::connection('sqlite')->create('estudiante', function (Blueprint $table): void {
            $table->bigIncrements('id_estudiante');
            $table->string('ci', 20)->unique();
            $table->string('nombre', 100);
            $table->string('apellido', 100);
            $table->string('codigo_universitario', 50)->unique();
            $table->string('correo', 150)->nullable();
            $table->string('estado', 20);
        });

        Schema::connection('sqlite')->create('personal_access_tokens', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->morphs('tokenable');
            $table->text('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Rol::create(['nombre' => 'ADMINISTRADOR', 'descripcion' => 'Admin']);
        Rol::create(['nombre' => 'DOCENTE', 'descripcion' => 'Docente']);
    }

    public function test_01_active_user_can_log_in(): void
    {
        $user = $this->makeUser();

        $this->postJson('/api/login', ['username' => $user->username, 'password' => 'Password123'])
            ->assertOk()
            ->assertJsonStructure(['token', 'usuario' => ['id', 'username', 'rol']]);
    }

    public function test_02_login_rejects_wrong_password_or_unknown_user(): void
    {
        $this->makeUser();

        $this->postJson('/api/login', ['username' => 'sprint.admin', 'password' => 'wrong-password'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('username');

        $this->postJson('/api/login', ['username' => 'missing-user', 'password' => 'Password123'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('username');
    }

    public function test_03_revoked_user_cannot_log_in(): void
    {
        $user = $this->makeUser(['estado' => 'REVOCADO']);

        $this->postJson('/api/login', ['username' => $user->username, 'password' => 'Password123'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('username');
    }

    public function test_04_protected_api_rejects_requests_without_a_token(): void
    {
        $this->getJson('/api/usuarios')->assertUnauthorized();
        $this->postJson('/api/estudiantes', [])->assertUnauthorized();
    }

    public function test_05_authenticated_user_can_create_a_user(): void
    {
        $response = $this->actingAs($this->makeUser())->postJson('/api/usuarios', [
            'nombre' => 'Ana', 'apellido' => 'Prueba', 'correo' => 'ana@example.test',
            'username' => 'ana.prueba', 'password' => 'Password123', 'id_rol' => 2,
        ]);

        $response->assertCreated()->assertJsonPath('usuario.username', 'ana.prueba');
        $this->assertDatabaseHas('usuario', ['username' => 'ana.prueba', 'estado' => 'ACTIVO']);
        $this->assertDatabaseMissing('usuario', ['username' => 'ana.prueba', 'password_hash' => 'Password123']);
    }

    public function test_06_user_creation_rejects_missing_or_invalid_fields(): void
    {
        $this->actingAs($this->makeUser())->postJson('/api/usuarios', [
            'nombre' => '', 'correo' => 'not-an-email', 'password' => 'short', 'id_rol' => 999,
        ])->assertUnprocessable()->assertJsonValidationErrors(['nombre', 'apellido', 'correo', 'username', 'password', 'id_rol']);

        $this->assertDatabaseCount('usuario', 1);
    }

    public function test_07_user_creation_rejects_duplicate_email_and_username(): void
    {
        $this->makeUser();
        $payload = [
            'nombre' => 'Ana', 'apellido' => 'Prueba', 'correo' => 'new@example.test',
            'username' => 'new.user', 'password' => 'Password123', 'id_rol' => 1,
        ];
        $this->actingAs(Usuario::first())->postJson('/api/usuarios', [...$payload, 'correo' => 'sprint.admin@example.test'])
            ->assertUnprocessable()->assertJsonValidationErrors('correo');
        $this->actingAs(Usuario::first())->postJson('/api/usuarios', [...$payload, 'username' => 'sprint.admin'])
            ->assertUnprocessable()->assertJsonValidationErrors('username');
    }

    public function test_08_user_list_paginates_at_twenty_records(): void
    {
        $this->actingAs($this->makeUser());
        foreach (range(1, 20) as $number) {
            $this->makeUser(['username' => "user{$number}", 'correo' => "user{$number}@example.test"]);
        }

        $this->getJson('/api/usuarios')->assertOk()
            ->assertJsonPath('per_page', 20)->assertJsonCount(20, 'data');
        $this->getJson('/api/usuarios?page=2')->assertOk()
            ->assertJsonPath('current_page', 2)->assertJsonCount(1, 'data');
    }

    public function test_09_user_list_filters_by_role(): void
    {
        $admin = $this->makeUser();
        $this->makeUser(['username' => 'docente', 'correo' => 'docente@example.test', 'id_rol' => 2]);

        $this->actingAs($admin)->getJson('/api/usuarios?rol=2')->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.username', 'docente');
    }

    public function test_10_user_search_is_case_insensitive_across_user_fields(): void
    {
        $admin = $this->makeUser(['nombre' => 'MONICA', 'apellido' => 'LOPEZ']);

        $this->actingAs($admin)->getJson('/api/usuarios?buscar=monica')->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.id_usuario', $admin->id_usuario);
    }

    public function test_11_user_detail_returns_not_found_for_unknown_id(): void
    {
        $this->actingAs($this->makeUser())->getJson('/api/usuarios/999')->assertNotFound();
    }

    public function test_12_user_can_be_updated_and_state_is_normalized(): void
    {
        $admin = $this->makeUser();
        $target = $this->makeUser(['username' => 'target', 'correo' => 'target@example.test']);
        $this->actingAs($admin)->putJson("/api/usuarios/{$target->id_usuario}", [
            'nombre' => 'Actualizado', 'apellido' => 'Prueba', 'correo' => $target->correo,
            'username' => $target->username, 'id_rol' => 2, 'estado' => 'revocado',
        ])->assertOk()->assertJsonPath('estado', 'REVOCADO');
    }

    public function test_13_user_update_rejects_another_users_email(): void
    {
        $admin = $this->makeUser();
        $target = $this->makeUser(['username' => 'target', 'correo' => 'target@example.test']);
        $this->actingAs($admin)->putJson("/api/usuarios/{$target->id_usuario}", [
            'nombre' => 'Target', 'apellido' => 'Test', 'correo' => 'sprint.admin@example.test',
            'username' => $target->username, 'id_rol' => 2, 'estado' => 'ACTIVO',
        ])->assertUnprocessable()->assertJsonValidationErrors('correo');
    }

    public function test_14_authenticated_user_can_create_a_student(): void
    {
        $this->actingAs($this->makeUser())->postJson('/api/estudiantes', $this->studentPayload())
            ->assertCreated()->assertJsonPath('estado', 'ACTIVO');
        $this->assertDatabaseHas('estudiante', ['ci' => '1234567', 'codigo_universitario' => 'C100']);
    }

    public function test_15_student_creation_validates_required_fields_email_and_state(): void
    {
        $this->actingAs($this->makeUser())->postJson('/api/estudiantes', [
            ...$this->studentPayload(), 'ci' => '', 'correo' => 'bad-email', 'estado' => 'PENDIENTE',
        ])->assertUnprocessable()->assertJsonValidationErrors(['ci', 'correo', 'estado']);
        $this->assertDatabaseCount('estudiante', 0);
    }

    public function test_16_student_creation_rejects_duplicate_ci_and_university_code(): void
    {
        $admin = $this->makeUser();
        Estudiante::create($this->studentPayload());

        $this->actingAs($admin)->postJson('/api/estudiantes', [...$this->studentPayload(), 'ci' => '7654321'])
            ->assertUnprocessable()->assertJsonValidationErrors('codigo_universitario');
        $this->actingAs($admin)->postJson('/api/estudiantes', [...$this->studentPayload(), 'codigo_universitario' => 'C200'])
            ->assertUnprocessable()->assertJsonValidationErrors('ci');
    }

    public function test_17_student_search_is_case_insensitive_and_state_filter_works(): void
    {
        $admin = $this->makeUser();
        Estudiante::create($this->studentPayload(['nombre' => 'ALVARO', 'estado' => 'ACTIVO']));
        Estudiante::create($this->studentPayload(['ci' => '7654321', 'codigo_universitario' => 'C200', 'estado' => 'INACTIVO']));

        $this->actingAs($admin)->getJson('/api/estudiantes?buscar=alvaro&estado=ACTIVO')->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.nombre', 'ALVARO');
    }

    public function test_18_student_can_be_updated_and_state_is_normalized(): void
    {
        $admin = $this->makeUser();
        $student = Estudiante::create($this->studentPayload());

        $this->actingAs($admin)->putJson("/api/estudiantes/{$student->id_estudiante}", [
            'nombre' => 'Editado', 'estado' => 'inactivo',
        ])->assertOk()->assertJsonPath('nombre', 'Editado')->assertJsonPath('estado', 'INACTIVO');
    }

    public function test_19_csv_import_accepts_semicolon_utf8_and_trailing_empty_column(): void
    {
        $admin = $this->makeUser();
        $csv = "ci;codigo_universitario;nombre;apellido;correo;estado;\n1234567;C100;José;Muñoz;jose@example.test;activo;\n";
        $file = UploadedFile::fake()->createWithContent('estudiantes.csv', $csv);

        $this->actingAs($admin)->postJson('/api/estudiantes/importar', ['archivo' => $file])
            ->assertOk()->assertJsonPath('importados', 1)->assertJsonPath('errores', []);
        $this->assertDatabaseHas('estudiante', ['ci' => '1234567', 'nombre' => 'José', 'estado' => 'ACTIVO']);
    }

    public function test_20_csv_import_rejects_wrong_headers_and_reports_invalid_rows(): void
    {
        $admin = $this->makeUser();
        $wrongHeaders = UploadedFile::fake()->createWithContent('bad.csv', "ci,nombre\n1,Ana\n");
        $this->actingAs($admin)->postJson('/api/estudiantes/importar', ['archivo' => $wrongHeaders])
            ->assertUnprocessable()->assertJsonPath('message', 'El formato del archivo CSV no es válido.');

        $csv = "ci,codigo_universitario,nombre,apellido,correo,estado\n1234567,C100,Ana,Test,,ACTIVO\n1234567,C200,Repetida,Test,,ACTIVO\n";
        $file = UploadedFile::fake()->createWithContent('rows.csv', $csv);
        $this->actingAs($admin)->postJson('/api/estudiantes/importar', ['archivo' => $file])
            ->assertOk()->assertJsonPath('importados', 1)
            ->assertJson(fn (AssertableJson $json) => $json->has('errores', 1)->where('errores.0.fila', 3)->etc());
        $this->assertDatabaseCount('estudiante', 1);
    }

    private function makeUser(array $overrides = []): Usuario
    {
        static $sequence = 0;
        $sequence++;

        return Usuario::create(array_merge([
            'nombre' => 'Sprint',
            'apellido' => 'Admin',
            'correo' => 'sprint.admin@example.test',
            'username' => 'sprint.admin',
            'password_hash' => Hash::make('Password123'),
            'id_rol' => 1,
            'estado' => 'ACTIVO',
        ], $overrides));
    }

    private function studentPayload(array $overrides = []): array
    {
        return array_merge([
            'ci' => '1234567', 'nombre' => 'José', 'apellido' => 'Muñoz',
            'codigo_universitario' => 'C100', 'correo' => 'jose@example.test', 'estado' => 'ACTIVO',
        ], $overrides);
    }
}
