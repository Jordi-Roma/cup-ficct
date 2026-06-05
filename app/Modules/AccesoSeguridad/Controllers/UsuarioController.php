<?php

namespace App\Modules\AccesoSeguridad\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AccesoSeguridad\Models\Rol;
use App\Modules\AccesoSeguridad\Models\User;
use App\Modules\AccesoSeguridad\Requests\StoreUsuarioRequest;
use App\Modules\AccesoSeguridad\Requests\UpdateUsuarioAccessRequest;
use App\Modules\AccesoSeguridad\Requests\UpdateUsuarioRolesRequest;
use App\Modules\AccesoSeguridad\Services\RolService;
use App\Modules\AccesoSeguridad\Services\UsuarioService;
use App\Modules\GestionAcademica\Models\Carrera;
use App\Modules\GestionAcademica\Models\GestionAcademica;
use App\Modules\GestionAcademica\Models\MateriaCup;
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
            'materias' => MateriaCup::query()->where('activo', true)->orderBy('nombre')->get(['id_materia', 'nombre']),
            'gestiones' => GestionAcademica::query()->orderByDesc('fecha_inicio')->get(['id_gestion', 'nombre', 'activo']),
            'carreras' => Carrera::query()->where('activo', true)->orderBy('nombre')->get(['id_carrera', 'nombre']),
        ]);
    }

    public function store(StoreUsuarioRequest $request): RedirectResponse
    {
        $this->usuarioService->createByType($request->validated());

        return back()->with('success', 'Usuario creado correctamente.');
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
