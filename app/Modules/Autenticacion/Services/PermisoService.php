<?php

namespace App\Modules\Autenticacion\Services;

use App\Modules\Autenticacion\Models\Permiso;
use Illuminate\Support\Collection;

class PermisoService
{
    public function list(): Collection
    {
        return Permiso::query()
            ->orderBy('modulo')
            ->orderBy('accion')
            ->get()
            ->map(fn (Permiso $permiso) => $this->serialize($permiso));
    }

    public function groupedByModule(): array
    {
        return $this->list()
            ->groupBy('modulo')
            ->map(fn (Collection $permisos) => $permisos->values())
            ->toArray();
    }

    public function create(array $data): Permiso
    {
        return Permiso::create([
            'nombre' => $data['nombre'],
            'modulo' => $data['modulo'],
            'accion' => strtoupper($data['accion']),
            'descripcion' => $data['descripcion'] ?? null,
            'activo' => $data['activo'] ?? true,
        ]);
    }

    public function update(Permiso $permiso, array $data): Permiso
    {
        $permiso->update([
            'nombre' => $data['nombre'],
            'modulo' => $data['modulo'],
            'accion' => strtoupper($data['accion']),
            'descripcion' => $data['descripcion'] ?? null,
            'activo' => $data['activo'] ?? $permiso->activo,
        ]);

        return $permiso;
    }

    public function toggleActive(Permiso $permiso): Permiso
    {
        $permiso->update(['activo' => ! $permiso->activo]);

        return $permiso;
    }

    public function serialize(Permiso $permiso): array
    {
        return [
            'id_permiso' => $permiso->id_permiso,
            'nombre' => $permiso->nombre,
            'modulo' => $permiso->modulo,
            'accion' => $permiso->accion,
            'descripcion' => $permiso->descripcion,
            'activo' => $permiso->activo,
        ];
    }
}
