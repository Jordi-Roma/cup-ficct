<?php

namespace App\Modules\RegistroPostulantes\Services;

use App\Modules\AccesoSeguridad\Models\User;
use App\Modules\RegistroPostulantes\Models\PagoInscripcion;
use App\Modules\RegistroPostulantes\Models\Postulacion;
use App\Modules\RegistroPostulantes\Models\Postulante;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Stripe\Checkout\Session;
use Stripe\StripeClient;

class PagoPostulanteService
{
    private const STRIPE_AMOUNT_CENTS = 5000;

    private const STRIPE_AMOUNT_DECIMAL = 50.00;

    private const STRIPE_CURRENCY = 'usd';

    public function currentState(User $user): array
    {
        $postulante = $this->postulanteFor($user);
        $postulacion = $this->currentPostulacion($postulante);
        $postulacion->loadMissing('gestion');

        return [
            'postulante' => [
                'id_postulante'   => $postulante->id_postulante,
                'nombre_completo' => $user->name,
                'ci'              => $user->ci,
                'correo'          => $user->correo,
            ],
            'postulacion' => [
                'id_postulacion'  => $postulacion->id_postulacion,
                'estado_proceso'  => $postulacion->estado_proceso,
                'estado_admision' => $postulacion->estado_admision,
                'carrera_opcion1' => $postulacion->carreraOpcion1?->nombre,
                'carrera_opcion2' => $postulacion->carreraOpcion2?->nombre,
                'gestion'         => $postulacion->gestion?->nombre,
            ],
            'puede_pagar' => $postulacion->estado_proceso === 'VALIDADO_PENDIENTE_PAGO',
            'monto' => [
                'valor'  => self::STRIPE_AMOUNT_DECIMAL,
                'moneda' => strtoupper(self::STRIPE_CURRENCY),
            ],
        ];
    }

    public function createStripeCheckoutSession(User $user): Session
    {
        $postulante = $this->postulanteFor($user);
        $postulacion = $this->currentPostulacion($postulante);

        $this->ensureCanPay($postulante, $postulacion);

        $client = $this->stripeClient();

        return $client->checkout->sessions->create($this->checkoutSessionPayload($user, $postulante, $postulacion));
    }

    public function confirmStripePayment(User $user, string $sessionId): void
    {
        if ($sessionId === '') {
            throw ValidationException::withMessages([
                'stripe' => 'No se recibio una sesion de pago valida.',
            ]);
        }

        $session = $this->retrieveCheckoutSession($sessionId);

        if ($session->payment_status !== 'paid') {
            throw ValidationException::withMessages([
                'stripe' => 'Stripe todavia no confirmo el pago.',
            ]);
        }

        DB::transaction(function () use ($user, $session): void {
            $postulante = $this->postulanteFor($user);
            $postulacion = $this->currentPostulacion($postulante);
            $metadata = $this->metadataArray($session);

            if ((int) ($metadata['id_postulacion'] ?? 0) !== (int) $postulacion->id_postulacion) {
                throw ValidationException::withMessages([
                    'stripe' => 'La sesion de Stripe no corresponde a esta postulacion.',
                ]);
            }

            if ($postulacion->estado_proceso !== 'VALIDADO_PENDIENTE_PAGO') {
                if ($postulacion->estado_proceso === 'HABILITADO_CUP') {
                    return;
                }

                throw ValidationException::withMessages([
                    'pago' => 'El postulante no tiene un pago pendiente.',
                ]);
            }

            $approvedPayment = PagoInscripcion::query()
                ->where('id_postulacion', $postulacion->id_postulacion)
                ->where('estado_pago', 'APROBADO')
                ->first();

            if (! $approvedPayment) {
                PagoInscripcion::create([
                    'id_postulacion' => $postulacion->id_postulacion,
                    'monto' => self::STRIPE_AMOUNT_DECIMAL,
                    'moneda' => strtoupper(self::STRIPE_CURRENCY),
                    'pasarela' => 'STRIPE',
                    'numero_transaccion' => $session->payment_intent ?: $session->id,
                    'codigo_autorizacion' => $session->id,
                    'estado_pago' => 'APROBADO',
                    'fecha_inicio' => now(),
                    'fecha_confirmacion' => now(),
                ]);
            }

            $postulacion->update([
                'estado_proceso' => 'HABILITADO_CUP',
            ]);

            $user->update([
                'activo' => true,
                'estado_acceso' => 'HABILITADO',
            ]);
        });
    }

