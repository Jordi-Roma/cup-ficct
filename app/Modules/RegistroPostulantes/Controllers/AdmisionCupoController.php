<?php

namespace App\Modules\RegistroPostulantes\Controllers;

use App\Modules\RegistroPostulantes\Requests\UpsertCupoCarreraRequest;
use App\Modules\RegistroPostulantes\Services\AdmisionCupoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdmisionCupoController extends BaseController
{
    public function __construct(private readonly AdmisionCupoService $service) {}

    public function index(Request $request): Response
    {
        $gestion = $this->service->resolveGestion(
            filled($request->query('id_gestion')) ? (int) $request->query('id_gestion') : null,
        );

        return Inertia::render('registro-postulantes/admision-cupos', [
            'gestiones' => $this->service->gestiones(),
            'selectedGestion' => [
                'id_gestion' => $gestion->id_gestion,
                'nombre' => $gestion->nombre,
                'activo' => (bool) $gestion->activo,
            ],
            'carreras' => $this->service->carreras(),
            'cupos' => $this->service->cupos($gestion->id_gestion),
            'postulantes' => $this->service->postulantes($gestion->id_gestion),
            'resumen' => $this->service->resumen($gestion->id_gestion),
            'filters' => $request->only(['id_gestion']),
        ]);
    }

    public function upsertCupo(UpsertCupoCarreraRequest $request): RedirectResponse
    {
        $this->service->upsertCupo($request->validated());

        return back()->with('success', 'Cupo actualizado correctamente.');
    }

    public function process(Request $request): RedirectResponse
    {
        $gestion = $this->service->resolveGestion(
            filled($request->input('id_gestion')) ? (int) $request->input('id_gestion') : null,
        );
        $result = $this->service->processAdmission($gestion->id_gestion);

        return back()->with(
            'success',
            "Admisión procesada. Procesados: {$result['procesados']}, admitidos: {$result['admitidos']}, no admitidos: {$result['no_admitidos']}, pendientes: {$result['pendientes']}.",
        );
    }
}
