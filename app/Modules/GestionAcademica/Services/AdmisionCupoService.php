<?php

namespace App\Modules\GestionAcademica\Services;

use App\Modules\GestionAcademica\Models\Carrera;
use App\Modules\GestionAcademica\Models\CupoCarrera;
use App\Modules\GestionAcademica\Models\GestionAcademica;
use App\Modules\RegistroPostulantes\Models\Postulacion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdmisionCupoService
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

    public function carreras(): Collection
    {
        return Carrera::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get()
            ->map(fn (Carrera $carrera): array => [
                'id_carrera' => $carrera->id_carrera,
                'nombre' => $carrera->nombre,
            ]);
    }

    public function cupos(int $idGestion): Collection
    {
        $cupos = CupoCarrera::query()
            ->where('id_gestion', $idGestion)
            ->get()
            ->keyBy('id_carrera');
        $admitidos = $this->admitidosPorCarrera($idGestion);

        return Carrera::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get()
            ->map(function (Carrera $carrera) use ($cupos, $admitidos): array {
                $cupo = $cupos->get($carrera->id_carrera);
                $cupoMaximo = $cupo?->cupo_maximo ?? 0;
                $admitidosActuales = (int) ($admitidos[$carrera->id_carrera] ?? 0);

                return [
                    'id_carrera' => $carrera->id_carrera,
                    'carrera' => $carrera->nombre,
                    'id_cupo' => $cupo?->id_cupo,
                    'cupo_maximo' => $cupoMaximo,
                    'admitidos' => $admitidosActuales,
                    'disponibles' => max(0, $cupoMaximo - $admitidosActuales),
                ];
            })
            ->values();
    }

    public function resumen(int $idGestion): array
    {
        $postulantes = $this->postulantes($idGestion);

        return [
            'total_postulantes' => $postulantes->count(),
            'admitidos' => $postulantes->where('estado_admision', 'ADMITIDO')->count(),
            'no_admitidos' => $postulantes->where('estado_admision', 'NO_ADMITIDO')->count(),
            'pendientes' => $postulantes->where('estado_admision', 'PENDIENTE')->count(),
            'cupos_configurados' => CupoCarrera::query()->where('id_gestion', $idGestion)->count(),
        ];
    }

    public function upsertCupo(array $data): CupoCarrera
    {
        $admitidos = $this->admitidosActuales((int) $data['id_gestion'], (int) $data['id_carrera']);

        if ((int) $data['cupo_maximo'] < $admitidos) {
            throw ValidationException::withMessages([
                'cupo_maximo' => 'No se puede bajar el cupo por debajo de los admitidos actuales.',
            ]);
        }

        return CupoCarrera::query()->updateOrCreate(
            [
                'id_carrera' => $data['id_carrera'],
                'id_gestion' => $data['id_gestion'],
            ],
            [
                'cupo_maximo' => $data['cupo_maximo'],
            ],
        );
    }

    public function postulantes(int $idGestion): Collection
    {
        return $this->postulacionesBase($idGestion)
            ->orderBy('fecha_postulacion')
            ->get()
            ->map(fn (Postulacion $postulacion): array => $this->serializePostulacion($postulacion))
            ->values();
    }

    public function processAdmission(int $idGestion): array
    {
        return DB::transaction(function () use ($idGestion): array {
            $cupos = CupoCarrera::query()
                ->where('id_gestion', $idGestion)
                ->pluck('cupo_maximo', 'id_carrera')
                ->map(fn ($value) => (int) $value)
                ->all();
            $ocupados = array_fill_keys(array_keys($cupos), 0);
            $postulaciones = $this->postulacionesBase($idGestion)->get();

            Postulacion::query()
                ->where('id_gestion', $idGestion)
                ->update([
                    'estado_admision' => 'PENDIENTE',
                    'id_carrera_admitida' => null,
                ]);

            $procesables = $postulaciones
                ->map(function (Postulacion $postulacion): array {
                    return [
                        'postulacion' => $postulacion,
                        'resultado' => $this->calculateFinalResult($postulacion->id_postulacion),
                    ];
                });

            foreach ($procesables as $item) {
                $postulacion = $item['postulacion'];
                $resultado = $item['resultado'];

                if (! $postulacion->postulante?->documentacion_completa || $resultado['estado_academico'] === 'PENDIENTE') {
                    continue;
                }

                if ($resultado['estado_academico'] === 'REPROBADO') {
                    $postulacion->update([
                        'estado_admision' => 'NO_ADMITIDO',
                        'id_carrera_admitida' => null,
                    ]);
                }
            }

            $aprobados = $procesables
                ->filter(fn (array $item): bool => $item['postulacion']->postulante?->documentacion_completa
                    && $item['resultado']['estado_academico'] === 'APROBADO')
                ->sortBy([
                    fn (array $a, array $b): int => $b['resultado']['promedio_final'] <=> $a['resultado']['promedio_final'],
                    fn (array $a, array $b): int => $a['postulacion']->fecha_postulacion <=> $b['postulacion']->fecha_postulacion,
                ]);

            foreach ($aprobados as $item) {
                $postulacion = $item['postulacion'];
                $carreraAdmitida = $this->availableCareer($postulacion, $cupos, $ocupados);

                if ($carreraAdmitida) {
                    $ocupados[$carreraAdmitida]++;
                    $postulacion->update([
                        'estado_admision' => 'ADMITIDO',
                        'id_carrera_admitida' => $carreraAdmitida,
                    ]);

                    continue;
                }

                $postulacion->update([
                    'estado_admision' => 'NO_ADMITIDO',
                    'id_carrera_admitida' => null,
                ]);
            }

            $resumen = $this->resumen($idGestion);

            return [
                'procesados' => $postulaciones->count(),
                'admitidos' => $resumen['admitidos'],
                'no_admitidos' => $resumen['no_admitidos'],
                'pendientes' => $resumen['pendientes'],
            ];
        });
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
            'estado_academico' => $count < 12 || $promedio === null
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
        $user = $postulacion->postulante?->usuario;
        $result = $this->calculateFinalResult($postulacion->id_postulacion);

        return [
            'id_postulacion' => $postulacion->id_postulacion,
            'id_postulante' => $postulacion->id_postulante,
            'ci' => $user?->ci,
            'nombre_completo' => $user?->name,
            'documentacion_completa' => (bool) $postulacion->postulante?->documentacion_completa,
            'carrera_opcion1' => $postulacion->carreraOpcion1?->nombre,
            'carrera_opcion2' => $postulacion->carreraOpcion2?->nombre,
            'carrera_admitida' => $postulacion->carreraAdmitida?->nombre,
            'promedio_final' => $result['promedio_final'],
            'estado_academico' => $result['estado_academico'],
            'estado_admision' => $postulacion->estado_admision,
            'grupo' => $postulacion->grupo?->nombre,
        ];
    }

    private function availableCareer(Postulacion $postulacion, array $cupos, array $ocupados): ?int
    {
        foreach ([$postulacion->id_carrera_opcion1, $postulacion->id_carrera_opcion2] as $idCarrera) {
            if (! $idCarrera) {
                continue;
            }

            $max = $cupos[$idCarrera] ?? 0;
            $current = $ocupados[$idCarrera] ?? 0;

            if ($max > $current) {
                return (int) $idCarrera;
            }
        }

        return null;
    }

    private function admitidosPorCarrera(int $idGestion): array
    {
        return Postulacion::query()
            ->where('id_gestion', $idGestion)
            ->where('estado_admision', 'ADMITIDO')
            ->whereNotNull('id_carrera_admitida')
            ->select('id_carrera_admitida', DB::raw('count(*) as total'))
            ->groupBy('id_carrera_admitida')
            ->pluck('total', 'id_carrera_admitida')
            ->map(fn ($value) => (int) $value)
            ->all();
    }

    private function admitidosActuales(int $idGestion, int $idCarrera): int
    {
        return Postulacion::query()
            ->where('id_gestion', $idGestion)
            ->where('estado_admision', 'ADMITIDO')
            ->where('id_carrera_admitida', $idCarrera)
            ->count();
    }
}
