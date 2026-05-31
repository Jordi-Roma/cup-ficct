<?php

namespace App\Modules\GestionAcademica\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\GestionAcademica\Models\MateriaCup;
use App\Modules\GestionAcademica\Requests\StoreMateriaCupRequest;
use App\Modules\GestionAcademica\Requests\UpdateMateriaCupRequest;
use App\Modules\GestionAcademica\Services\MateriaCupService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MateriaCupController extends Controller
{
    public function __construct(private readonly MateriaCupService $materiaCupService)
    {
    }

    public function index(): Response
    {
        return Inertia::render('gestion-academica/materias', [
            'materias' => $this->materiaCupService->list(),
        ]);
    }

    public function store(StoreMateriaCupRequest $request): RedirectResponse
    {
        $this->materiaCupService->create($request->validated());

        return back()->with('success', 'Materia creada correctamente.');
    }

    public function update(UpdateMateriaCupRequest $request, MateriaCup $materia): RedirectResponse
    {
        $this->materiaCupService->update($materia, $request->validated());

        return back()->with('success', 'Materia actualizada correctamente.');
    }

    public function toggle(MateriaCup $materia): RedirectResponse
    {
        $this->materiaCupService->toggleActive($materia);

        return back()->with('success', 'Estado de la materia actualizado.');
    }
}
