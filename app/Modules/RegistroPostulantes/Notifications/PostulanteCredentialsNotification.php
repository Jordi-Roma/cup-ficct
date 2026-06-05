<?php

namespace App\Modules\RegistroPostulantes\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PostulanteCredentialsNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $nombreCompleto,
        private readonly string $ci,
        private readonly string $username,
        private readonly string $password,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Credenciales de postulante CUP-FICCT')
            ->greeting('Credenciales generadas')
            ->line('Nombre: '.$this->nombreCompleto)
            ->line('CI: '.$this->ci)
            ->line('Username: '.$this->username)
            ->line('Contrasena: '.$this->password)
            ->line('Estado del proceso: VALIDADO_PENDIENTE_PAGO')
            ->line('Debe iniciar sesion y completar el pago de inscripcion.');
    }
}
