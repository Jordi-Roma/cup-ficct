<?php

namespace App\Modules\AccesoSeguridad\Services;

use App\Modules\AccesoSeguridad\Models\Rol;
use Illuminate\Support\Collection;

class RolService
{
    public function __construct(private readonly PermisoService $permisoService)
    {
    }

    public function list(): Collection
    {
        return Rol::query()
            ->with(['permisos' => fn ($query) => $query->orderBy('modulo')->orderBy('accion')])
            ->orderBy('nombre')
            ->get()
            ->map(fn (Rol $rol) => $this->serialize($rol));
    }

    public function create(array $data): Rol
    {
        $rol = Rol::create([
            'nombre' => strtoupper($data['nombre']),
            'descripcion' => $data['descripcion'] ?? null,
            'activo' => $data['activo'] ?? true,
        ]);

        $this->syncPermissions($rol, $data['permisos'] ?? []);

        return $rol->load('permisos');
    }

    public function update(Rol $rol, array $data): Rol
    {
        $rol->update([
            'nombre' => strtoupper($data['nombre']),
            'descripcion' => $data['descripcion'] ?? null,
            'activo' => $data['activo'] ?? $rol->activo,
        ]);

        $this->syncPermissions($rol, $data['permisos'] ?? []);

        return $rol->load('permisos');
    }

    public function toggleActive(Rol $rol): Rol
    {
        $rol->update(['activo' => ! $rol->activo]);

        return $rol;
    }

    public function syncPermissions(Rol $rol, array $permissionIds): void
    {
        $rol->permisos()->syncWithPivotValues($permissionIds, [
            'activo' => true,
            'fecha_asignacion' => now(),
        ]);
    }

    public function serialize(Rol $rol): array
    {
        return [
            'id_rol' => $rol->id_rol,
            'nombre' => $rol->nombre,
            'descripcion' => $rol->descripcion,
            'activo' => $rol->activo,
            'fecha_creacion' => $rol->fecha_creacion?->toDateTimeString(),
            'permisos' => $rol->relationLoaded('permisos')
                ? $rol->permisos->map(fn ($permiso) => $this->permisoService->serialize($permiso))->values()
                : [],
        ];
    }
}
