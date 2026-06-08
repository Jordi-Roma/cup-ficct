<?php

namespace App\Modules\GestionAcademica\Controllers;

use App\Modules\GestionAcademica\Models\GestionAcademica;
use App\Modules\GestionAcademica\Requests\StoreGestionAcademicaRequest;
use App\Modules\GestionAcademica\Requests\UpdateGestionAcademicaRequest;
use App\Modules\GestionAcademica\Services\GestionAcademicaService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class GestionAcademicaController extends BaseController
{
    public function __construct(private readonly GestionAcademicaService $service) {}

    public function index(): Response
    {
        return Inertia::render('gestion-academica/gestiones', [
            'gestiones' => $this->service->list(),
        ]);
    }

    public function store(StoreGestionAcademicaRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());

        return back()->with('success', 'Gestion academica creada correctamente.');
    }

    public function update(UpdateGestionAcademicaRequest $request, GestionAcademica $gestion): RedirectResponse
    {
        $this->service->update($gestion, $request->validated());

        return back()->with('success', 'Gestion academica actualizada correctamente.');
    }

    public function toggle(GestionAcademica $gestion): RedirectResponse
    {
        $wasActive = $gestion->activo;
        $this->service->toggle($gestion);

        return back()->with('success', $wasActive
            ? 'Gestion academica desactivada correctamente.'
            : 'Gestion academica activada correctamente.');
    }
}
