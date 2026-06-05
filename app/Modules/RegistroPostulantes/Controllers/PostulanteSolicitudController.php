<?php

namespace App\Modules\RegistroPostulantes\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RegistroPostulantes\Models\Postulante;
use App\Modules\RegistroPostulantes\Services\PostulanteSolicitudService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PostulanteSolicitudController extends Controller
{
    public function __construct(private readonly PostulanteSolicitudService $service)
    {
    }

    public function index(): Response
    {
        return Inertia::render('registro-postulantes/solicitudes', [
            'solicitudes' => $this->service->list(),
        ]);
    }

    public function confirmar(Postulante $postulante): RedirectResponse
    {
        $this->service->confirm($postulante, auth()->id());

        return back()->with('success', 'Solicitud confirmada. Las credenciales fueron generadas.');
    }

    public function rechazar(Postulante $postulante): RedirectResponse
    {
        $this->service->reject($postulante);

        return back()->with('success', 'Solicitud rechazada correctamente.');
    }
}
