<?php

namespace App\Modules\GestionAcademica\Services;

use App\Modules\GestionAcademica\Models\GestionAcademica;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GestionAcademicaService
{
    public function list(): Collection
    {
        return GestionAcademica::query()
            ->withCount(['grupos', 'postulaciones'])
            ->orderByDesc('fecha_inicio')
            ->orderByDesc('id_gestion')
            ->get()
            ->map(fn (GestionAcademica $gestion) => $this->serialize($gestion));
    }

    public function create(array $data): GestionAcademica
    {
        return DB::transaction(function () use ($data): GestionAcademica {
            $active = (bool) ($data['activo'] ?? false);

            if ($active) {
                GestionAcademica::query()->update(['activo' => false]);
            }

            $gestion = GestionAcademica::create([
                'nombre' => $data['nombre'],
                'fecha_inicio' => $data['fecha_inicio'],
                'fecha_fin' => $data['fecha_fin'],
                'activo' => $active,
            ]);

            if (! GestionAcademica::query()->where('activo', true)->exists()) {
                $gestion->update(['activo' => true]);
            }

            return $gestion;
        });
    }

    public function update(GestionAcademica $gestion, array $data): GestionAcademica
    {
        return DB::transaction(function () use ($gestion, $data): GestionAcademica {
            $active = (bool) $data['activo'];

            if (! $active && $gestion->activo && ! $this->hasAnotherActiveGestion($gestion)) {
                throw ValidationException::withMessages([
                    'gestion' => 'Debe existir al menos una gestion academica activa.',
                ]);
            }

            if ($active) {
                GestionAcademica::query()
                    ->whereKeyNot($gestion->id_gestion)
                    ->update(['activo' => false]);
            }

            $gestion->update([
                'nombre' => $data['nombre'],
                'fecha_inicio' => $data['fecha_inicio'],
                'fecha_fin' => $data['fecha_fin'],
                'activo' => $active,
            ]);

            return $gestion;
        });
    }

    public function toggle(GestionAcademica $gestion): GestionAcademica
    {
        return DB::transaction(function () use ($gestion): GestionAcademica {
            if (! $gestion->activo) {
                GestionAcademica::query()
                    ->whereKeyNot($gestion->id_gestion)
                    ->update(['activo' => false]);

                $gestion->update(['activo' => true]);

                return $gestion;
            }

            if (! $this->hasAnotherActiveGestion($gestion)) {
                throw ValidationException::withMessages([
                    'gestion' => 'No se puede desactivar la unica gestion activa.',
                ]);
            }

            $gestion->update(['activo' => false]);

            return $gestion;
        });
    }

    public function serialize(GestionAcademica $gestion): array
    {
        return [
            'id_gestion' => $gestion->id_gestion,
            'nombre' => $gestion->nombre,
            'fecha_inicio' => $gestion->fecha_inicio?->toDateString(),
            'fecha_fin' => $gestion->fecha_fin?->toDateString(),
            'activo' => (bool) $gestion->activo,
            'grupos_count' => (int) ($gestion->grupos_count ?? 0),
            'postulaciones_count' => (int) ($gestion->postulaciones_count ?? 0),
        ];
    }

    private function hasAnotherActiveGestion(GestionAcademica $gestion): bool
    {
        return GestionAcademica::query()
            ->whereKeyNot($gestion->id_gestion)
            ->where('activo', true)
            ->exists();
    }
}
