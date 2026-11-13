<?php

namespace App\Http\Controllers\Api;

use App\Enums\AccionAuditoria;
use App\Enums\CodigoErrorVerificacion;
use App\Enums\MotivoIntentoIngreso;
use App\Exceptions\EstudianteNoAsociadoException;
use App\Exceptions\Qr\InvalidSignatureException;
use App\Exceptions\Qr\TokenMalformedException;
use App\Exceptions\Qr\UnsupportedVersionException;
use App\Http\Controllers\Controller;
use App\Http\Resources\VerificacionResource;
use App\Models\AsignacionAmbiente;
use App\Models\Estudiante;
use App\Models\Examen;
use App\Models\Habilitacion;
use App\Models\IntentoIngreso;
use App\Services\AmbienteValidacionService;
use App\Services\AuditService;
use App\Services\PertenenciaExamenService;
use App\Services\QrTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class VerificacionController extends Controller
{
    /**
     * Helper resiliente para registrar auditoría sin tumbar la verificación en caso de fallo (VER-01).
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
     * POST /api/verificaciones/qr
     */
    public function verificarPorQr(
        Request $request,
        QrTokenService $qrTokenService,
        AuditService $auditService,
        PertenenciaExamenService $pertenenciaService
    ): JsonResponse
    {
        $examenId = $request->input('examen_id');
        $token = $request->input('token');

        // 1. Validar presencia de examen_id
        if ($examenId === null || $examenId === '' || !is_numeric($examenId)) {
            $this->auditar($auditService, [
                'metodo'            => 'QR',
                'examen_id'         => null,
                'estudiante_id'     => null,
                'resultado'         => CodigoErrorVerificacion::VALIDACION_FALLIDA->value,
                'valor_enmascarado' => AuditService::enmascararValor(is_string($token) ? $token : null),
            ]);

            return response()->json([
                'message' => 'El campo examen_id es obligatorio y debe ser numérico.',
                'code'    => CodigoErrorVerificacion::VALIDACION_FALLIDA->value,
                'error'   => CodigoErrorVerificacion::VALIDACION_FALLIDA->value,
            ], 422);
        }

        // 2. Validar presencia de token
        if ($token === null || $token === '' || !is_string($token)) {
            $this->auditar($auditService, [
                'metodo'            => 'QR',
                'examen_id'         => (int) $examenId,
                'estudiante_id'     => null,
                'resultado'         => CodigoErrorVerificacion::QR_INVALIDO->value,
                'valor_enmascarado' => AuditService::enmascararValor((string) $token),
            ]);

            return response()->json([
                'message' => 'El código QR proporcionado es inválido o ha sido alterado.',
                'code'    => CodigoErrorVerificacion::QR_INVALIDO->value,
                'error'   => CodigoErrorVerificacion::QR_INVALIDO->value,
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
                'resultado'         => CodigoErrorVerificacion::QR_INVALIDO->value,
                'valor_enmascarado' => AuditService::enmascararValor($token),
            ]);

            return response()->json([
                'message' => 'El código QR proporcionado es inválido o ha sido alterado.',
                'code'    => CodigoErrorVerificacion::QR_INVALIDO->value,
                'error'   => CodigoErrorVerificacion::QR_INVALIDO->value,
            ], 422);
        }

        // 4. Buscar estudiante en la base de datos (EST-02)
        $estudiante = Estudiante::find($idEstudiante);
        if (!$estudiante) {
            $this->auditar($auditService, [
                'metodo'            => 'QR',
                'examen_id'         => (int) $examenId,
                'estudiante_id'     => null,
                'resultado'         => CodigoErrorVerificacion::ESTUDIANTE_NO_ENCONTRADO->value,
                'valor_enmascarado' => AuditService::enmascararValor($token),
            ]);

            return response()->json([
                'message' => 'Estudiante no encontrado.',
                'code'    => CodigoErrorVerificacion::ESTUDIANTE_NO_ENCONTRADO->value,
                'error'   => CodigoErrorVerificacion::ESTUDIANTE_NO_ENCONTRADO->value,
            ], 404);
        }

        // 5. Verificar existencia del examen (EXA-01)
        $examen = Examen::with('asignatura')->find((int) $examenId);
        if (!$examen) {
            $this->auditar($auditService, [
                'metodo'        => 'QR',
                'examen_id'     => (int) $examenId,
                'estudiante_id' => $estudiante->id_estudiante,
                'resultado'     => CodigoErrorVerificacion::EXAMEN_NO_ENCONTRADO->value,
            ]);

            return response()->json([
                'message' => 'Examen no encontrado.',
                'code'    => CodigoErrorVerificacion::EXAMEN_NO_ENCONTRADO->value,
                'error'   => CodigoErrorVerificacion::EXAMEN_NO_ENCONTRADO->value,
            ], 404);
        }

        // 6. Validar pertenencia del estudiante al examen (VER-03 / S4-VER03-01 / S4-VER03-02)
        try {
            $pertenenciaService->verificarPertenencia((int) $estudiante->id_estudiante, (int) $examen->id_examen);
        } catch (EstudianteNoAsociadoException $e) {
            $this->registrarIntentoIndebidoExamenAjeno($request, $estudiante, (int) $examen->id_examen, 'QR');

            return response()->json([
                'message' => 'El estudiante no pertenece a este examen.',
                'code'    => CodigoErrorVerificacion::NO_ASOCIADO->value,
                'error'   => CodigoErrorVerificacion::NO_ASOCIADO->value,
            ], 422);
        }

        // 7. Criterio de No-Persistencia: No escribir en tablas de dominio (asistencia/ingreso es ING-01/ING-02 Sprint 4)

        // 8. Registrar auditoría de verificación exitosa
        $this->auditar($auditService, [
            'metodo'        => 'QR',
            'examen_id'     => (int) $examenId,
            'estudiante_id' => $estudiante->id_estudiante,
            'resultado'     => 'EXITOSO',
        ]);

        // 9. Retornar recurso estandarizado
        $resource = new VerificacionResource('QR', $estudiante, $examen);

        return response()->json($resource->resolve(), 200);
    }

    /**
     * Verificar identidad de un estudiante mediante CI o código SIS (S3-VER01-03 / S3-VER01-05).
     * POST /api/verificaciones/documento
     */
    public function verificarPorDocumento(
        Request $request,
        AuditService $auditService,
        PertenenciaExamenService $pertenenciaService
    ): JsonResponse
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
                'resultado'         => CodigoErrorVerificacion::VALIDACION_FALLIDA->value,
                'valor_enmascarado' => AuditService::enmascararValor(is_string($rawValor) ? $rawValor : null),
            ]);

            return response()->json([
                'message' => $validator->errors()->first(),
                'code'    => CodigoErrorVerificacion::VALIDACION_FALLIDA->value,
                'error'   => CodigoErrorVerificacion::VALIDACION_FALLIDA->value,
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
                'resultado'         => CodigoErrorVerificacion::ESTUDIANTE_NO_ENCONTRADO->value,
                'valor_enmascarado' => AuditService::enmascararValor(is_string($valor) ? $valor : (string) $rawValor),
            ]);

            return response()->json([
                'message' => 'Estudiante no encontrado.',
                'code'    => CodigoErrorVerificacion::ESTUDIANTE_NO_ENCONTRADO->value,
                'error'   => CodigoErrorVerificacion::ESTUDIANTE_NO_ENCONTRADO->value,
            ], 404);
        }

        // 4. Verificación de existencia del examen (EXA-01)
        $examen = Examen::with('asignatura')->find((int) $examenId);

        if (!$examen) {
            $this->auditar($auditService, [
                'metodo'        => $tipo,
                'examen_id'     => (int) $examenId,
                'estudiante_id' => $estudiante->id_estudiante,
                'resultado'     => CodigoErrorVerificacion::EXAMEN_NO_ENCONTRADO->value,
            ]);

            return response()->json([
                'message' => 'Examen no encontrado.',
                'code'    => CodigoErrorVerificacion::EXAMEN_NO_ENCONTRADO->value,
                'error'   => CodigoErrorVerificacion::EXAMEN_NO_ENCONTRADO->value,
            ], 404);
        }

        // 5. Validar pertenencia del estudiante al examen (VER-03 / S4-VER03-01 / S4-VER03-02)
        try {
            $pertenenciaService->verificarPertenencia((int) $estudiante->id_estudiante, (int) $examen->id_examen);
        } catch (EstudianteNoAsociadoException $e) {
            $this->registrarIntentoIndebidoExamenAjeno($request, $estudiante, (int) $examen->id_examen, 'DOCUMENTO');

            return response()->json([
                'message' => 'El estudiante no pertenece a este examen.',
                'code'    => CodigoErrorVerificacion::NO_ASOCIADO->value,
                'error'   => CodigoErrorVerificacion::NO_ASOCIADO->value,
            ], 422);
        }

        // 6. Criterio de No-Persistencia: No escribir en tablas de dominio (asistencia/ingreso es ING-01/ING-02 Sprint 4)

        // 7. Registrar auditoría de verificación exitosa
        $this->auditar($auditService, [
            'metodo'        => $tipo,
            'examen_id'     => (int) $examenId,
            'estudiante_id' => $estudiante->id_estudiante,
            'resultado'     => 'EXITOSO',
        ]);

        // 8. Retornar recurso estandarizado (metodo: CI | SIS)
        $resource = new VerificacionResource($tipo, $estudiante, $examen);

        return response()->json($resource->resolve(), 200);
    }

    /**
     * Registrar intento indebido de ingreso a examen ajeno y su correspondiente auditoría (S4-VER03-02).
     */
    private function registrarIntentoIndebidoExamenAjeno(Request $request, Estudiante $estudiante, int $idExamen, string $metodo): void
    {
        $idUsuario = Auth::guard('sanctum')->id()
            ?? $request->user()?->id_usuario
            ?? $request->user()?->id;

        $idAsignacionAmbiente = $request->input('id_asignacion_ambiente')
            ?? $request->input('asignacion_ambiente_id')
            ?? $request->input('ambiente_id');

        // 1. Persistir en la tabla existente intento_ingreso con tolerancia a fallos
        try {
            IntentoIngreso::create([
                'id_estudiante'          => $estudiante->id_estudiante,
                'id_examen'              => $idExamen,
                'id_asignacion_ambiente' => $idAsignacionAmbiente ? (int) $idAsignacionAmbiente : null,
                'id_usuario'             => $idUsuario ? (int) $idUsuario : null,
                'fecha_hora'             => now(),
                'motivo'                 => MotivoIntentoIngreso::EXAMEN_NO_CORRESPONDIENTE->value,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al persistir intento de ingreso indebido: ' . $e->getMessage(), [
                'id_estudiante' => $estudiante->id_estudiante,
                'id_examen'     => $idExamen,
                'exception'     => $e,
            ]);
        }

        // 2. Registrar en audit_logs mediante AuditService con tolerancia a fallos
        try {
            AuditService::registrar(
                accion: AccionAuditoria::INTENTO_INGRESO_EXAMEN_AJENO->value,
                entidad: 'estudiante',
                idRegistro: (string) $estudiante->id_estudiante,
                informacion: [
                    'id_examen_intentado' => $idExamen,
                    'metodo'              => $metodo,
                    'motivo'              => MotivoIntentoIngreso::EXAMEN_NO_CORRESPONDIENTE->value,
                ],
                idUsuario: $idUsuario
            );
        } catch (\Throwable $e) {
            Log::error('Error al registrar auditoría de intento de ingreso indebido: ' . $e->getMessage(), [
                'id_estudiante' => $estudiante->id_estudiante,
                'id_examen'     => $idExamen,
                'exception'     => $e,
            ]);
        }
    }

    /**
     * Verificar discrepancia de ambiente asignado vs actual del controlador de puerta (ING-04).
     * POST /api/verificaciones/ambiente
     */
    public function verificarAmbiente(
        Request $request,
        AmbienteValidacionService $ambienteService
    ): JsonResponse {
        $validated = $request->validate([
            'id_examen'              => ['required', 'integer', 'exists:examen,id_examen'],
            'id_asignacion_ambiente' => ['required', 'integer', 'exists:asignacion_ambiente,id_asignacion_ambiente'],
            'id_habilitacion'        => ['nullable', 'integer', 'exists:habilitacion,id_habilitacion'],
            'id_estudiante'          => ['nullable', 'integer', 'exists:estudiante,id_estudiante'],
        ], [
            'id_examen.required'              => 'El campo id_examen es obligatorio.',
            'id_examen.exists'                => 'El examen especificado no existe.',
            'id_asignacion_ambiente.required' => 'El campo id_asignacion_ambiente es obligatorio.',
            'id_asignacion_ambiente.exists'   => 'La asignación de ambiente especificada no existe.',
        ]);

        if (empty($validated['id_habilitacion']) && empty($validated['id_estudiante'])) {
            return response()->json([
                'message' => 'Debe proporcionar id_habilitacion o id_estudiante.',
                'code'    => CodigoErrorVerificacion::DATOS_INSUFICIENTES->value,
                'error'   => CodigoErrorVerificacion::DATOS_INSUFICIENTES->value,
            ], 422);
        }

        $idUsuario = Auth::guard('sanctum')->id()
            ?? $request->user()?->id_usuario
            ?? $request->user()?->id;

        if (!$idUsuario) {
            return response()->json([
                'message' => 'No autenticado.',
            ], 401);
        }

        // Buscar habilitación
        $habilitacion = null;
        if (!empty($validated['id_habilitacion'])) {
            $habilitacion = Habilitacion::with(['estudiante', 'asignacionAmbiente.ambiente'])->find($validated['id_habilitacion']);
        } elseif (!empty($validated['id_estudiante'])) {
            $habilitacion = Habilitacion::with(['estudiante', 'asignacionAmbiente.ambiente'])
                ->where('id_estudiante', $validated['id_estudiante'])
                ->where('id_examen', $validated['id_examen'])
                ->first();
        }

        if (!$habilitacion || (int) $habilitacion->id_examen !== (int) $validated['id_examen']) {
            return response()->json([
                'message' => 'El estudiante no pertenece a este examen.',
                'code'    => CodigoErrorVerificacion::NO_ASOCIADO->value,
                'error'   => CodigoErrorVerificacion::NO_ASOCIADO->value,
            ], 422);
        }

        // Validar estado de HABILITADO
        if (strtoupper(trim((string) $habilitacion->estado)) !== 'HABILITADO') {
            return response()->json([
                'message' => 'El estudiante no se encuentra habilitado para este examen.',
                'code'    => CodigoErrorVerificacion::NO_HABILITADO->value,
                'error'   => CodigoErrorVerificacion::NO_HABILITADO->value,
            ], 403);
        }

        // Validar que la asignación de ambiente pertenezca al examen
        $asignacionAmbiente = AsignacionAmbiente::find($validated['id_asignacion_ambiente']);
        if (!$asignacionAmbiente || (int) $asignacionAmbiente->id_examen !== (int) $validated['id_examen']) {
            return response()->json([
                'message' => 'El ambiente seleccionado no corresponde a este examen.',
                'code'    => CodigoErrorVerificacion::AMBIENTE_NO_CORRESPONDE->value,
                'error'   => CodigoErrorVerificacion::AMBIENTE_NO_CORRESPONDE->value,
            ], 422);
        }

        // Validar correspondencia
        $resultado = $ambienteService->validarCorrespondencia(
            $habilitacion,
            (int) $validated['id_asignacion_ambiente'],
            (int) $idUsuario
        );

        if (!$resultado['valido']) {
            return response()->json([
                'message'           => 'El estudiante está habilitado, pero le corresponde ingresar en otro ambiente.',
                'code'              => CodigoErrorVerificacion::AULA_INCORRECTA->value,
                'error'             => CodigoErrorVerificacion::AULA_INCORRECTA->value,
                'ambiente_asignado' => $resultado['ambiente_asignado'],
            ], 422);
        }

        return response()->json([
            'valido'            => true,
            'coincide'          => true,
            'message'           => 'El ambiente corresponde al estudiante.',
            'ambiente_asignado' => $resultado['ambiente_asignado'],
        ], 200);
    }
}
