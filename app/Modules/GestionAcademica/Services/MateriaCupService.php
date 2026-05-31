<?php

namespace App\Modules\GestionAcademica\Services;

use App\Modules\GestionAcademica\Models\MateriaCup;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MateriaCupService
{
    public function list(): Collection
    {
        return MateriaCup::query()
            ->orderBy('nombre')
            ->get()
            ->map(fn (MateriaCup $materia) => $this->serialize($materia));
    }

    public function create(array $data): MateriaCup
    {
        return MateriaCup::create([
            'nombre' => $data['nombre'],
            'activo' => $data['activo'] ?? true,
        ]);
    }

    public function update(MateriaCup $materia, array $data): MateriaCup
    {
        $materia->update([
            'nombre' => $data['nombre'],
            'activo' => $data['activo'] ?? $materia->activo,
        ]);

        return $materia;
    }

    public function toggleActive(MateriaCup $materia): MateriaCup
    {
        if ($materia->activo && $this->hasNotasInActiveGestion($materia)) {
            throw ValidationException::withMessages([
                'materia' => 'No se puede desactivar una materia con notas registradas en la gestión activa.',
            ]);
        }

        $materia->update(['activo' => ! $materia->activo]);

        return $materia;
    }

    public function serialize(MateriaCup $materia): array
    {
        return [
            'id_materia' => $materia->id_materia,
            'nombre' => $materia->nombre,
            'activo' => $materia->activo,
        ];
    }

    private function hasNotasInActiveGestion(MateriaCup $materia): bool
    {
        return DB::table('nota')
            ->join('postulacion', 'nota.id_postulacion', '=', 'postulacion.id_postulacion')
            ->join('gestion_academica', 'postulacion.id_gestion', '=', 'gestion_academica.id_gestion')
            ->where('nota.id_materia', $materia->id_materia)
            ->where('gestion_academica.activo', true)
            ->exists();
    }
}
