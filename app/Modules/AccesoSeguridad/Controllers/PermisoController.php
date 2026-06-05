<?php

namespace App\Modules\AccesoSeguridad\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AccesoSeguridad\Models\Permiso;
use App\Modules\AccesoSeguridad\Requests\StorePermisoRequest;
use App\Modules\AccesoSeguridad\Requests\UpdatePermisoRequest;
use App\Modules\AccesoSeguridad\Services\PermisoService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PermisoController extends Controller
{
    public function __construct(private readonly PermisoService $permisoService)
    {
    }

    public function index(): Response
    {
        return Inertia::render('admin/permisos', [
            'permisos' => $this->permisoService->list(),
            'permisosPorModulo' => $this->permisoService->groupedByModule(),
        ]);
    }

    public function store(StorePermisoRequest $request): RedirectResponse
    {
        $this->permisoService->create($request->validated());

        return back()->with('success', 'Permiso creado correctamente.');
    }

    public function update(UpdatePermisoRequest $request, Permiso $permiso): RedirectResponse
    {
        $this->permisoService->update($permiso, $request->validated());

        return back()->with('success', 'Permiso actualizado correctamente.');
    }

    public function toggle(Permiso $permiso): RedirectResponse
    {
        $this->permisoService->toggleActive($permiso);

        return back()->with('success', 'Estado del permiso actualizado.');
    }
}
