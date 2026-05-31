<?php

namespace App\Modules\GestionAcademica\Services;

use App\Modules\GestionAcademica\Models\GestionAcademica;
use App\Modules\GestionAcademica\Models\GrupoAcademico;
use App\Modules\RegistroPostulantes\Models\Postulacion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GrupoAcademicoService
{
    private const GROUP_CAPACITY = 70;

    public function activeGestion(): GestionAcademica
    {
        $gestion = GestionAcademica::query()
            ->where('activo', true)
            ->orderByDesc('id_gestion')
            ->first();

        if (! $gestion) {
            throw ValidationException::withMessages([
                'gestion' => 'No existe una gestión académica activa.',
            ]);
        }

        return $gestion;
    }

    public function list(): Collection
    {
        $gestion = $this->activeGestion();

        return GrupoAcademico::query()
            ->where('id_gestion', $gestion->id_gestion)
            ->with([
                'postulaciones.postulante.usuario',
                'postulaciones.carreraOpcion1',
                'postulaciones.carreraOpcion2',
            ])
            ->withCount('postulaciones')
            ->orderBy('nombre')
            ->get()
            ->map(fn (GrupoAcademico $grupo) => $this->serialize($grupo));
    }

    public function countEligiblePostulantes(): int
    {
        $gestion = $this->activeGestion();

        return Postulacion::query()
            ->join('postulante', 'postulacion.id_postulante', '=', 'postulante.id_postulante')
            ->where('postulacion.id_gestion', $gestion->id_gestion)
            ->where('postulante.documentacion_completa', true)
            ->count();
    }

    public function calculateRequiredGroups(): array
    {
        $gestion = $this->activeGestion();
        $totalInscritos = $this->countEligiblePostulantes();
        $gruposNecesarios = $this->calculateGroups($totalInscritos);
        $gruposActivos = GrupoAcademico::query()
            ->where('id_gestion', $gestion->id_gestion)
            ->where('activo', true)
            ->count();

        return [
            'total_inscritos' => $totalInscritos,
            'grupos_necesarios' => $gruposNecesarios,
            'grupos_activos' => $gruposActivos,
            'grupos_faltantes' => max($gruposNecesarios - $gruposActivos, 0),
            'gestion_activa' => [
                'id_gestion' => $gestion->id_gestion,
                'nombre' => $gestion->nombre,
            ],
        ];
    }

    public function create(array $data): GrupoAcademico
    {
        $gestion = $this->activeGestion();
        $this->ensureUniqueName($gestion->id_gestion, $data['nombre']);

        return GrupoAcademico::create([
            'id_gestion' => $gestion->id_gestion,
            'nombre' => $data['nombre'],
            'capacidad_maxima' => $data['capacidad_maxima'] ?? self::GROUP_CAPACITY,
            'activo' => true,
        ]);
    }

    public function update(GrupoAcademico $grupo, array $data): GrupoAcademico
    {
        $assigned = $this->assignedCount($grupo);

        if ((int) $data['capacidad_maxima'] < $assigned) {
            throw ValidationException::withMessages([
                'capacidad_maxima' => 'La capacidad no puede ser menor a los postulantes ya asignados.',
            ]);
        }

        $this->ensureUniqueName($grupo->id_gestion, $data['nombre'], $grupo->id_grupo);

        $grupo->update([
            'nombre' => $data['nombre'],
            'capacidad_maxima' => $data['capacidad_maxima'],
        ]);

        return $grupo;
    }

    public function createMissingGroups(): int
    {
        $gestion = $this->activeGestion();
        $summary = $this->calculateRequiredGroups();
        $missing = $summary['grupos_faltantes'];

        if ($missing === 0) {
            return 0;
        }

        $existingNames = GrupoAcademico::query()
            ->where('id_gestion', $gestion->id_gestion)
            ->pluck('nombre')
            ->all();

        $created = 0;
        $index = 0;

        while ($created < $missing) {
            $name = 'Grupo '.$this->groupLetter($index);
            $index++;

            if (in_array($name, $existingNames, true)) {
                continue;
            }

            GrupoAcademico::create([
                'id_gestion' => $gestion->id_gestion,
                'nombre' => $name,
                'capacidad_maxima' => self::GROUP_CAPACITY,
                'activo' => true,
            ]);

            $existingNames[] = $name;
            $created++;
        }

        return $created;
    }

    public function assignPostulantes(): int
    {
        $gestion = $this->activeGestion();
        $groups = GrupoAcademico::query()
            ->where('id_gestion', $gestion->id_gestion)
            ->where('activo', true)
            ->withCount('postulaciones')
            ->orderBy('nombre')
            ->get()
            ->map(fn (GrupoAcademico $grupo) => [
                'id_grupo' => $grupo->id_grupo,
                'capacidad_maxima' => $grupo->capacidad_maxima,
                'asignados' => $grupo->postulaciones_count,
            ])
            ->values()
            ->all();

        $postulaciones = Postulacion::query()
            ->join('postulante', 'postulacion.id_postulante', '=', 'postulante.id_postulante')
            ->where('postulacion.id_gestion', $gestion->id_gestion)
            ->where('postulante.documentacion_completa', true)
            ->whereNull('postulacion.id_grupo')
            ->orderBy('postulacion.fecha_postulacion')
            ->select('postulacion.*')
            ->get();

        $availableCapacity = collect($groups)->sum(fn (array $group) => max($group['capacidad_maxima'] - $group['asignados'], 0));

        if ($postulaciones->count() > $availableCapacity) {
            throw ValidationException::withMessages([
                'grupos' => 'No hay cupos suficientes en los grupos activos para asignar a todos los postulantes.',
            ]);
        }

        $assigned = 0;

        DB::transaction(function () use (&$groups, $postulaciones, &$assigned): void {
            foreach ($postulaciones as $postulacion) {
                $groupIndex = $this->nextAvailableGroupIndex($groups);

                if ($groupIndex === null) {
                    throw ValidationException::withMessages([
                        'grupos' => 'No hay grupos activos con cupos disponibles.',
                    ]);
                }

                $postulacion->update(['id_grupo' => $groups[$groupIndex]['id_grupo']]);
                $groups[$groupIndex]['asignados']++;
                $assigned++;
            }
        });

        return $assigned;
    }

    public function toggleActive(GrupoAcademico $grupo): GrupoAcademico
    {
        if ($grupo->activo) {
            if ($this->assignedCount($grupo) > 0) {
                throw ValidationException::withMessages([
                    'grupo' => 'No se puede desactivar un grupo con postulantes asignados.',
                ]);
            }

            if ($this->hasActiveAcademicAssignments($grupo)) {
                throw ValidationException::withMessages([
                    'grupo' => 'No se puede desactivar un grupo con asignaciones académicas activas.',
                ]);
            }
        }

        $grupo->update(['activo' => ! $grupo->activo]);

        return $grupo;
    }

    public function serialize(GrupoAcademico $grupo): array
    {
        $assigned = $grupo->postulaciones_count ?? $this->assignedCount($grupo);

        return [
            'id_grupo' => $grupo->id_grupo,
            'id_gestion' => $grupo->id_gestion,
            'nombre' => $grupo->nombre,
            'capacidad_maxima' => $grupo->capacidad_maxima,
            'activo' => $grupo->activo,
            'postulantes_asignados' => $assigned,
            'cupos_disponibles' => max($grupo->capacidad_maxima - $assigned, 0),
            'postulantes' => $grupo->postulaciones
                ->sortBy('fecha_postulacion')
                ->map(function (Postulacion $postulacion): array {
                    $postulante = $postulacion->postulante;
                    $usuario = $postulante?->usuario;

                    return [
                        'id_postulante' => $postulante?->id_postulante,
                        'ci' => $usuario?->ci,
                        'nombre' => $usuario?->nombre,
                        'apellido' => $usuario?->apellido,
                        'nombre_completo' => $usuario?->name,
                        'username' => $usuario?->username,
                        'correo' => $usuario?->correo,
                        'telefono' => $usuario?->telefono,
                        'ciudad' => $postulante?->ciudad,
                        'colegio_procedencia' => $postulante?->colegio_procedencia,
                        'documentacion_completa' => $postulante?->documentacion_completa ?? false,
                        'estado_admision' => $postulacion->estado_admision,
                        'carrera_opcion1' => $postulacion->carreraOpcion1?->nombre,
                        'carrera_opcion2' => $postulacion->carreraOpcion2?->nombre,
                    ];
                })
                ->values(),
        ];
    }

    private function calculateGroups(int $totalInscritos): int
    {
        try {
            return (int) DB::selectOne('SELECT calcular_grupos(?) AS total', [$totalInscritos])->total;
        } catch (\Throwable) {
            return (int) ceil($totalInscritos / self::GROUP_CAPACITY);
        }
    }

    private function ensureUniqueName(int $gestionId, string $name, ?int $ignoreGroupId = null): void
    {
        $exists = GrupoAcademico::query()
            ->where('id_gestion', $gestionId)
            ->where('nombre', $name)
            ->when($ignoreGroupId, fn ($query) => $query->where('id_grupo', '!=', $ignoreGroupId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'nombre' => 'Ya existe un grupo con ese nombre en la gestión activa.',
            ]);
        }
    }

    private function assignedCount(GrupoAcademico $grupo): int
    {
        return Postulacion::query()
            ->where('id_grupo', $grupo->id_grupo)
            ->count();
    }

    private function hasActiveAcademicAssignments(GrupoAcademico $grupo): bool
    {
        return DB::table('asignacion_academica')
            ->where('id_grupo', $grupo->id_grupo)
            ->where('activo', true)
            ->exists();
    }

    private function groupLetter(int $index): string
    {
        $letters = '';

        do {
            $letters = chr(65 + ($index % 26)).$letters;
            $index = intdiv($index, 26) - 1;
        } while ($index >= 0);

        return $letters;
    }

    private function nextAvailableGroupIndex(array $groups): ?int
    {
        $available = null;

        foreach ($groups as $index => $group) {
            if ($group['asignados'] >= $group['capacidad_maxima']) {
                continue;
            }

            if ($available === null || $group['asignados'] < $groups[$available]['asignados']) {
                $available = $index;
            }
        }

        return $available;
    }
}
