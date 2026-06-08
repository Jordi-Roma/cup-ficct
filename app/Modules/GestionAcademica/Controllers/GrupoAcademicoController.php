<?php

namespace App\Modules\GestionAcademica\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\GestionAcademica\Models\GrupoAcademico;
use App\Modules\GestionAcademica\Requests\StoreGrupoAcademicoRequest;
use App\Modules\GestionAcademica\Requests\UpdateGrupoAcademicoRequest;
use App\Modules\GestionAcademica\Services\GrupoAcademicoService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class GrupoAcademicoController extends Controller
{
    public function __construct(private readonly GrupoAcademicoService $grupoAcademicoService)
    {
    }

    public function index(): Response
    {
        return Inertia::render('gestion-academica/grupos', [
            'grupos' => $this->grupoAcademicoService->list(),
            'resumen' => $this->grupoAcademicoService->calculateRequiredGroups(),
        ]);
    }

    public function store(StoreGrupoAcademicoRequest $request): RedirectResponse
    {
        $this->grupoAcademicoService->create($request->validated());

        return back()->with('success', 'Grupo creado correctamente.');
    }

    public function update(UpdateGrupoAcademicoRequest $request, GrupoAcademico $grupo): RedirectResponse
    {
        $this->grupoAcademicoService->update($grupo, $request->validated());

        return back()->with('success', 'Grupo actualizado correctamente.');
    }

    public function toggle(GrupoAcademico $grupo): RedirectResponse
    {
        $this->grupoAcademicoService->toggleActive($grupo);

        return back()->with('success', 'Estado del grupo actualizado.');
    }

    public function generate(): RedirectResponse
    {
        $created = $this->grupoAcademicoService->createMissingGroups();

        return back()->with('success', "Grupos generados correctamente. Creados: {$created}.");
    }

}
