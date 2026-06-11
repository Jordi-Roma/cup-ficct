<?php

namespace App\Modules\RegistroPostulantes\Mail;

use App\Modules\AccesoSeguridad\Models\User;
use App\Modules\RegistroPostulantes\Models\PagoInscripcion;
use App\Modules\RegistroPostulantes\Models\Postulacion;
use App\Modules\RegistroPostulantes\Models\Postulante;
use Illuminate\Mail\Mailable;
use Stripe\Checkout\Session;

class PagoInscripcionReceiptMail extends Mailable
{
    public function __construct(
        public readonly User $user,
        public readonly Postulante $postulante,
        public readonly Postulacion $postulacion,
        public readonly PagoInscripcion $pago,
        public readonly ?Session $session = null,
    ) {}

    public function build(): self
    {
        return $this->subject('Recibo de pago CUP-FICCT')
            ->view('emails.pago-inscripcion-recibo')
            ->with([
                'user' => $this->user,
                'postulante' => $this->postulante,
                'postulacion' => $this->postulacion,
                'pago' => $this->pago,
                'session' => $this->session,
                'receiptNumber' => 'PAGO-'.str_pad((string) $this->pago->id_pago, 6, '0', STR_PAD_LEFT),
            ]);
    }
}
