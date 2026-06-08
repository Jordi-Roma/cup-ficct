<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $roles = [];
        $permissions = [];

        if ($user) {
            $activeRoles = $user->roles()
                ->where('rol.activo', true)
                ->with(['permisos' => fn ($query) => $query->where('permiso.activo', true)])
                ->get();

            $roles = $activeRoles->pluck('nombre')->values()->all();
            $permissions = $activeRoles
                ->flatMap(fn ($rol) => $rol->permisos->pluck('nombre'))
                ->unique()
                ->values()
                ->all();
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id_usuario,
                    'name' => $user->name,
                    'email' => $user->correo,
                    'username' => $user->username,
                    'nombre' => $user->nombre,
                    'apellido' => $user->apellido,
                    'correo' => $user->correo,
                    'avatar' => null,
                    'email_verified_at' => $user->email_verified_at,
                    'created_at' => $user->fecha_registro,
                    'updated_at' => null,
                ] : null,
                'roles' => $roles,
                'permissions' => $permissions,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
            ],
        ];
    }
}
