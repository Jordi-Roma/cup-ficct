<?php

namespace Tests\Feature\RegistroPostulantes;

use App\Modules\AccesoSeguridad\Models\Permiso;
use App\Modules\AccesoSeguridad\Models\Rol;
use App\Modules\AccesoSeguridad\Models\User;
use App\Modules\GestionAcademica\Models\Carrera;
use App\Modules\GestionAcademica\Models\GestionAcademica;
use App\Modules\RegistroPostulantes\Mail\PagoInscripcionReceiptMail;
use App\Modules\RegistroPostulantes\Models\PagoInscripcion;
use App\Modules\RegistroPostulantes\Models\Postulacion;
use App\Modules\RegistroPostulantes\Models\Postulante;
use App\Modules\RegistroPostulantes\Services\PagoPostulanteService;
use App\Modules\RegistroPostulantes\Services\PostulanteSolicitudService;
use Database\Seeders\AccessControlSeeder;
use Database\Seeders\CupCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Stripe\Checkout\Session;
use Tests\TestCase;

class PostulanteSolicitudPagoTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_pending_requests(): void
    {
        $this->createPendingRequest();
        $user = $this->userWithPermissions(['postulantes:update']);

        $response = $this->actingAs($user)->get('/postulantes/solicitudes');

        $response->assertOk();
        $solicitudes = $response->viewData('page')['props']['solicitudes'];

        $this->assertCount(1, $solicitudes);
        $this->assertSame('PENDIENTE_VALIDACION', $solicitudes[0]['estado_proceso']);
    }

    public function test_cannot_confirm_request_without_both_documents(): void
    {
        $postulante = $this->createPendingRequest([
            'presento_titulo_bachiller' => true,
            'presento_fotocopia_carnet' => false,
        ]);
        $user = $this->userWithPermissions(['postulantes:update']);

        $this->actingAs($user)
            ->patch("/postulantes/solicitudes/{$postulante->id_postulante}/confirmar")
            ->assertSessionHasErrors('documentos');
    }

    public function test_confirm_request_generates_credentials_and_pending_payment_state(): void
    {
        $postulante = $this->createPendingRequest();
        $user = $this->userWithPermissions(['postulantes:update']);

        $this->actingAs($user)
            ->patch("/postulantes/solicitudes/{$postulante->id_postulante}/confirmar")
            ->assertRedirect();

        $postulante->refresh();
        $postulacion = $postulante->postulaciones()->firstOrFail();

        $this->assertSame('P'.$postulante->usuario->ci, $postulante->usuario->username);
        $this->assertTrue($postulante->documentacion_validada);
        $this->assertTrue($postulante->documentacion_completa);
        $this->assertSame('VALIDADO_PENDIENTE_PAGO', $postulacion->estado_proceso);
    }

    public function test_generated_postulante_password_meets_strong_requirements(): void
    {
        $postulante = $this->createPendingRequest();
        $admin = $this->userWithPermissions(['postulantes:update']);

        $credentials = app(PostulanteSolicitudService::class)->confirm($postulante, $admin->id_usuario);

        $this->assertMatchesRegularExpression(
            '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/',
            $credentials['password'],
        );
    }

    public function test_reject_request_changes_process_state(): void
    {
        $postulante = $this->createPendingRequest();
        $user = $this->userWithPermissions(['postulantes:update']);

        $this->actingAs($user)
            ->patch("/postulantes/solicitudes/{$postulante->id_postulante}/rechazar")
            ->assertRedirect();

        $postulante->refresh();

        $this->assertFalse($postulante->usuario->activo);
        $this->assertSame('RECHAZADO', $postulante->postulaciones()->firstOrFail()->estado_proceso);
    }

    public function test_validated_postulante_is_redirected_to_payment(): void
    {
        $postulante = $this->createPendingRequest();
        $admin = $this->userWithPermissions(['postulantes:update']);
        $credentials = app(PostulanteSolicitudService::class)->confirm($postulante, $admin->id_usuario);
        $postulante->refresh();

        $loginResponse = $this->post('/login', [
            'username' => $credentials['username'],
            'password' => $credentials['password'],
        ]);
        $loginResponse->assertStatus(302);
        $this->assertStringEndsWith('/postulante/pago', $loginResponse->headers->get('Location'));

    }

    public function test_validated_postulante_can_start_stripe_checkout(): void
    {
        $postulante = $this->createPendingRequest();
        $admin = $this->userWithPermissions(['postulantes:update']);
        app(PostulanteSolicitudService::class)->confirm($postulante, $admin->id_usuario);
        $postulante->refresh();

        $this->app->instance(PagoPostulanteService::class, new class extends PagoPostulanteService
        {
            public function createStripeCheckoutSession(User $user): Session
            {
                return Session::constructFrom([
                    'id' => 'cs_test_fake',
                    'url' => 'https://checkout.stripe.test/session',
                ]);
            }
        });

        $response = $this->actingAs($postulante->usuario)
            ->withHeader('X-Inertia', 'true')
            ->post('/postulante/pago/stripe');

        $response->assertStatus(409);
        $this->assertSame('https://checkout.stripe.test/session', $response->headers->get('X-Inertia-Location'));
    }

    public function test_cannot_start_stripe_checkout_without_pending_payment_state(): void
    {
        $postulante = $this->createPendingRequest();
        $admin = $this->userWithPermissions(['postulantes:update']);
        app(PostulanteSolicitudService::class)->confirm($postulante, $admin->id_usuario);
        $postulacion = $postulante->postulaciones()->firstOrFail();
        $postulacion->update(['estado_proceso' => 'HABILITADO_CUP']);

        $this->actingAs($postulante->usuario)
            ->post('/postulante/pago/stripe')
            ->assertSessionHasErrors('pago');
    }

    public function test_stripe_checkout_payload_uses_50_usd(): void
    {
        $postulante = $this->createPendingRequest();
        $postulacion = $postulante->postulaciones()->firstOrFail();
        $service = app(PagoPostulanteService::class);

        $payload = $service->checkoutSessionPayload($postulante->usuario, $postulante, $postulacion);

        $this->assertSame('payment', $payload['mode']);
        $this->assertSame('usd', $payload['line_items'][0]['price_data']['currency']);
        $this->assertSame(5000, $payload['line_items'][0]['price_data']['unit_amount']);
        $this->assertSame((string) $postulacion->id_postulacion, $payload['metadata']['id_postulacion']);
    }

    public function test_stripe_checkout_payload_uses_institutional_notification_email(): void
    {
        config()->set('services.postulantes.notification_email', 'pagos-cup@example.com');
        config()->set('services.postulante_notification_email', 'pagos-cup@example.com');

        $postulante = $this->createPendingRequest();
        $postulacion = $postulante->postulaciones()->firstOrFail();
        $service = app(PagoPostulanteService::class);

        $payload = $service->checkoutSessionPayload($postulante->usuario, $postulante, $postulacion);

        $this->assertSame('pagos-cup@example.com', $payload['customer_email']);
        $this->assertSame('pagos-cup@example.com', $payload['payment_intent_data']['receipt_email']);
    }

    public function test_stripe_checkout_payload_falls_back_to_user_email_without_notification_email(): void
    {
        config()->set('services.postulantes.notification_email', null);
        config()->set('services.postulante_notification_email', null);

        $postulante = $this->createPendingRequest();
        $postulacion = $postulante->postulaciones()->firstOrFail();
        $service = app(PagoPostulanteService::class);

        $payload = $service->checkoutSessionPayload($postulante->usuario, $postulante, $postulacion);

        $this->assertSame($postulante->usuario->correo, $payload['customer_email']);
        $this->assertSame($postulante->usuario->correo, $payload['payment_intent_data']['receipt_email']);
    }

    public function test_confirm_paid_stripe_session_enables_access_and_registers_payment(): void
    {
        Mail::fake();
        config()->set('services.postulantes.notification_email', 'pagos-cup@example.com');

        $postulante = $this->createPendingRequest();
        $admin = $this->userWithPermissions(['postulantes:update']);
        app(PostulanteSolicitudService::class)->confirm($postulante, $admin->id_usuario);
        $postulante->refresh();
        $postulacion = $postulante->postulaciones()->firstOrFail();

        $service = new class($postulacion->id_postulacion) extends PagoPostulanteService
        {
            public function __construct(private readonly int $postulacionId)
            {
            }

            protected function retrieveCheckoutSession(string $sessionId): Session
            {
                return Session::constructFrom([
                    'id' => $sessionId,
                    'payment_status' => 'paid',
                    'payment_intent' => 'pi_test_123',
                    'payment_method_types' => ['card'],
                    'metadata' => [
                        'id_postulacion' => (string) $this->postulacionId,
                    ],
                ]);
            }
        };

        $service->confirmStripePayment($postulante->usuario, 'cs_test_paid');

        $this->assertDatabaseHas('postulacion', [
            'id_postulante' => $postulante->id_postulante,
            'estado_proceso' => 'HABILITADO_CUP',
        ]);
        $this->assertDatabaseHas('pago_inscripcion', [
            'id_postulacion' => $postulante->postulaciones()->firstOrFail()->id_postulacion,
            'estado_pago' => 'APROBADO',
            'pasarela' => 'STRIPE',
            'moneda' => 'USD',
        ]);

        Mail::assertSent(PagoInscripcionReceiptMail::class, function (PagoInscripcionReceiptMail $mail): bool {
            return $mail->hasTo('pagos-cup@example.com')
                && $mail->pago->estado_pago === 'APROBADO'
                && $mail->pago->numero_transaccion === 'pi_test_123'
                && str_contains($mail->render(), 'Recibo de pago de inscripcion')
                && str_contains($mail->render(), 'pi_test_123');
        });
    }

    public function test_paid_stripe_session_does_not_duplicate_approved_payment(): void
    {
        Mail::fake();
        config()->set('services.postulantes.notification_email', 'pagos-cup@example.com');

        $postulante = $this->createPendingRequest();
        $admin = $this->userWithPermissions(['postulantes:update']);
        app(PostulanteSolicitudService::class)->confirm($postulante, $admin->id_usuario);
        $postulante->refresh();
        $postulacion = $postulante->postulaciones()->firstOrFail();
        $service = new class($postulacion->id_postulacion) extends PagoPostulanteService
        {
            public function __construct(private readonly int $postulacionId)
            {
            }

            protected function retrieveCheckoutSession(string $sessionId): Session
            {
                return Session::constructFrom([
                    'id' => $sessionId,
                    'payment_status' => 'paid',
                    'payment_intent' => 'pi_test_duplicado',
                    'metadata' => ['id_postulacion' => (string) $this->postulacionId],
                ]);
            }
        };

        $service->confirmStripePayment($postulante->usuario, 'cs_test_paid');
        $service->confirmStripePayment($postulante->usuario, 'cs_test_paid');

        $this->assertSame(1, PagoInscripcion::where('id_postulacion', $postulacion->id_postulacion)->where('estado_pago', 'APROBADO')->count());
        Mail::assertSent(PagoInscripcionReceiptMail::class, 1);
    }

    public function test_cancelled_payment_redirects_to_payment_page(): void
    {
        $postulante = $this->createPendingRequest();
        $admin = $this->userWithPermissions(['postulantes:update']);
        app(PostulanteSolicitudService::class)->confirm($postulante, $admin->id_usuario);
        $postulante->refresh();

        $response = $this->actingAs($postulante->usuario)->get('/postulante/pago/cancelado');

        $response->assertStatus(302);
        $this->assertStringEndsWith('/postulante/pago', $response->headers->get('Location'));
    }

    public function test_payment_page_does_not_show_simulated_payment_button(): void
    {
        $contents = file_get_contents(resource_path('js/modules/registro-postulantes/pages/PagoPostulantePage.jsx'));

        $this->assertStringContainsString('Pagar 50 USD con Stripe', $contents);
        $this->assertStringNotContainsString('Realizar pago simulado', $contents);
        $this->assertStringNotContainsString('/postulante/pago/simular', $contents);
    }

    private function createPendingRequest(array $overrides = []): Postulante
    {
        $this->seed(CupCatalogSeeder::class);
        $this->seed(AccessControlSeeder::class);

        $gestion = GestionAcademica::where('nombre', '1-2026')->firstOrFail();
        $carrera = Carrera::where('activo', true)->firstOrFail();
        $ci = $overrides['ci'] ?? fake()->unique()->numerify('########');
        $user = User::factory()->create([
            'ci' => $ci,
            'username' => 'SOL'.$ci,
            'estado_acceso' => 'BLOQUEADO',
            'activo' => false,
        ]);

        $postulante = Postulante::create([
            'id_usuario' => $user->id_usuario,
            'fecha_nacimiento' => '2004-02-03',
            'direccion' => 'Direccion',
            'colegio_procedencia' => 'Colegio',
            'ciudad' => 'Santa Cruz',
            'documentacion_completa' => false,
            'presento_titulo_bachiller' => $overrides['presento_titulo_bachiller'] ?? true,
            'presento_fotocopia_carnet' => $overrides['presento_fotocopia_carnet'] ?? true,
            'documentacion_validada' => false,
            'creado_por_admin' => false,
            'requiere_pago' => true,
        ]);

        Postulacion::create([
            'id_postulante' => $postulante->id_postulante,
            'id_gestion' => $gestion->id_gestion,
            'id_carrera_opcion1' => $carrera->id_carrera,
            'estado_admision' => 'PENDIENTE',
            'estado_proceso' => 'PENDIENTE_VALIDACION',
        ]);

        return $postulante->load('usuario', 'postulaciones');
    }

    private function userWithPermissions(array $permissions): User
    {
        $this->seed(AccessControlSeeder::class);

        $user = User::factory()->create();
        $role = Rol::create([
            'nombre' => 'ROL_SOLICITUDES_'.Str::upper(Str::random(8)),
            'descripcion' => 'Rol de prueba solicitudes',
            'activo' => true,
        ]);
        $permissionIds = Permiso::whereIn('nombre', $permissions)->pluck('id_permiso')->all();

        $role->permisos()->attach($permissionIds, [
            'activo' => true,
            'fecha_asignacion' => now(),
        ]);
        $user->roles()->attach($role->id_rol, [
            'activo' => true,
            'fecha_asignacion' => now(),
        ]);

        return $user;
    }
}
