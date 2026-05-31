<?php

namespace App\Modules\Autenticacion\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Autenticacion\Models\Rol;
use App\Modules\Autenticacion\Models\User;
use App\Modules\Autenticacion\Requests\UpdateUsuarioAccessRequest;
use App\Modules\Autenticacion\Requests\UpdateUsuarioRolesRequest;
use App\Modules\Autenticacion\Services\RolService;
use App\Modules\Autenticacion\Services\UsuarioService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class UsuarioController extends Controller
{
    public function __construct(
        private readonly UsuarioService $usuarioService,
        private readonly RolService $rolService,
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('admin/usuarios', [
            'usuarios' => $this->usuarioService->list(),
            'roles' => Rol::query()->where('activo', true)->orderBy('nombre')->get()->map(
                fn (Rol $rol) => $this->rolService->serialize($rol->load('permisos')),
            ),
        ]);
    }

    public function update(UpdateUsuarioAccessRequest $request, User $usuario): RedirectResponse
    {
        $this->usuarioService->updateAccess($usuario, $request->validated());

        return back()->with('success', 'Acceso de usuario actualizado.');
    }

    public function syncRoles(UpdateUsuarioRolesRequest $request, User $usuario): RedirectResponse
    {
        $this->usuarioService->syncRoles($usuario, $request->validated('roles', []));

        return back()->with('success', 'Roles de usuario actualizados.');
    }
}
