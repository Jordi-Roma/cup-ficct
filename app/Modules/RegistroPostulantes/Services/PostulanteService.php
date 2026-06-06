<?php

namespace App\Modules\RegistroPostulantes\Services;

use App\Modules\GestionAcademica\Models\GestionAcademica;
use App\Modules\GestionAcademica\Models\GrupoAcademico;
use App\Modules\RegistroPostulantes\Models\Postulacion;
use App\Modules\RegistroPostulantes\Models\Postulante;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PostulanteService
{
    public function list(array $filters): Collection
    {
        return Postulante::query()
            ->with([
                'usuario',
                'postulaciones.carreraOpcion1',
                'postulaciones.carreraOpcion2',
                'postulaciones.grupo',
                'postulaciones.gestion',
            ])
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->whereHas('usuario', function (Builder $userQuery) use ($search): void {
                    $userQuery
                        ->where('ci', 'ilike', "%{$search}%")
                        ->orWhere('nombre', 'ilike', "%{$search}%")
                        ->orWhere('apellido', 'ilike', "%{$search}%")
                        ->orWhere('correo', 'ilike', "%{$search}%");
                });
            })
            ->when($filters['ciudad'] ?? null, fn (Builder $query, string $city) => $query->where('ciudad', 'ilike', "%{$city}%"))
            ->when($filters['colegio_procedencia'] ?? null, fn (Builder $query, string $school) => $query->where('colegio_procedencia', 'ilike', "%{$school}%"))
            ->when(isset($filters['documentacion_completa']) && $filters['documentacion_completa'] !== '', fn (Builder $query) => $query->where('documentacion_completa', filter_var($filters['documentacion_completa'], FILTER_VALIDATE_BOOLEAN)))
            ->when($filters['estado_admision'] ?? null, function (Builder $query, string $status): void {
                $query->whereHas('postulaciones', fn (Builder $postulacionQuery) => $postulacionQuery->where('estado_admision', $status));
            })
            ->when($filters['id_carrera'] ?? null, function (Builder $query, int|string $careerId): void {
                $query->whereHas('postulaciones', function (Builder $postulacionQuery) use ($careerId): void {
                    $postulacionQuery
                        ->where('id_carrera_opcion1', $careerId)
                        ->orWhere('id_carrera_opcion2', $careerId);
                });
            })
            ->whereHas('usuario')
            ->get()
            ->sortBy(fn (Postulante $postulante) => $postulante->usuario?->apellido.' '.$postulante->usuario?->nombre)
            ->map(fn (Postulante $postulante) => $this->serialize($postulante))
            ->values();
    }

    public function find(Postulante $postulante): array
    {
        return $this->serialize($postulante->load([
            'usuario',
            'postulaciones.carreraOpcion1',
            'postulaciones.carreraOpcion2',
            'postulaciones.grupo',
            'postulaciones.gestion',
        ]));
    }

    public function update(Postulante $postulante, array $data): Postulante
    {
        return DB::transaction(function () use ($postulante, $data): Postulante {
            $postulante->usuario()->update([
                'correo' => $data['correo'],
                'telefono' => $data['telefono'] ?? null,
            ]);

            $postulante->update([
                'fecha_nacimiento' => $data['fecha_nacimiento'],
                'direccion' => $data['direccion'] ?? null,
                'colegio_procedencia' => $data['colegio_procedencia'] ?? null,
                'ciudad' => $data['ciudad'] ?? null,
                'documentacion_completa' => $data['documentacion_completa'],
                'presento_titulo_bachiller' => $data['documentacion_completa'] ? true : $postulante->presento_titulo_bachiller,
                'presento_fotocopia_carnet' => $data['documentacion_completa'] ? true : $postulante->presento_fotocopia_carnet,
                'documentacion_validada' => $data['documentacion_completa'],
                'fecha_validacion_documentos' => $data['documentacion_completa']
                    ? ($postulante->fecha_validacion_documentos ?? now())
                    : null,
                'validado_por' => $data['documentacion_completa']
                    ? ($postulante->validado_por ?? auth()->id())
                    : null,
            ]);

            $postulacion = $this->currentPostulacion($postulante);

            if ($postulacion) {
                $newTurno = $data['turno_preferido'];

                if ($postulacion->id_grupo && $postulacion->turno_preferido !== $newTurno) {
                    $this->ensureAvailableGroupForTurno($postulacion->id_gestion, $newTurno);
                    $postulacion->id_grupo = null;
                }

                $postulacion->update([
                    'id_carrera_opcion1' => $data['id_carrera_opcion1'],
                    'id_carrera_opcion2' => $data['id_carrera_opcion2'] ?? null,
                    'turno_preferido' => $newTurno,
                    'id_grupo' => $postulacion->id_grupo,
                ]);
            }

            return $postulante;
        });
    }

    public function toggleActive(Postulante $postulante): void
    {
        $user = $postulante->usuario;

        if ($user) {
            $user->update(['activo' => ! $user->activo]);
        }
    }

    public function serialize(Postulante $postulante): array
    {
        $postulacion = $this->currentPostulacion($postulante);
        $user = $postulante->usuario;

        return [
            'id_postulante' => $postulante->id_postulante,
            'id_usuario' => $postulante->id_usuario,
            'ci' => $user?->ci,
            'nombre' => $user?->nombre,
            'apellido' => $user?->apellido,
            'nombre_completo' => $user?->name,
            'username' => $user?->username,
            'correo' => $user?->correo,
            'telefono' => $user?->telefono,
            'sexo' => $user?->sexo,
            'activo' => $user?->activo ?? false,
            'fecha_nacimiento' => $postulante->fecha_nacimiento?->toDateString(),
            'direccion' => $postulante->direccion,
            'colegio_procedencia' => $postulante->colegio_procedencia,
            'ciudad' => $postulante->ciudad,
            'documentacion_completa' => $postulante->documentacion_completa,
            'presento_titulo_bachiller' => (bool) $postulante->presento_titulo_bachiller,
            'presento_fotocopia_carnet' => (bool) $postulante->presento_fotocopia_carnet,
            'documentacion_validada' => (bool) $postulante->documentacion_validada,
            'fecha_validacion_documentos' => $postulante->fecha_validacion_documentos?->toDateTimeString(),
            'creado_por_admin' => (bool) $postulante->creado_por_admin,
            'requiere_pago' => (bool) $postulante->requiere_pago,
            'postulacion' => $postulacion ? [
                'id_postulacion' => $postulacion->id_postulacion,
                'estado_admision' => $postulacion->estado_admision,
                'estado_proceso' => $postulacion->estado_proceso,
                'turno_preferido' => $postulacion->turno_preferido,
                'turno_preferido_label' => $this->turnoLabel($postulacion->turno_preferido),
                'carrera_opcion1' => $postulacion->carreraOpcion1 ? [
                    'id_carrera' => $postulacion->carreraOpcion1->id_carrera,
                    'nombre' => $postulacion->carreraOpcion1->nombre,
                ] : null,
                'carrera_opcion2' => $postulacion->carreraOpcion2 ? [
                    'id_carrera' => $postulacion->carreraOpcion2->id_carrera,
                    'nombre' => $postulacion->carreraOpcion2->nombre,
                ] : null,
                'grupo' => $postulacion->grupo ? [
                    'id_grupo' => $postulacion->grupo->id_grupo,
                    'nombre' => $postulacion->grupo->nombre,
                    'turno' => $postulacion->grupo->turno,
                ] : null,
            ] : null,
        ];
    }

    private function ensureAvailableGroupForTurno(int $gestionId, string $turno): void
    {
        $hasCapacity = GrupoAcademico::query()
            ->where('id_gestion', $gestionId)
            ->where('turno', $turno)
            ->where('activo', true)
            ->whereRaw('capacidad_maxima > (SELECT COUNT(*) FROM postulacion WHERE postulacion.id_grupo = grupo_academico.id_grupo)')
            ->exists();

        if (! $hasCapacity) {
            throw ValidationException::withMessages([
                'turno_preferido' => 'No hay cupos disponibles en grupos activos del turno seleccionado.',
            ]);
        }
    }

    private function currentPostulacion(Postulante $postulante): ?Postulacion
    {
        $postulaciones = $postulante->relationLoaded('postulaciones')
            ? $postulante->postulaciones
            : $postulante->postulaciones()->with(['carreraOpcion1', 'carreraOpcion2', 'grupo', 'gestion'])->get();
        $activeGestionId = GestionAcademica::query()
            ->where('activo', true)
            ->orderByDesc('id_gestion')
            ->value('id_gestion');

        return $postulaciones->firstWhere('id_gestion', $activeGestionId)
            ?? $postulaciones->sortByDesc('fecha_postulacion')->first();
    }

    private function turnoLabel(?string $turno): string
    {
        return [
            'MANANA' => 'Mañana',
            'TARDE' => 'Tarde',
            'NOCHE' => 'Noche',
        ][$turno] ?? 'Sin turno';
    }
}
