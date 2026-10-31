<?php

namespace App\Mail;

use App\Models\Usuario;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BienvenidaUsuarioMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
    public Usuario $usuario,
    public ?string $enlaceConfiguracion = null
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Bienvenido al Sistema de Control de Exámenes - DeltaSoft S.R.L.'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.bienvenida-usuario'
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
