<?php

namespace App\Modules\GestionAcademica\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\GestionAcademica\Models\AsignacionAcademica;
use App\Modules\GestionAcademica\Requests\StoreAsignacionAcademicaRequest;
use App\Modules\GestionAcademica\Requests\UpdateAsignacionAcademicaRequest;
use App\Modules\GestionAcademica\Services\AsignacionAcademicaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AsignacionAcademicaController extends Controller
{
    public function __construct(private readonly AsignacionAcademicaService $asignacionService)
    {
    }

    public function index(Request $request): Response
    {
        $filters = $request->only([]);

        return Inertia::render('gestion-academica/asignaciones', [
            'asignaciones' => $this->asignacionService->list($filters),
            'options' => $this->asignacionService->getFormOptions(),
            'filters' => $filters,
        ]);
    }

    public function store(StoreAsignacionAcademicaRequest $request): RedirectResponse
    {
        $this->asignacionService->create($request->validated());

        return back()->with('success', 'Asignacion academica creada correctamente.');
    }

    public function update(UpdateAsignacionAcademicaRequest $request, AsignacionAcademica $asignacion): RedirectResponse
    {
        $this->asignacionService->update($asignacion, $request->validated());

        return back()->with('success', 'Asignacion academica actualizada correctamente.');
    }

    public function toggle(AsignacionAcademica $asignacion): RedirectResponse
    {
        $this->asignacionService->toggleActive($asignacion);

        return back()->with('success', 'Estado de la asignacion actualizado.');
    }
}
