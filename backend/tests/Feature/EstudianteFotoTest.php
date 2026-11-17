<?php

namespace Tests\Feature;

use App\Models\Carrera;
use App\Models\Estudiante;
use App\Models\Facultad;
use App\Models\Rol;
use App\Models\Usuario;
use App\Utilities\PasswordHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EstudianteFotoTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $usuario;
    private Estudiante $estudiante;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $rol = Rol::firstOrCreate(['nombre' => 'ESTUDIANTE'], ['descripcion' => 'Estudiante']);
        $this->usuario = Usuario::create([
            'nombre' => 'Ana', 'apellido' => 'Pérez', 'correo' => 'ana.foto@test.bo',
            'username' => '20260002', 'password_hash' => PasswordHasher::hash('7654321'),
            'id_rol' => $rol->id_rol, 'estado' => 'ACTIVO',
        ]);
        $facultad = Facultad::create(['codigo' => 'FCT', 'nombre' => 'Facultad de Tecnología']);
        $carrera = Carrera::create([
            'id_facultad' => $facultad->id_facultad,
            'codigo' => 'SIS', 'nombre' => 'Ingeniería de Sistemas',
        ]);
        $this->estudiante = Estudiante::create([
            'ci' => '7654321', 'nombre' => 'Ana', 'apellido' => 'Pérez',
            'codigo_universitario' => '20260002', 'id_carrera' => $carrera->id_carrera,
            'correo' => 'ana.foto@test.bo', 'estado' => 'ACTIVO',
            'id_usuario' => $this->usuario->id_usuario,
        ]);
    }

    public function test_acepta_foto_cuadrada_709px_y_la_entrega_con_enlace_firmado(): void
    {
        $respuesta = $this->actingAs($this->usuario)->post('/api/estudiante/foto', [
            'foto' => $this->crearFotoJpeg(709, 709, 600),
        ]);

        $respuesta->assertCreated();
        $this->assertDatabaseHas('estudiante_foto', [
            'id_estudiante' => $this->estudiante->id_estudiante,
            'cloudinary_type' => 'local',
            'estado_validacion' => 'aprobada',
        ]);

        $this->actingAs($this->usuario)->postJson('/api/estudiante/foto', [
            'foto' => $this->crearFotoJpeg(709, 709, 600),
        ])->assertConflict();

        $estado = $this->actingAs($this->usuario)->getJson('/api/estudiante/foto')
            ->assertOk()
            ->assertJsonPath('tiene_foto', true)
            ->assertJsonPath('puede_subir_primera_foto', false);
        $url = $estado->json('url_foto');
        $this->get($url)->assertOk()->assertHeader('Content-Type', 'image/jpeg');
    }

    public function test_rechaza_foto_que_no_cumple_el_tamano_de_impresion(): void
    {
        $this->actingAs($this->usuario)->postJson('/api/estudiante/foto', [
            'foto' => $this->crearFotoJpeg(708, 709, 600),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['foto']);

        $this->assertDatabaseMissing('estudiante_foto', [
            'id_estudiante' => $this->estudiante->id_estudiante,
        ]);
    }

    public function test_rechaza_archivos_mayores_a_cinco_megabytes(): void
    {
        $archivoGrande = UploadedFile::fake()->createWithContent(
            'foto.jpg',
            str_repeat('x', (5 * 1024 * 1024) + 1)
        );

        $this->actingAs($this->usuario)->postJson('/api/estudiante/foto', [
            'foto' => $archivoGrande,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['foto']);
    }

    public function test_rechaza_foto_con_resolucion_distinta_a_600_ppp(): void
    {
        $this->actingAs($this->usuario)->postJson('/api/estudiante/foto', [
            'foto' => $this->crearFotoJpeg(709, 709, 300),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['foto']);
    }

    private function crearFotoJpeg(int $ancho, int $alto, int $ppp): UploadedFile
    {
        $foto = UploadedFile::fake()->image('foto.jpg', $ancho, $alto);
        $bytes = file_get_contents($foto->getRealPath());
        $jfif = pack('C*',
            0xff, 0xe0, 0x00, 0x10,
            0x4a, 0x46, 0x49, 0x46, 0x00, 0x01, 0x01, 0x01,
            ($ppp >> 8) & 0xff, $ppp & 0xff,
            ($ppp >> 8) & 0xff, $ppp & 0xff,
            0x00, 0x00
        );

        file_put_contents($foto->getRealPath(), substr($bytes, 0, 2) . $jfif . substr($bytes, 2));

        return $foto;
    }
}
