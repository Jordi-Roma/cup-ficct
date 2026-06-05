<?php

namespace App\Modules\RegistroPostulantes\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RegistroPostulantes\Services\PagoPostulanteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class PagoPostulanteController extends Controller
{
    public function __construct(private readonly PagoPostulanteService $service)
    {
    }

    public function index(): Response|RedirectResponse
    {
        $state = $this->service->currentState(auth()->user());

        if ($state['postulacion']['estado_proceso'] === 'HABILITADO_CUP') {
            return redirect()->route('dashboard');
        }

        return Inertia::render('registro-postulantes/pago', $state);
    }

    public function stripe(): SymfonyResponse
    {
        $session = $this->service->createStripeCheckoutSession(auth()->user());

        return Inertia::location($session->url);
    }

    public function exito(Request $request): RedirectResponse
    {
        $this->service->confirmStripePayment(auth()->user(), (string) $request->query('session_id', ''));

        return redirect()->route('dashboard')->with('success', 'Pago aprobado con Stripe. Ya puedes acceder al sistema.');
    }

    public function cancelado(): RedirectResponse
    {
        return redirect()
            ->route('postulante.pago.index')
            ->with('warning', 'El pago fue cancelado. Puedes intentarlo nuevamente.');
    }
}
