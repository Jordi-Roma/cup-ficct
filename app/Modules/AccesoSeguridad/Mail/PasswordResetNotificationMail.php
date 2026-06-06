<?php

namespace App\Modules\AccesoSeguridad\Mail;

use Illuminate\Mail\Mailable;

class PasswordResetNotificationMail extends Mailable
{
    public function __construct(
        public readonly string $body,
    ) {}

    public function build(): self
    {
        return $this->subject('Recuperacion de contrasena - CUP-FICCT')
            ->text('emails.password-reset-institutional')
            ->with(['body' => $this->body]);
    }
}
