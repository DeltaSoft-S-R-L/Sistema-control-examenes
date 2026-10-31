<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\Qr\InvalidSignatureException;
use App\Exceptions\Qr\TokenMalformedException;
use App\Exceptions\Qr\UnsupportedVersionException;
use App\Http\Controllers\Controller;
use App\Http\Resources\VerificacionResource;
use App\Models\Estudiante;
use App\Models\Examen;
use App\Services\AuditService;
use App\Services\QrTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class VerificacionController extends Controller
{
    /**
     * Helper resiliente para registrar auditoría sin tumbar la verificación en caso de fallo.
     */
    private function auditar(AuditService $auditService, array $datos): void
    {
        try {
            $auditService->registrarVerificacion($datos);
        } catch (\Throwable $e) {
            Log::error('Error al registrar auditoría de verificación: ' . $e->getMessage(), [
                'datos'     => $datos,
                'exception' => $e,
            ]);
        }
    }

    /**
     * Verificar identidad de un estudiante mediante escaneo de código QR (S3-VER01-02 / S3-VER01-05).
     */
    public function verificarPorQr(Request $request, QrTokenService $qrTokenService, AuditService $auditService): JsonResponse
    {
        $examenId = $request->input('examen_id');
        $token = $request->input('token');

        // 1. Validar presencia de examen_id
        if ($examenId === null || $examenId === '' || !is_numeric($examenId)) {
            $this->auditar($auditService, [
                'metodo'            => 'QR',
                'examen_id'         => null,
                'estudiante_id'     => null,
                'resultado'         => 'VALIDACION_FALLIDA',
                'valor_enmascarado' => AuditService::enmascararValor(is_string($token) ? $token : null),
            ]);

            return response()->json([
                'message' => 'El campo examen_id es obligatorio y debe ser numérico.',
                'code'    => 'VALIDACION_FALLIDA',
                'error'   => 'VALIDACION_FALLIDA',
            ], 422);
        }

        // 2. Validar presencia de token
        if ($token === null || $token === '' || !is_string($token)) {
            $this->auditar($auditService, [
                'metodo'            => 'QR',
                'examen_id'         => (int) $examenId,
                'estudiante_id'     => null,
                'resultado'         => 'QR_INVALIDO',
                'valor_enmascarado' => AuditService::enmascararValor((string) $token),
            ]);

            return response()->json([
                'message' => 'El código QR proporcionado es inválido o ha sido alterado.',
                'code'    => 'QR_INVALIDO',
                'error'   => 'QR_INVALIDO',
            ], 422);
        }

        // 3. Validar token criptográfico con QrTokenService
        try {
            $idEstudiante = $qrTokenService->validar($token);
        } catch (TokenMalformedException | InvalidSignatureException | UnsupportedVersionException $e) {
            $this->auditar($auditService, [
                'metodo'            => 'QR',
                'examen_id'         => (int) $examenId,
                'estudiante_id'     => null,
                'resultado'         => 'QR_INVALIDO',
                'valor_enmascarado' => AuditService::enmascararValor($token),
            ]);

            return response()->json([
                'message' => 'El código QR proporcionado es inválido o ha sido alterado.',
                'code'    => 'QR_INVALIDO',
                'error'   => 'QR_INVALIDO',
            ], 422);
        }

        // 4. Buscar estudiante en la base de datos (EST-02)
        $estudiante = Estudiante::find($idEstudiante);
        if (!$estudiante) {
            $this->auditar($auditService, [
                'metodo'            => 'QR',
                'examen_id'         => (int) $examenId,
                'estudiante_id'     => null,
                'resultado'         => 'ESTUDIANTE_NO_ENCONTRADO',
                'valor_enmascarado' => AuditService::enmascararValor($token),
            ]);

            return response()->json([
                'message' => 'Estudiante no encontrado.',
                'code'    => 'ESTUDIANTE_NO_ENCONTRADO',
                'error'   => 'ESTUDIANTE_NO_ENCONTRADO',
            ], 404);
        }

        // 5. Verificar existencia del examen (EXA-01)
        $examen = Examen::with('asignatura')->find((int) $examenId);
        if (!$examen) {
            $this->auditar($auditService, [
                'metodo'        => 'QR',
                'examen_id'     => (int) $examenId,
                'estudiante_id' => $estudiante->id_estudiante,
                'resultado'     => 'EXAMEN_NO_ENCONTRADO',
            ]);

            return response()->json([
                'message' => 'Examen no encontrado.',
                'code'    => 'EXAMEN_NO_ENCONTRADO',
                'error'   => 'EXAMEN_NO_ENCONTRADO',
            ], 404);
        }

        // 6. Criterio de No-Persistencia: No escribir en tablas de dominio (asistencia/ingreso es ING-01/ING-02 Sprint 4)

        // 7. Registrar auditoría de verificación exitosa
        $this->auditar($auditService, [
            'metodo'        => 'QR',
            'examen_id'     => (int) $examenId,
            'estudiante_id' => $estudiante->id_estudiante,
            'resultado'     => 'EXITOSO',
        ]);

        // 8. Retornar recurso estandarizado
        $resource = new VerificacionResource('QR', $estudiante, $examen);

        return response()->json($resource->resolve(), 200);
    }

    /**
     * Verificar identidad de un estudiante mediante CI o código SIS (S3-VER01-03 / S3-VER01-05).
     */
    public function verificarPorDocumento(Request $request, AuditService $auditService): JsonResponse
    {
        // 1. Normalización estricta de entrada
        $rawTipo = $request->input('tipo');
        $tipo = is_string($rawTipo) ? strtoupper(trim($rawTipo)) : $rawTipo;

        $rawValor = $request->input('valor');
        $valor = is_string($rawValor) ? preg_replace('/\s+/', '', mb_strtoupper(trim($rawValor))) : $rawValor;

        $examenId = $request->input('examen_id');

        // 2. Validación de entrada (422)
        $validator = Validator::make([
            'tipo'      => $tipo,
            'valor'     => $valor,
            'examen_id' => $examenId,
        ], [
            'tipo'      => ['required', 'string', Rule::in(['CI', 'SIS'])],
            'valor'     => ['required', 'string', 'max:50'],
            'examen_id' => ['required', 'numeric'],
        ], [
            'tipo.required'      => 'El campo tipo es obligatorio.',
            'tipo.in'            => 'El tipo de documento debe ser CI o SIS.',
            'valor.required'     => 'El valor del documento es obligatorio.',
            'examen_id.required' => 'El campo examen_id es obligatorio y debe ser numérico.',
            'examen_id.numeric'  => 'El campo examen_id es obligatorio y debe ser numérico.',
        ]);

        if ($validator->fails()) {
            $metodoFallback = is_string($tipo) && in_array($tipo, ['CI', 'SIS'], true) ? $tipo : 'DOCUMENTO';
            $this->auditar($auditService, [
                'metodo'            => $metodoFallback,
                'examen_id'         => is_numeric($examenId) ? (int) $examenId : null,
                'estudiante_id'     => null,
                'resultado'         => 'VALIDACION_FALLIDA',
                'valor_enmascarado' => AuditService::enmascararValor(is_string($rawValor) ? $rawValor : null),
            ]);

            return response()->json([
                'message' => $validator->errors()->first(),
                'code'    => 'VALIDACION_FALLIDA',
                'error'   => 'VALIDACION_FALLIDA',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // 3. Reutilización de búsqueda indexada (EST-02)
        $estudiante = Estudiante::buscarPorDocumento($tipo, $valor)->first();

        if (!$estudiante) {
            $this->auditar($auditService, [
                'metodo'            => $tipo,
                'examen_id'         => (int) $examenId,
                'estudiante_id'     => null,
                'resultado'         => 'ESTUDIANTE_NO_ENCONTRADO',
                'valor_enmascarado' => AuditService::enmascararValor(is_string($valor) ? $valor : (string) $rawValor),
            ]);

            return response()->json([
                'message' => 'Estudiante no encontrado.',
                'code'    => 'ESTUDIANTE_NO_ENCONTRADO',
                'error'   => 'ESTUDIANTE_NO_ENCONTRADO',
            ], 404);
        }

        // 4. Verificación de existencia del examen (EXA-01)
        $examen = Examen::with('asignatura')->find((int) $examenId);

        if (!$examen) {
            $this->auditar($auditService, [
                'metodo'        => $tipo,
                'examen_id'     => (int) $examenId,
                'estudiante_id' => $estudiante->id_estudiante,
                'resultado'     => 'EXAMEN_NO_ENCONTRADO',
            ]);

            return response()->json([
                'message' => 'Examen no encontrado.',
                'code'    => 'EXAMEN_NO_ENCONTRADO',
                'error'   => 'EXAMEN_NO_ENCONTRADO',
            ], 404);
        }

        // 5. Criterio de No-Persistencia: No escribir en tablas de dominio (asistencia/ingreso es ING-01/ING-02 Sprint 4)

        // 6. Registrar auditoría de verificación exitosa
        $this->auditar($auditService, [
            'metodo'        => $tipo,
            'examen_id'     => (int) $examenId,
            'estudiante_id' => $estudiante->id_estudiante,
            'resultado'     => 'EXITOSO',
        ]);

        // 7. Retornar recurso estandarizado (metodo: CI | SIS)
        $resource = new VerificacionResource($tipo, $estudiante, $examen);

        return response()->json($resource->resolve(), 200);
    }
}
