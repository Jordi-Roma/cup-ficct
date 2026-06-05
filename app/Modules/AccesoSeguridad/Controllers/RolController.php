<?php

namespace App\Modules\AccesoSeguridad\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AccesoSeguridad\Models\Rol;
use App\Modules\AccesoSeguridad\Requests\StoreRolRequest;
use App\Modules\AccesoSeguridad\Requests\UpdateRolRequest;
use App\Modules\AccesoSeguridad\Services\PermisoService;
use App\Modules\AccesoSeguridad\Services\RolService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RolController extends Controller
{
    public function __construct(
        private readonly RolService $rolService,
        private readonly PermisoService $permisoService,
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('admin/roles', [
            'roles' => $this->rolService->list(),
            'permisos' => $this->permisoService->list(),
            'permisosPorModulo' => $this->permisoService->groupedByModule(),
        ]);
    }

    public function store(StoreRolRequest $request): RedirectResponse
    {
        $this->rolService->create($request->validated());

        return back()->with('success', 'Rol creado correctamente.');
    }

    public function update(UpdateRolRequest $request, Rol $rol): RedirectResponse
    {
        $this->rolService->update($rol, $request->validated());

        return back()->with('success', 'Rol actualizado correctamente.');
    }

    public function toggle(Rol $rol): RedirectResponse
    {
        $this->rolService->toggleActive($rol);

        return back()->with('success', 'Estado del rol actualizado.');
    }
}
