<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use App\Models\Examen;
use App\Services\AuditoriaService;
use App\Services\HabilitacionService;
use App\Services\QrTokenService;
use Illuminate\Http\Request;

class VerificacionController extends Controller
{
    public function __construct(
        protected HabilitacionService $habilitacionService
    ) {}

    /**
     * Verificación de estudiante mediante Código QR.
     * POST /api/verificar/qr
     */
    public function verificarQr(Request $request)
    {
        $request->validate([
            'qr_token'  => 'nullable|string',
            'token'     => 'nullable|string',
            'id_examen' => 'required|exists:examen,id_examen',
        ]);

        $token = trim((string) ($request->input('qr_token') ?? $request->input('token') ?? ''));
        $examen = Examen::findOrFail($request->input('id_examen'));

        if ($token === '') {
            return response()->json([
                'message'      => 'El token QR es requerido.',
                'identificado' => false,
            ], 422);
        }

        $resultadoToken = QrTokenService::validarToken($token);

        if (!$resultadoToken['valido']) {
            // Registro de auditoría de intento fallido por token QR inválido o alterado
            AuditoriaService::registrar(
                $request->user()?->id_usuario,
                'VERIFICACION_QR',
                'ESTUDIANTE',
                null,
                'FALLO',
                "Intento de verificación QR fallido. Error: {$resultadoToken['error']} para examen ID {$examen->id_examen}."
            );

            $mensajeError = $resultadoToken['error'] === 'QR_ALTERADO'
                ? 'El código QR ha sido alterado o la firma es inválida.'
                : 'El formato del token QR no es válido.';

            return response()->json([
                'message'      => $mensajeError,
                'error'        => $resultadoToken['error'],
                'identificado' => false,
            ], 422);
        }

        $idEstudiante = $resultadoToken['datos']['id_estudiante'] ?? null;
        $estudiante = $idEstudiante ? Estudiante::with('carrera')->find($idEstudiante) : null;

        return $this->procesarResultadoVerificacion($estudiante, $examen, 'QR', $request);
    }

    /**
     * Verificación de estudiante mediante Cédula de Identidad (CI) o Código SIS.
     * POST /api/verificar/documento
     */
    public function verificarDocumento(Request $request)
    {
        $request->validate([
            'identificador' => 'nullable|string',
            'ci_sis'        => 'nullable|string',
            'documento'     => 'nullable|string',
            'id_examen'     => 'required|exists:examen,id_examen',
        ]);

        $identificador = trim((string) (
            $request->input('identificador') ??
            $request->input('ci_sis') ??
            $request->input('documento') ??
            ''
        ));

        $examen = Examen::findOrFail($request->input('id_examen'));

        if ($identificador === '') {
            return response()->json([
                'message'      => 'Debe ingresar un número de CI o Código SIS.',
                'identificado' => false,
            ], 422);
        }

        // Búsqueda insensible a mayúsculas/minúsculas por CI o Código SIS
        $estudiante = Estudiante::with('carrera')
            ->where(function ($query) use ($identificador) {
                $query->where('ci', $identificador)
                      ->orWhere('codigo_universitario', $identificador);
            })
            ->first();

        return $this->procesarResultadoVerificacion($estudiante, $examen, 'CI_SIS', $request);
    }

    /**
     * Método compartido y unificado para procesar la verificación y auditoría.
     * Garantiza que QR y CI/SIS compartan la misma lógica sin duplicidad.
     */
    protected function procesarResultadoVerificacion(?Estudiante $estudiante, Examen $examen, string $metodo, Request $request)
    {
        // 1. Si la identificación falla: NO comprobar habilitación
        if (!$estudiante) {
            AuditoriaService::registrar(
                $request->user()?->id_usuario,
                "VERIFICACION_{$metodo}",
                'ESTUDIANTE',
                null,
                'FALLO',
                "Identificación fallida por {$metodo}. Estudiante no encontrado para examen ID {$examen->id_examen}."
            );

            return response()->json([
                'message'      => 'Estudiante no encontrado en el sistema.',
                'identificado' => false,
            ], 404);
        }

        // 2. Si el estudiante fue identificado: llamar a HabilitacionService::comprobar
        $habilitacion = $this->habilitacionService->comprobar($examen->id_examen, $estudiante->id_estudiante);

        // 3. Registrar auditoría con el resultado de la habilitación
        $resultadoAuditoria = $habilitacion->habilitado ? 'EXITO' : 'FALLO';
        $detalleAuditoria = "Verificación por {$metodo} para examen ID {$examen->id_examen} ('{$examen->nombre}'). " .
            "Estudiante: {$estudiante->nombre} {$estudiante->apellido} (CI: {$estudiante->ci}, SIS: {$estudiante->codigo_universitario}). " .
            "Estado: {$habilitacion->estado}" .
            ($habilitacion->motivo ? " - Motivo: {$habilitacion->motivo}" : "");

        AuditoriaService::registrar(
            $request->user()?->id_usuario,
            "VERIFICACION_{$metodo}",
            'ESTUDIANTE',
            $estudiante->id_estudiante,
            $resultadoAuditoria,
            $detalleAuditoria
        );

        // 4. Retornar respuesta extendida con el bloque habilitacion (sin escribir en tablas de dominio)
        return response()->json([
            'identificado' => true,
            'metodo'       => $metodo,
            'estudiante'   => [
                'id_estudiante'        => $estudiante->id_estudiante,
                'nombre'               => $estudiante->nombre,
                'apellido'             => $estudiante->apellido,
                'ci'                   => $estudiante->ci,
                'codigo_universitario' => $estudiante->codigo_universitario,
                'correo'               => $estudiante->correo,
                'carrera'              => $estudiante->carrera?->nombre ?? null,
                'estado'               => $estudiante->estado,
            ],
            'examen' => [
                'id_examen' => $examen->id_examen,
                'nombre'    => $examen->nombre,
                'fecha'     => $examen->fecha ? (is_string($examen->fecha) ? substr($examen->fecha, 0, 10) : $examen->fecha->format('Y-m-d')) : null,
            ],
            'habilitacion' => [
                'estado'          => $habilitacion->estado,
                'habilitado'      => $habilitacion->habilitado,
                'motivo'          => $habilitacion->motivo,
                'id_habilitacion' => $habilitacion->id_habilitacion,
            ],
        ]);
    }

    /**
     * Endpoint utilitario para generar tokens QR firmados de prueba.
     * GET/POST /api/verificar/generar-qr/{id_estudiante}
     */
    public function generarQrPrueba(string $idEstudiante)
    {
        $estudiante = Estudiante::findOrFail($idEstudiante);
        $token = QrTokenService::generarToken($estudiante);

        return response()->json([
            'token'      => $token,
            'estudiante' => [
                'id_estudiante'        => $estudiante->id_estudiante,
                'nombre_completo'      => "{$estudiante->nombre} {$estudiante->apellido}",
                'ci'                   => $estudiante->ci,
                'codigo_universitario' => $estudiante->codigo_universitario,
            ],
        ]);
    }
}
