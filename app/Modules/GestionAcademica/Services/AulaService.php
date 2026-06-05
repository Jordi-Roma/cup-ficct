<?php

namespace App\Modules\GestionAcademica\Services;

use App\Modules\GestionAcademica\Models\Aula;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class AulaService
{
    public function list(): Collection
    {
        return Aula::query()
            ->withCount([
                'asignaciones as asignaciones_activas' => fn ($query) => $query->where('activo', true),
            ])
            ->orderBy('nombre')
            ->get()
            ->map(fn (Aula $aula) => $this->serialize($aula));
    }

    public function create(array $data): Aula
    {
        return Aula::create([
            'nombre' => $data['nombre'],
            'capacidad' => $data['capacidad'],
            'activo' => true,
        ]);
    }

    public function update(Aula $aula, array $data): Aula
    {
        $aula->update([
            'nombre' => $data['nombre'],
            'capacidad' => $data['capacidad'],
            'activo' => $data['activo'] ?? $aula->activo,
        ]);

        return $aula;
    }

    public function toggleActive(Aula $aula): Aula
    {
        if ($aula->activo && $aula->asignaciones()->where('activo', true)->exists()) {
            throw ValidationException::withMessages([
                'aula' => 'No se puede desactivar un aula con asignaciones academicas activas.',
            ]);
        }

        $aula->update(['activo' => ! $aula->activo]);

        return $aula;
    }

    public function serialize(Aula $aula): array
    {
        return [
            'id_aula' => $aula->id_aula,
            'nombre' => $aula->nombre,
            'capacidad' => $aula->capacidad,
            'activo' => (bool) $aula->activo,
            'asignaciones_activas' => (int) ($aula->asignaciones_activas ?? 0),
        ];
    }
}
