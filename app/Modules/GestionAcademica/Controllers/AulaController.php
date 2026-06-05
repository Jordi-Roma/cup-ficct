<?php

namespace App\Modules\GestionAcademica\Controllers;

use App\Modules\GestionAcademica\Models\Aula;
use App\Modules\GestionAcademica\Requests\StoreAulaRequest;
use App\Modules\GestionAcademica\Requests\UpdateAulaRequest;
use App\Modules\GestionAcademica\Services\AulaService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AulaController extends BaseController
{
    public function __construct(private readonly AulaService $service) {}

    public function index(): Response
    {
        return Inertia::render('gestion-academica/aulas', [
            'aulas' => $this->service->list(),
        ]);
    }

    public function store(StoreAulaRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());

        return back()->with('success', 'Aula creada correctamente.');
    }

    public function update(UpdateAulaRequest $request, Aula $aula): RedirectResponse
    {
        $this->service->update($aula, $request->validated());

        return back()->with('success', 'Aula actualizada correctamente.');
    }

    public function toggle(Aula $aula): RedirectResponse
    {
        $this->service->toggleActive($aula);

        return back()->with('success', 'Estado del aula actualizado correctamente.');
    }
}