    public function checkoutSessionPayload(User $user, Postulante $postulante, Postulacion $postulacion): array
    {
        return [
            'mode' => 'payment',
            'success_url' => route('postulante.pago.exito', [], true).'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('postulante.pago.cancelado', [], true),
            'customer_email' => $this->checkoutCustomerEmail($user),
            'line_items' => [
                [
                    'quantity' => 1,
                    'price_data' => [
                        'currency' => self::STRIPE_CURRENCY,
                        'unit_amount' => self::STRIPE_AMOUNT_CENTS,
                        'product_data' => [
                            'name' => 'Inscripcion CUP-FICCT',
                            'description' => 'Pago de inscripcion al Curso Preuniversitario',
                        ],
                    ],
                ],
            ],
            'metadata' => [
                'id_usuario' => (string) $user->id_usuario,
                'id_postulante' => (string) $postulante->id_postulante,
                'id_postulacion' => (string) $postulacion->id_postulacion,
                'ci' => (string) $user->ci,
            ],
        ];
    }

    protected function stripeClient(): StripeClient
    {
        $secret = config('services.stripe.secret');

        if (! $secret) {
            throw ValidationException::withMessages([
                'stripe' => 'No se configuro STRIPE_SECRET.',
            ]);
        }

        return new StripeClient($secret);
    }

    protected function retrieveCheckoutSession(string $sessionId): Session
    {
        return $this->stripeClient()->checkout->sessions->retrieve($sessionId, []);
    }

    protected function postulanteFor(User $user): Postulante
    {
        $postulante = $user->postulante()
            ->with(['postulaciones.carreraOpcion1', 'postulaciones.carreraOpcion2'])
            ->first();

        if (! $postulante) {
            throw ValidationException::withMessages([
                'postulante' => 'No se encontro un perfil de postulante para este usuario.',
            ]);
        }

        return $postulante;
    }

    protected function currentPostulacion(Postulante $postulante): Postulacion
    {
        $postulacion = $postulante->postulaciones->sortByDesc('fecha_postulacion')->first();

        if (! $postulacion) {
            throw ValidationException::withMessages([
                'postulacion' => 'No se encontro una postulacion activa.',
            ]);
        }

        return $postulacion;
    }

    private function ensureCanPay(Postulante $postulante, Postulacion $postulacion): void
    {
        if ($postulacion->estado_proceso !== 'VALIDADO_PENDIENTE_PAGO') {
            throw ValidationException::withMessages([
                'pago' => 'El postulante no tiene un pago pendiente.',
            ]);
        }

        // Verificar que esta postulación en particular no tenga ya un pago aprobado.
        // No usamos postulante->requiere_pago porque ese flag es de nivel de perfil
        // y puede ser false para cuentas creadas por admin, pero igual deben pagar
        // si hacen una repostulación.
        $pagoAprobado = PagoInscripcion::query()
            ->where('id_postulacion', $postulacion->id_postulacion)
            ->where('estado_pago', 'APROBADO')
            ->exists();

        if ($pagoAprobado) {
            throw ValidationException::withMessages([
                'pago' => 'Ya existe un pago aprobado para esta postulación.',
            ]);
        }
    }

    private function metadataArray(Session $session): array
    {
        if (is_array($session->metadata)) {
            return $session->metadata;
        }

        if (is_object($session->metadata) && method_exists($session->metadata, 'toArray')) {
            return $session->metadata->toArray();
        }

        return (array) $session->metadata;
    }

    private function checkoutCustomerEmail(User $user): string
    {
        return config('services.postulantes.notification_email')
            ?: config('services.postulante_notification_email')
            ?: $user->correo;
    }
}
