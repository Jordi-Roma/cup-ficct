<?php

namespace App\Modules\Examenes\Controllers;

use App\Modules\Examenes\Services\HistorialAcademicoService;
use App\Modules\RegistroPostulantes\Models\Postulante;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HistorialAcademicoController extends BaseController
{
    public function __construct(private readonly HistorialAcademicoService $service) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $filters = $request->only(['search', 'id_postulante']);
        $canViewAny = $this->service->canViewAny($user);
        $postulantes = collect();

        if ($canViewAny) {
            $postulantes = $this->service->searchPostulantes($filters);
            $historial = filled($filters['id_postulante'] ?? null)
                ? $this->service->getHistorialByPostulante(
                    Postulante::findOrFail($filters['id_postulante']),
                    $user,
                )
                : null;
        } else {
            $historial = filled($filters['id_postulante'] ?? null)
                ? $this->service->getHistorialByPostulante(
                    Postulante::findOrFail($filters['id_postulante']),
                    $user,
                )
                : $this->service->getOwnHistorial($user);
        }

        return Inertia::render('examenes/historial', [
            'historial' => $historial,
            'postulantes' => $postulantes,
            'filters' => $filters,
            'canViewAny' => $canViewAny,
        ]);
    }
}
