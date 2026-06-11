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

    public const TURNOS = [
        'MANANA' => ['label' => 'Mañana', 'prefix' => 'M'],
        'TARDE' => ['label' => 'Tarde', 'prefix' => 'T'],
        'NOCHE' => ['label' => 'Noche', 'prefix' => 'N'],
    ];

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
            ->orderByRaw("array_position(ARRAY['MANANA','TARDE','NOCHE'], turno)")
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
        $turnos = collect(self::TURNOS)
            ->map(function (array $turnoConfig, string $turno) use ($gestion): array {
                $totalInscritos = $this->countEligiblePostulantesByTurno($turno);
                $gruposNecesarios = $this->calculateGroups($totalInscritos);
                $gruposActivos = GrupoAcademico::query()
                    ->where('id_gestion', $gestion->id_gestion)
                    ->where('turno', $turno)
                    ->where('activo', true)
                    ->count();

                return [
                    'turno' => $turno,
                    'label' => $turnoConfig['label'],
                    'prefix' => $turnoConfig['prefix'],
                    'total_inscritos' => $totalInscritos,
                    'grupos_necesarios' => $gruposNecesarios,
                    'grupos_activos' => $gruposActivos,
                    'grupos_faltantes' => max($gruposNecesarios - $gruposActivos, 0),
                ];
            })
            ->values();

        return [
            'total_inscritos' => $turnos->sum('total_inscritos'),
            'grupos_necesarios' => $turnos->sum('grupos_necesarios'),
            'grupos_activos' => $turnos->sum('grupos_activos'),
            'grupos_faltantes' => $turnos->sum('grupos_faltantes'),
            'turnos' => $turnos,
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
            'turno' => $data['turno'],
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
            'turno' => $data['turno'],
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

        $created = 0;

        foreach ($summary['turnos'] as $turnoSummary) {
            $turno = $turnoSummary['turno'];
            $turnoMissing = $turnoSummary['grupos_faltantes'];

            if ($turnoMissing === 0) {
                continue;
            }

            $existingNames = GrupoAcademico::query()
                ->where('id_gestion', $gestion->id_gestion)
                ->pluck('nombre')
                ->all();
            $index = 1;

            while ($turnoMissing > 0) {
                $name = $this->groupCode($turno, $index);
                $index++;

                if (in_array($name, $existingNames, true)) {
                    continue;
                }

                GrupoAcademico::create([
                    'id_gestion' => $gestion->id_gestion,
                    'nombre' => $name,
                    'turno' => $turno,
                    'capacidad_maxima' => self::GROUP_CAPACITY,
                    'activo' => true,
                ]);

                $existingNames[] = $name;
                $created++;
                $turnoMissing--;
            }
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
            ->orderByRaw("array_position(ARRAY['MANANA','TARDE','NOCHE'], turno)")
            ->orderBy('nombre')
            ->get()
            ->map(fn (GrupoAcademico $grupo) => [
                'id_grupo' => $grupo->id_grupo,
                'turno' => $grupo->turno,
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

        foreach (self::TURNOS as $turno => $config) {
            $postulacionesTurno = $postulaciones->where('turno_preferido', $turno)->count();
            $availableCapacity = collect($groups)
                ->where('turno', $turno)
                ->sum(fn (array $group) => max($group['capacidad_maxima'] - $group['asignados'], 0));

            if ($postulacionesTurno > $availableCapacity) {
                throw ValidationException::withMessages([
                    'grupos' => "No hay cupos suficientes en grupos del turno {$config['label']}.",
                ]);
            }
        }

        $assigned = 0;

        DB::transaction(function () use (&$groups, $postulaciones, &$assigned): void {
            foreach ($postulaciones as $postulacion) {
                $groupIndex = $this->nextAvailableGroupIndex($groups, $postulacion->turno_preferido);

                if ($groupIndex === null) {
                    throw ValidationException::withMessages([
                        'grupos' => 'No hay grupos activos con cupos disponibles para el turno del postulante.',
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
            'turno' => $grupo->turno,
            'turno_label' => $this->turnoLabel($grupo->turno),
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
                        'turno_preferido' => $postulacion->turno_preferido,
                        'turno_preferido_label' => $this->turnoLabel($postulacion->turno_preferido),
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

    private function countEligiblePostulantesByTurno(string $turno): int
    {
        $gestion = $this->activeGestion();

        return Postulacion::query()
            ->join('postulante', 'postulacion.id_postulante', '=', 'postulante.id_postulante')
            ->where('postulacion.id_gestion', $gestion->id_gestion)
            ->where('postulacion.turno_preferido', $turno)
            ->where('postulante.documentacion_completa', true)
            ->count();
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

    private function groupCode(string $turno, int $index): string
    {
        return self::TURNOS[$turno]['prefix'].str_pad((string) $index, 3, '0', STR_PAD_LEFT);
    }

    private function nextAvailableGroupIndex(array $groups, string $turno): ?int
    {
        foreach ($groups as $index => $group) {
            if ($group['turno'] !== $turno) {
                continue;
            }

            if ($group['asignados'] >= $group['capacidad_maxima']) {
                continue;
            }

            return $index;
        }

        return null;
    }

    private function turnoLabel(?string $turno): string
    {
        return self::TURNOS[$turno]['label'] ?? 'Sin turno';
    }
}
