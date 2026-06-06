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
                'gestion' => 'No existe una gestión académica activa.',
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
                'id_gestion' => 'La gestión académica seleccionada no existe.',
            ]);
        }

        return $gestion;
    }

    public function dashboard(int $idGestion): array
    {
        $postulaciones = $this->postulacionesBase($idGestion)->get();
        $resultados = $postulaciones->map(fn (Postulacion $postulacion): array => $this->calculateFinalResult($postulacion->id_postulacion));

        return [
            'total_postulantes' => $postulaciones->count(),
            'postulantes_con_documentacion' => $postulaciones
                ->filter(fn (Postulacion $postulacion): bool => (bool) $postulacion->postulante?->documentacion_completa)
                ->count(),
            'grupos_activos' => GrupoAcademico::query()->where('id_gestion', $idGestion)->where('activo', true)->count(),
            'materias_activas' => MateriaCup::query()->where('activo', true)->count(),
            'docentes_contratados' => Docente::query()->where('activo', true)->where('contratado', true)->count(),
            'asignaciones_activas' => AsignacionAcademica::query()
                ->where('activo', true)
                ->whereHas('grupo', fn (Builder $query) => $query->where('id_gestion', $idGestion))
                ->count(),
            'aprobados' => $resultados->where('estado_final', 'APROBADO')->count(),
            'reprobados' => $resultados->where('estado_final', 'REPROBADO')->count(),
            'pendientes' => $resultados->where('estado_final', 'PENDIENTE')->count(),
        ];
    }

    public function listaGeneralPostulantes(int $idGestion): Collection
    {
        return $this->postulacionesBase($idGestion)
            ->orderBy('fecha_postulacion')
            ->get()
            ->map(fn (Postulacion $postulacion): array => $this->serializePostulacion($postulacion))
            ->values();
    }

    public function postulantesAprobados(int $idGestion): Collection
    {
        return $this->listaGeneralPostulantes($idGestion)
            ->where('estado_final', 'APROBADO')
            ->values();
    }

    public function postulantesReprobados(int $idGestion): Collection
    {
        return $this->listaGeneralPostulantes($idGestion)
            ->where('estado_final', 'REPROBADO')
            ->values();
    }

    public function promediosPorPostulante(int $idGestion): Collection
    {
        return $this->listaGeneralPostulantes($idGestion)
            ->map(fn (array $postulante): array => [
                'id_postulacion' => $postulante['id_postulacion'],
                'ci' => $postulante['ci'],
                'nombre_completo' => $postulante['nombre_completo'],
                'promedio_final' => $postulante['promedio_final'],
                'estado_final' => $postulante['estado_final'],
            ])
            ->values();
    }

    public function estadisticasPorMateria(int $idGestion): Collection
    {
        $postulacionIds = Postulacion::query()
            ->where('id_gestion', $idGestion)
            ->pluck('id_postulacion');

        return MateriaCup::query()
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

    public function gruposConCapacidad(int $idGestion): Collection
    {
        return GrupoAcademico::query()
            ->withCount('postulaciones')
            ->where('id_gestion', $idGestion)
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

    public function docentesPorGrupo(int $idGestion): Collection
    {
        return AsignacionAcademica::query()
            ->with(['grupo', 'materia', 'docente.usuario', 'aula', 'horario'])
            ->whereHas('grupo', fn (Builder $query) => $query->where('id_gestion', $idGestion))
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

    public function gruposConMasAprobados(int $idGestion): Collection
    {
        return GrupoAcademico::query()
            ->with('postulaciones')
            ->where('id_gestion', $idGestion)
            ->get()
            ->map(function (GrupoAcademico $grupo): array {
                $total = $grupo->postulaciones->count();
                $aprobados = $grupo->postulaciones
                    ->map(fn (Postulacion $postulacion): array => $this->calculateFinalResult($postulacion->id_postulacion))
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

    public function exportCsv(string $tipo, int $idGestion): StreamedResponse
    {
        [$filename, $headers, $rows] = match ($tipo) {
            'postulantes' => [
                'postulantes.csv',
                ['CI', 'Nombre completo', 'Correo', 'Carrera 1', 'Carrera 2', 'Estado admision', 'Grupo', 'Promedio final', 'Estado final'],
                $this->listaGeneralPostulantes($idGestion),
            ],
            'aprobados' => [
                'aprobados.csv',
                ['CI', 'Nombre completo', 'Carrera', 'Promedio final'],
                $this->postulantesAprobados($idGestion),
            ],
            'reprobados' => [
                'reprobados.csv',
                ['CI', 'Nombre completo', 'Carrera', 'Promedio final'],
                $this->postulantesReprobados($idGestion),
            ],
            'materias' => [
                'estadisticas-materia.csv',
                ['Materia', 'Promedio', 'Cantidad notas', 'Aprobados', 'Reprobados', 'Pendientes'],
                $this->estadisticasPorMateria($idGestion),
            ],
            'docentes-grupo' => [
                'docentes-por-grupo.csv',
                ['Grupo', 'Materia', 'Docente', 'Horario', 'Aula'],
                $this->docentesPorGrupo($idGestion),
            ],
            'grupos-aprobados' => [
                'grupos-aprobados.csv',
                ['Grupo', 'Aprobados', 'Total postulantes', 'Porcentaje aprobados'],
                $this->gruposConMasAprobados($idGestion),
            ],
            default => throw ValidationException::withMessages(['tipo' => 'El tipo de reporte no es válido.']),
        };

        return response()->streamDownload(function () use ($headers, $rows, $tipo): void {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);

            foreach ($rows as $row) {
                fputcsv($file, $this->csvRow($tipo, $row));
            }

            fclose($file);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function calculateFinalResult(int $idPostulacion): array
    {
        $notas = DB::table('nota')
            ->where('id_postulacion', $idPostulacion)
            ->pluck('nota')
            ->map(fn ($value) => (float) $value);
        $count = $notas->count();
        $promedio = $notas->isEmpty() ? null : round($notas->avg(), 2);

        return [
            'total_notas' => $count,
            'promedio_final' => $promedio,
            'estado_final' => $count < 12 || $promedio === null
                ? 'PENDIENTE'
                : ($promedio >= 60 ? 'APROBADO' : 'REPROBADO'),
        ];
    }

    private function postulacionesBase(int $idGestion): Builder
    {
        return Postulacion::query()
            ->with([
                'postulante.usuario',
                'carreraOpcion1',
                'carreraOpcion2',
                'carreraAdmitida',
                'grupo',
            ])
            ->where('id_gestion', $idGestion);
    }

    private function serializePostulacion(Postulacion $postulacion): array
    {
        $usuario = $postulacion->postulante?->usuario;
        $result = $this->calculateFinalResult($postulacion->id_postulacion);

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
