<?php

namespace App\Modules\Autenticacion\Services;

use App\Modules\Autenticacion\Models\User;
use Illuminate\Support\Collection;

class UsuarioService
{
    public function list(): Collection
    {
        return User::query()
            ->with(['roles' => fn ($query) => $query->orderBy('nombre')])
            ->orderBy('apellido')
            ->orderBy('nombre')
            ->get()
            ->map(fn (User $user) => $this->serialize($user));
    }

    public function updateAccess(User $user, array $data): User
    {
        $user->update([
            'estado_acceso' => $data['estado_acceso'],
            'activo' => $data['activo'],
        ]);

        return $user;
    }

    public function syncRoles(User $user, array $roleIds): void
    {
        $user->roles()->syncWithPivotValues($roleIds, [
            'activo' => true,
            'fecha_asignacion' => now(),
        ]);
    }

    public function serialize(User $user): array
    {
        return [
            'id_usuario' => $user->id_usuario,
            'ci' => $user->ci,
            'nombre' => $user->nombre,
            'apellido' => $user->apellido,
            'name' => $user->name,
            'username' => $user->username,
            'correo' => $user->correo,
            'telefono' => $user->telefono,
            'sexo' => $user->sexo,
            'estado_acceso' => $user->estado_acceso,
            'activo' => $user->activo,
            'roles' => $user->relationLoaded('roles')
                ? $user->roles->map(fn ($rol) => [
                    'id_rol' => $rol->id_rol,
                    'nombre' => $rol->nombre,
                    'descripcion' => $rol->descripcion,
                    'activo' => $rol->activo,
                ])->values()
                : [],
        ];
    }
}
