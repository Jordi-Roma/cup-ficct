<?php

namespace App\Modules\ReportesMonitoreo\Services;

use App\Modules\GestionAcademica\Models\AsignacionAcademica;
use App\Modules\GestionAcademica\Models\Docente;
use App\Modules\GestionAcademica\Models\GestionAcademica;
use App\Modules\GestionAcademica\Models\GrupoAcademico;
use App\Modules\GestionAcademica\Models\MateriaCup;
use App\Modules\RegistroPostulantes\Models\Postulacion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteService
{
    private const ESTADOS_REPORTE = [
        'TODOS' => 'Todos',
        'APROBADO' => 'Aprobados',
        'REPROBADO' => 'Reprobados',
        'ADMITIDO' => 'Admitidos',
        'NO_ADMITIDO' => 'No admitidos',
        'PENDIENTE' => 'Pendientes',
    ];

    public function gestiones(): Collection
    {
        return GestionAcademica::query()
            ->orderByDesc('fecha_inicio')
            ->get()
            ->map(fn (GestionAcademica $gestion): array => [
                'id_gestion' => $gestion->id_gestion,
                'nombre' => $gestion->nombre,
                'activo' => (bool) $gestion->activo,
            ]);
    }

    public function activeGestion(): GestionAcademica
    {
        $gestion = GestionAcademica::query()->where('activo', true)->first();

        if (! $gestion) {
            throw ValidationException::withMessages([
                'gestion' => 'No existe una gestion academica activa.',
            ]);
        }

        return $gestion;
    }

    public function resolveGestion(?int $idGestion): GestionAcademica
    {
        if (! $idGestion) {
            return $this->activeGestion();
        }

        $gestion = GestionAcademica::query()->find($idGestion);

        if (! $gestion) {
            throw ValidationException::withMessages([
                'id_gestion' => 'La gestion academica seleccionada no existe.',
            ]);
        }

        return $gestion;
    }

    public function resolveReportFilters(array $input): array
    {
        $gestion = $this->resolveGestion(
            filled($input['id_gestion'] ?? null) ? (int) $input['id_gestion'] : null,
        );
        $idGrupo = filled($input['id_grupo'] ?? null) && $input['id_grupo'] !== 'TODOS'
            ? (int) $input['id_grupo']
            : null;
        $idMateria = filled($input['id_materia'] ?? null) && $input['id_materia'] !== 'TODOS'
            ? (int) $input['id_materia']
            : null;
        $estado = filled($input['estado'] ?? null) ? (string) $input['estado'] : 'TODOS';

        if ($idGrupo && ! GrupoAcademico::query()->where('id_gestion', $gestion->id_gestion)->whereKey($idGrupo)->exists()) {
            $idGrupo = null;
        }

        if ($idMateria && ! MateriaCup::query()->whereKey($idMateria)->exists()) {
            $idMateria = null;
        }

        if (! array_key_exists($estado, self::ESTADOS_REPORTE)) {
            $estado = 'TODOS';
        }

        return [
            'id_gestion' => $gestion->id_gestion,
            'id_grupo' => $idGrupo,
            'id_materia' => $idMateria,
            'estado' => $estado,
        ];
    }

    public function reportOptions(array $filters): array
    {
        return [
            'gestiones' => $this->gestiones(),
            'grupos' => GrupoAcademico::query()
                ->where('id_gestion', $filters['id_gestion'])
                ->orderBy('nombre')
                ->get()
                ->map(fn (GrupoAcademico $grupo): array => [
                    'id_grupo' => $grupo->id_grupo,
                    'nombre' => $grupo->nombre,
                    'turno' => $grupo->turno,
                    'activo' => (bool) $grupo->activo,
                ])
                ->values(),
            'materias' => MateriaCup::query()
                ->orderBy('nombre')
                ->get()
                ->map(fn (MateriaCup $materia): array => [
                    'id_materia' => $materia->id_materia,
                    'nombre' => $materia->nombre,
                    'activo' => (bool) $materia->activo,
                ])
                ->values(),
            'estados' => collect(self::ESTADOS_REPORTE)
                ->map(fn (string $label, string $value): array => [
                    'value' => $value,
                    'label' => $label,
                ])
                ->values(),
        ];
    }

    public function dashboard(int|array $filters): array
    {
        $filters = $this->normalizeFilters($filters);
        $postulaciones = $this->postulacionesBase($filters)->get();
        $lista = $postulaciones
            ->map(fn (Postulacion $postulacion): array => $this->serializePostulacion($postulacion, $filters['id_materia']))
            ->filter(fn (array $row): bool => $this->matchesEstado($row, $filters['estado']))
            ->values();

        return [
            'total_postulantes' => $lista->count(),
            'postulantes_con_documentacion' => $postulaciones
                ->filter(fn (Postulacion $postulacion): bool => (bool) $postulacion->postulante?->documentacion_completa)
                ->count(),
            'grupos_activos' => GrupoAcademico::query()
                ->where('id_gestion', $filters['id_gestion'])
                ->when($filters['id_grupo'], fn (Builder $query, int $idGrupo) => $query->whereKey($idGrupo))
                ->where('activo', true)
                ->count(),
            'materias_activas' => MateriaCup::query()
                ->when($filters['id_materia'], fn (Builder $query, int $idMateria) => $query->whereKey($idMateria))
                ->where('activo', true)
                ->count(),
            'docentes_contratados' => Docente::query()->where('activo', true)->where('contratado', true)->count(),
            'asignaciones_activas' => AsignacionAcademica::query()
                ->where('activo', true)
                ->whereHas('grupo', fn (Builder $query) => $query->where('id_gestion', $filters['id_gestion']))
                ->when($filters['id_grupo'], fn (Builder $query, int $idGrupo) => $query->where('id_grupo', $idGrupo))
                ->when($filters['id_materia'], fn (Builder $query, int $idMateria) => $query->where('id_materia', $idMateria))
                ->count(),
            'aprobados' => $lista->where('estado_final', 'APROBADO')->count(),
            'reprobados' => $lista->where('estado_final', 'REPROBADO')->count(),
            'pendientes' => $lista->where('estado_final', 'PENDIENTE')->count(),
            'admitidos' => $lista->where('estado_admision', 'ADMITIDO')->count(),
            'no_admitidos' => $lista->where('estado_admision', 'NO_ADMITIDO')->count(),
        ];
    }

    public function listaGeneralPostulantes(int|array $filters): Collection
    {
        $filters = $this->normalizeFilters($filters);

        return $this->postulacionesBase($filters)
            ->orderBy('fecha_postulacion')
            ->get()
            ->map(fn (Postulacion $postulacion): array => $this->serializePostulacion($postulacion, $filters['id_materia']))
            ->filter(fn (array $row): bool => $this->matchesEstado($row, $filters['estado']))
            ->values();
    }

    public function postulantesAprobados(int|array $filters): Collection
    {
        return $this->listaGeneralPostulantes($filters)
            ->where('estado_final', 'APROBADO')
            ->values();
    }

    public function postulantesReprobados(int|array $filters): Collection
    {
        return $this->listaGeneralPostulantes($filters)
            ->where('estado_final', 'REPROBADO')
            ->values();
    }

    public function promediosPorPostulante(int|array $filters): Collection
    {
        return $this->listaGeneralPostulantes($filters)
            ->map(fn (array $postulante): array => [
                'id_postulacion' => $postulante['id_postulacion'],
                'ci' => $postulante['ci'],
                'nombre_completo' => $postulante['nombre_completo'],
                'promedio_final' => $postulante['promedio_final'],
                'estado_final' => $postulante['estado_final'],
            ])
            ->values();
    }

    public function estadisticasPorMateria(int|array $filters): Collection
    {
        $filters = $this->normalizeFilters($filters);
        $postulacionIds = Postulacion::query()
            ->where('id_gestion', $filters['id_gestion'])
            ->when($filters['id_grupo'], fn (Builder $query, int $idGrupo) => $query->where('id_grupo', $idGrupo))
            ->pluck('id_postulacion');

        return MateriaCup::query()
            ->when($filters['id_materia'], fn (Builder $query, int $idMateria) => $query->whereKey($idMateria))
            ->orderBy('nombre')
            ->get()
            ->map(function (MateriaCup $materia) use ($postulacionIds): array {
                $notas = DB::table('nota')
                    ->whereIn('id_postulacion', $postulacionIds)
                    ->where('id_materia', $materia->id_materia)
                    ->get();
                $porPostulacion = $notas->groupBy('id_postulacion');
                $estados = $porPostulacion->map(function (Collection $items): string {
                    $promedio = round((float) $items->avg('nota'), 2);

                    if ($items->count() < 3) {
                        return 'PENDIENTE';
                    }

                    return $promedio >= 60 ? 'APROBADO' : 'REPROBADO';
                });

                return [
                    'id_materia' => $materia->id_materia,
                    'materia' => $materia->nombre,
                    'promedio' => $notas->isEmpty() ? null : round((float) $notas->avg('nota'), 2),
                    'cantidad_notas' => $notas->count(),
                    'aprobados' => $estados->filter(fn (string $estado): bool => $estado === 'APROBADO')->count(),
                    'reprobados' => $estados->filter(fn (string $estado): bool => $estado === 'REPROBADO')->count(),
                    'pendientes' => $estados->filter(fn (string $estado): bool => $estado === 'PENDIENTE')->count(),
                ];
            })
            ->values();
    }

    public function gruposConCapacidad(int|array $filters): Collection
    {
        $filters = $this->normalizeFilters($filters);

        return GrupoAcademico::query()
            ->withCount('postulaciones')
            ->where('id_gestion', $filters['id_gestion'])
            ->when($filters['id_grupo'], fn (Builder $query, int $idGrupo) => $query->whereKey($idGrupo))
            ->orderBy('nombre')
            ->get()
            ->map(fn (GrupoAcademico $grupo): array => [
                'id_grupo' => $grupo->id_grupo,
                'grupo' => $grupo->nombre,
                'capacidad_maxima' => $grupo->capacidad_maxima,
                'postulantes_asignados' => $grupo->postulaciones_count,
                'cupos_disponibles' => max(0, $grupo->capacidad_maxima - $grupo->postulaciones_count),
                'activo' => (bool) $grupo->activo,
            ])
            ->values();
    }

    public function docentesPorGrupo(int|array $filters): Collection
    {
        $filters = $this->normalizeFilters($filters);

        return AsignacionAcademica::query()
            ->with(['grupo', 'materia', 'docente.usuario', 'aula', 'horario'])
            ->whereHas('grupo', fn (Builder $query) => $query->where('id_gestion', $filters['id_gestion']))
            ->when($filters['id_grupo'], fn (Builder $query, int $idGrupo) => $query->where('id_grupo', $idGrupo))
            ->when($filters['id_materia'], fn (Builder $query, int $idMateria) => $query->where('id_materia', $idMateria))
            ->orderBy('id_grupo')
            ->get()
            ->map(fn (AsignacionAcademica $asignacion): array => [
                'id_asignacion' => $asignacion->id_asignacion,
                'grupo' => $asignacion->grupo?->nombre,
                'materia' => $asignacion->materia?->nombre,
                'docente' => $asignacion->docente?->usuario?->name,
                'horario' => $asignacion->horario
                    ? "Lunes a sabado {$asignacion->horario->hora_inicio}-{$asignacion->horario->hora_fin}"
                    : null,
                'aula' => $asignacion->aula?->nombre,
                'activo' => (bool) $asignacion->activo,
            ])
            ->values();
    }

    public function gruposConMasAprobados(int|array $filters): Collection
    {
        $filters = $this->normalizeFilters($filters);

        return GrupoAcademico::query()
            ->with('postulaciones')
            ->where('id_gestion', $filters['id_gestion'])
            ->when($filters['id_grupo'], fn (Builder $query, int $idGrupo) => $query->whereKey($idGrupo))
            ->get()
            ->map(function (GrupoAcademico $grupo) use ($filters): array {
                $postulaciones = $grupo->postulaciones;
                $total = $postulaciones->count();
                $aprobados = $postulaciones
                    ->filter(function (Postulacion $postulacion) use ($filters): bool {
                        if (! $filters['id_materia']) {
                            return true;
                        }

                        return DB::table('nota')
                            ->where('id_postulacion', $postulacion->id_postulacion)
                            ->where('id_materia', $filters['id_materia'])
                            ->exists();
                    })
                    ->map(fn (Postulacion $postulacion): array => $this->calculateFinalResult($postulacion->id_postulacion, $filters['id_materia']))
                    ->where('estado_final', 'APROBADO')
                    ->count();

                return [
                    'id_grupo' => $grupo->id_grupo,
                    'grupo' => $grupo->nombre,
                    'aprobados' => $aprobados,
                    'total_postulantes' => $total,
                    'porcentaje_aprobados' => $total === 0 ? 0 : round(($aprobados / $total) * 100, 2),
                ];
            })
            ->sortByDesc('aprobados')
            ->values();
    }

    public function exportCsv(string $tipo, int|array $filters): StreamedResponse
    {
        $filters = $this->normalizeFilters($filters);
        [$filename, $headers, $rows] = match ($tipo) {
            'postulantes' => [
                'reporte-postulantes.csv',
                ['CI', 'Nombre completo', 'Correo', 'Carrera 1', 'Carrera 2', 'Estado admision', 'Grupo', 'Promedio final', 'Estado final'],
                $this->listaGeneralPostulantes($filters),
            ],
            'aprobados' => [
                'reporte-aprobados.csv',
                ['CI', 'Nombre completo', 'Carrera', 'Promedio final'],
                $this->postulantesAprobados($filters),
            ],
            'reprobados' => [
                'reporte-reprobados.csv',
                ['CI', 'Nombre completo', 'Carrera', 'Promedio final'],
                $this->postulantesReprobados($filters),
            ],
            'materias' => [
                'reporte-estadisticas-materia.csv',
                ['Materia', 'Promedio', 'Cantidad notas', 'Aprobados', 'Reprobados', 'Pendientes'],
                $this->estadisticasPorMateria($filters),
            ],
            'docentes-grupo' => [
                'reporte-docentes-por-grupo.csv',
                ['Grupo', 'Materia', 'Docente', 'Horario', 'Aula'],
                $this->docentesPorGrupo($filters),
            ],
            'grupos-aprobados' => [
                'reporte-grupos-aprobados.csv',
                ['Grupo', 'Aprobados', 'Total postulantes', 'Porcentaje aprobados'],
                $this->gruposConMasAprobados($filters),
            ],
            default => throw ValidationException::withMessages(['tipo' => 'El tipo de reporte no es valido.']),
        };

        return response()->streamDownload(function () use ($headers, $rows, $tipo): void {
            $file = fopen('php://output', 'w');
            fwrite($file, "\xEF\xBB\xBF");
            fputcsv($file, $headers, ';');

            foreach ($rows as $row) {
                fputcsv($file, $this->csvRow($tipo, $row), ';');
            }

            fclose($file);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function calculateFinalResult(int $idPostulacion, ?int $idMateria = null): array
    {
        $notas = DB::table('nota')
            ->where('id_postulacion', $idPostulacion)
            ->when($idMateria, fn ($query, int $materia) => $query->where('id_materia', $materia))
            ->pluck('nota')
            ->map(fn ($value) => (float) $value);
        $count = $notas->count();
        $promedio = $notas->isEmpty() ? null : round($notas->avg(), 2);
        $requiredNotes = $idMateria ? 3 : 12;

        return [
            'total_notas' => $count,
            'promedio_final' => $promedio,
            'estado_final' => $count < $requiredNotes || $promedio === null
                ? 'PENDIENTE'
                : ($promedio >= 60 ? 'APROBADO' : 'REPROBADO'),
        ];
    }

    private function postulacionesBase(int|array $filters): Builder
    {
        $filters = $this->normalizeFilters($filters);

        return Postulacion::query()
            ->with([
                'postulante.usuario',
                'carreraOpcion1',
                'carreraOpcion2',
                'carreraAdmitida',
                'grupo',
            ])
            ->where('id_gestion', $filters['id_gestion'])
            ->when($filters['id_grupo'], fn (Builder $query, int $idGrupo) => $query->where('id_grupo', $idGrupo))
            ->when($filters['id_materia'], function (Builder $query, int $idMateria): void {
                $query->whereExists(function ($subquery) use ($idMateria): void {
                    $subquery->selectRaw('1')
                        ->from('nota')
                        ->whereColumn('nota.id_postulacion', 'postulacion.id_postulacion')
                        ->where('nota.id_materia', $idMateria);
                });
            });
    }

    private function serializePostulacion(Postulacion $postulacion, ?int $idMateria = null): array
    {
        $usuario = $postulacion->postulante?->usuario;
        $result = $this->calculateFinalResult($postulacion->id_postulacion, $idMateria);

        return [
            'id_postulacion' => $postulacion->id_postulacion,
            'id_postulante' => $postulacion->id_postulante,
            'ci' => $usuario?->ci,
            'nombre_completo' => $usuario?->name,
            'correo' => $usuario?->correo,
            'carrera_opcion1' => $postulacion->carreraOpcion1?->nombre,
            'carrera_opcion2' => $postulacion->carreraOpcion2?->nombre,
            'carrera_admitida' => $postulacion->carreraAdmitida?->nombre,
            'estado_admision' => $postulacion->estado_admision,
            'grupo' => $postulacion->grupo?->nombre,
            'promedio_final' => $result['promedio_final'],
            'estado_final' => $result['estado_final'],
            'total_notas' => $result['total_notas'],
        ];
    }

    private function normalizeFilters(int|array $filters): array
    {
        if (is_int($filters)) {
            return [
                'id_gestion' => $filters,
                'id_grupo' => null,
                'id_materia' => null,
                'estado' => 'TODOS',
            ];
        }

        return [
            'id_gestion' => (int) $filters['id_gestion'],
            'id_grupo' => filled($filters['id_grupo'] ?? null) ? (int) $filters['id_grupo'] : null,
            'id_materia' => filled($filters['id_materia'] ?? null) ? (int) $filters['id_materia'] : null,
            'estado' => $filters['estado'] ?? 'TODOS',
        ];
    }

    private function matchesEstado(array $row, string $estado): bool
    {
        return match ($estado) {
            'APROBADO', 'REPROBADO' => $row['estado_final'] === $estado,
            'ADMITIDO', 'NO_ADMITIDO' => $row['estado_admision'] === $estado,
            'PENDIENTE' => $row['estado_final'] === 'PENDIENTE' || $row['estado_admision'] === 'PENDIENTE',
            default => true,
        };
    }

    private function csvRow(string $tipo, array $row): array
    {
        return match ($tipo) {
            'postulantes' => [
                $row['ci'],
                $row['nombre_completo'],
                $row['correo'],
                $row['carrera_opcion1'],
                $row['carrera_opcion2'],
                $row['estado_admision'],
                $row['grupo'],
                $row['promedio_final'],
                $row['estado_final'],
            ],
            'aprobados', 'reprobados' => [
                $row['ci'],
                $row['nombre_completo'],
                $row['carrera_opcion1'],
                $row['promedio_final'],
            ],
            'materias' => [
                $row['materia'],
                $row['promedio'],
                $row['cantidad_notas'],
                $row['aprobados'],
                $row['reprobados'],
                $row['pendientes'],
            ],
            'docentes-grupo' => [
                $row['grupo'],
                $row['materia'],
                $row['docente'],
                $row['horario'],
                $row['aula'],
            ],
            'grupos-aprobados' => [
                $row['grupo'],
                $row['aprobados'],
                $row['total_postulantes'],
                $row['porcentaje_aprobados'],
            ],
        };
    }
}
