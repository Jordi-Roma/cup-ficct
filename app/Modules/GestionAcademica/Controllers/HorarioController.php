<?php

namespace App\Modules\GestionAcademica\Controllers;

use App\Modules\GestionAcademica\Models\Horario;
use App\Modules\GestionAcademica\Requests\StoreHorarioRequest;
use App\Modules\GestionAcademica\Requests\UpdateHorarioRequest;
use App\Modules\GestionAcademica\Services\HorarioService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class HorarioController extends BaseController
{
    public function __construct(private readonly HorarioService $service) {}

    public function index(): Response
    {
        return Inertia::render('gestion-academica/horarios', [
            'horarios' => $this->service->list(),
        ]);
    }

    public function store(StoreHorarioRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());

        return back()->with('success', 'Horario creado correctamente.');
    }

    public function update(UpdateHorarioRequest $request, Horario $horario): RedirectResponse
    {
        $this->service->update($horario, $request->validated());

        return back()->with('success', 'Horario actualizado correctamente.');
    }

    public function toggle(Horario $horario): RedirectResponse
    {
        $this->service->toggleActive($horario);

        return back()->with('success', 'Estado del horario actualizado correctamente.');
    }
}
