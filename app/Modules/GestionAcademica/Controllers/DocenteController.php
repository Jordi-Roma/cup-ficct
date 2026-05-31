<?php

namespace App\Modules\GestionAcademica\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\GestionAcademica\Models\Docente;
use App\Modules\GestionAcademica\Requests\StoreDocenteRequest;
use App\Modules\GestionAcademica\Requests\UpdateDocenteRequest;
use App\Modules\GestionAcademica\Services\DocenteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DocenteController extends Controller
{
    public function __construct(private readonly DocenteService $docenteService)
    {
    }

    public function index(Request $request): Response
    {
        $filters = $request->only([
            'search',
            'contratado',
            'activo',
            'profesional_area',
            'maestria',
            'diplomado_educacion_superior',
        ]);

        return Inertia::render('gestion-academica/docentes', [
            'docentes' => $this->docenteService->list($filters),
            'filters' => $filters,
        ]);
    }

    public function store(StoreDocenteRequest $request): RedirectResponse
    {
        $this->docenteService->create($request->validated());

        return back()->with('success', 'Docente creado correctamente.');
    }

    public function update(UpdateDocenteRequest $request, Docente $docente): RedirectResponse
    {
        $this->docenteService->update($docente, $request->validated());

        return back()->with('success', 'Docente actualizado correctamente.');
    }

    public function toggle(Docente $docente): RedirectResponse
    {
        $this->docenteService->toggleActive($docente);

        return back()->with('success', 'Estado del docente actualizado.');
    }
}
