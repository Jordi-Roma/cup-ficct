<?php

namespace App\Modules\Examenes\Services;

use App\Modules\AccesoSeguridad\Models\User;
use App\Modules\GestionAcademica\Models\AsignacionAcademica;
use App\Modules\GestionAcademica\Models\Docente;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class MisAsignacionesService
{
    public function listForUser(User $user): Collection
    {
        $docente = Docente::query()
            ->where('id_usuario', $user->id_usuario)
            ->first();

        if (! $docente) {
            throw ValidationException::withMessages([
                'docente' => 'El usuario autenticado no tiene perfil docente.',
            ]);
        }

        return AsignacionAcademica::query()
            ->with(['grupo.gestion', 'materia', 'aula', 'horario'])
            ->where('id_docente', $docente->id_docente)
            ->where('activo', true)
            ->get()
            ->sortBy([
                fn (AsignacionAcademica $asignacion) => $asignacion->grupo?->gestion?->nombre ?? '',
                fn (AsignacionAcademica $asignacion) => $asignacion->grupo?->nombre ?? '',
                fn (AsignacionAcademica $asignacion) => $asignacion->materia?->nombre ?? '',
                fn (AsignacionAcademica $asignacion) => $asignacion->horario?->dia ?? '',
                fn (AsignacionAcademica $asignacion) => $asignacion->horario?->hora_inicio ?? '',
            ])
            ->map(fn (AsignacionAcademica $asignacion): array => $this->serialize($asignacion))
            ->values();
    }

    public function resumen(Collection $asignaciones): array
    {
        return [
            'total_asignaciones' => $asignaciones->count(),
            'total_grupos' => $asignaciones->pluck('grupo.id_grupo')->filter()->unique()->count(),
            'total_materias' => $asignaciones->pluck('materia.id_materia')->filter()->unique()->count(),
            'total_horas' => null,
        ];
    }

    private function serialize(AsignacionAcademica $asignacion): array
    {
        return [
            'id_asignacion' => $asignacion->id_asignacion,
            'activo' => (bool) $asignacion->activo,
            'grupo' => [
                'id_grupo' => $asignacion->grupo?->id_grupo,
                'nombre' => $asignacion->grupo?->nombre,
                'capacidad_maxima' => $asignacion->grupo?->capacidad_maxima,
            ],
            'gestion' => [
                'id_gestion' => $asignacion->grupo?->gestion?->id_gestion,
                'nombre' => $asignacion->grupo?->gestion?->nombre,
                'activo' => (bool) $asignacion->grupo?->gestion?->activo,
            ],
            'materia' => [
                'id_materia' => $asignacion->materia?->id_materia,
                'nombre' => $asignacion->materia?->nombre,
            ],
            'aula' => [
                'id_aula' => $asignacion->aula?->id_aula,
                'nombre' => $asignacion->aula?->nombre,
                'capacidad' => $asignacion->aula?->capacidad,
            ],
            'horario' => [
                'id_horario' => $asignacion->horario?->id_horario,
                'dia' => $asignacion->horario?->dia,
                'hora_inicio' => $asignacion->horario?->hora_inicio,
                'hora_fin' => $asignacion->horario?->hora_fin,
            ],
        ];
    }
}
