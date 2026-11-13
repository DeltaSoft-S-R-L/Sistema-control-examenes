<?php

namespace App\Services;

use App\Mail\BienvenidaUsuarioMail;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;

class ActivacionCuentaService
{
    /**
     * Genera un enlace temporal y envía el correo.
     *
     * El usuario debe existir previamente.
     * El método no crea cuentas ni modifica estudiantes.
     */
    public function enviarEnlace(Usuario $usuario): void
    {
        if (!$usuario->correo) {
            throw new RuntimeException(
                'El usuario no tiene un correo registrado.'
            );
        }

        if (strtoupper((string) $usuario->estado) !== 'ACTIVO') {
            throw new RuntimeException(
                'La cuenta no está activa.'
            );
        }

        $frontendUrl = config('app.frontend_url');

        if (!is_string($frontendUrl) || trim($frontendUrl) === '') {
            throw new RuntimeException(
                'No está configurada la URL del frontend.'
            );
        }

        // Token seguro: 64 caracteres aleatorios.
        $token = Str::random(64);

        // Nunca almacenar el token original.
        $tokenHash = hash('sha256', $token);

        // Guardar un único enlace vigente por usuario.
        DB::transaction(function () use ($usuario, $tokenHash) {
            DB::table('password_setup_tokens')
                ->where('id_usuario', $usuario->id_usuario)
                ->delete();

            DB::table('password_setup_tokens')->insert([
                'id_usuario' => $usuario->id_usuario,
                'token_hash' => $tokenHash,
                'expires_at' => now()->addMinutes(30),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $enlace = rtrim($frontendUrl, '/')
            . '/configurar-contrasena?token='
            . urlencode($token);

        // Cargar el rol para mostrarlo en el correo.
        $usuario->loadMissing('rol');

        Mail::to($usuario->correo)->send(
            new BienvenidaUsuarioMail($usuario, $enlace)
        );
    }
}
