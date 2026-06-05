<?php

namespace App\Modules\Examenes\Services;

use App\Modules\AccesoSeguridad\Models\User;
use App\Modules\Examenes\Models\Nota;
use App\Modules\GestionAcademica\Models\GestionAcademica;
use App\Modules\GestionAcademica\Models\MateriaCup;
use App\Modules\RegistroPostulantes\Models\Postulacion;
use App\Modules\RegistroPostulantes\Models\Postulante;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class HistorialAcademicoService
{
    public function canViewAny(User $user): bool
    {
        return $user->isAdmin()
            || $user->hasRole('ADMINISTRATIVO')
            || $user->hasPermission('historial:read');
    }

    public function getOwnHistorial(User $user): array
    {
        $postulante = Postulante::query()
            ->with('usuario')
            ->where('id_usuario', $user->id_usuario)
            ->first();

        if (! $postulante) {
            return [
                'postulante' => null,
                'postulacion' => null,
                'materias' => [],
                'resumen' => $this->emptyFinalSummary(),
                'message' => 'No existe un perfil de postulante asociado a tu usuario.',
            ];
        }

        return $this->buildHistorial($postulante);
    }

    public function searchPostulantes(array $filters): Collection
    {
        $search = trim((string) ($filters['search'] ?? ''));

        if ($search === '') {
            return collect();
        }

        return Postulante::query()
            ->with(['usuario', 'postulaciones.carreraOpcion1'])
            ->whereHas('usuario', function (Builder $query) use ($search): void {
                $query->where('ci', 'ilike', "%{$search}%")
                    ->orWhere('nombre', 'ilike', "%{$search}%")
                    ->orWhere('apellido', 'ilike', "%{$search}%")
                    ->orWhere('correo', 'ilike', "%{$search}%");
            })
            ->limit(15)
            ->get()
            ->map(fn (Postulante $postulante): array => $this->serializeSearchResult($postulante))
            ->values();
    }

    public function getHistorialByPostulante(Postulante $postulante, User $viewer): array
    {
        if (! $this->canViewAny($viewer) && (int) $postulante->id_usuario !== (int) $viewer->id_usuario) {
            throw new HttpException(403, 'No puedes consultar el historial de otro postulante.');
        }

        return $this->buildHistorial($postulante);
    }

    public function buildHistorial(Postulante $postulante): array
    {
        $postulante->loadMissing('usuario');
        $postulacion = $this->currentPostulacion($postulante);

        if (! $postulacion) {
            return [
                'postulante' => $this->serializePostulante($postulante),
                'postulacion' => null,
                'materias' => [],
                'resumen' => $this->emptyFinalSummary(),
                'message' => 'El postulante todavía no tiene una postulación registrada.',
            ];
        }

        $materias = $this->calculateMateriaSummary($postulacion);

        return [
            'postulante' => $this->serializePostulante($postulante),
            'postulacion' => $this->serializePostulacion($postulacion),
            'materias' => $materias,
            'resumen' => $this->calculateFinalSummary($postulacion),
            'message' => null,
        ];
    }

    public function calculateMateriaSummary(Postulacion $postulacion): array
    {
        $notas = Nota::query()
            ->where('id_postulacion', $postulacion->id_postulacion)
            ->get()
            ->groupBy('id_materia');

        $materiaIdsWithNotas = $notas->keys()->map(fn ($id) => (int) $id)->all();

        return MateriaCup::query()
            ->where(function (Builder $query) use ($materiaIdsWithNotas): void {
                $query->where('activo', true)
                    ->when($materiaIdsWithNotas, fn (Builder $query) => $query->orWhereIn('id_materia', $materiaIdsWithNotas));
            })
            ->orderBy('nombre')
            ->get()
            ->map(function (MateriaCup $materia) use ($notas): array {
                $notasMateria = $notas->get($materia->id_materia, collect());
                $byExam = $notasMateria->keyBy('nro_examen');
                $values = $notasMateria->pluck('nota')->map(fn ($value) => (float) $value);
                $promedio = $values->isEmpty() ? null : round($values->avg(), 2);
                $completa = $notasMateria->count() === 3;

                return [
                    'id_materia' => $materia->id_materia,
                    'materia' => $materia->nombre,
                    'examen_1' => $this->notaValue($byExam->get(1)),
                    'examen_2' => $this->notaValue($byExam->get(2)),
                    'examen_3' => $this->notaValue($byExam->get(3)),
                    'promedio' => $promedio,
                    'estado_materia' => $this->estadoPorPromedio($promedio, $completa),
                ];
            })
            ->values()
            ->all();
    }

    public function calculateFinalSummary(Postulacion $postulacion): array
    {
        $notas = Nota::query()
            ->where('id_postulacion', $postulacion->id_postulacion)
            ->pluck('nota')
            ->map(fn ($value) => (float) $value);

        $count = $notas->count();
        $promedio = $notas->isEmpty() ? null : round($notas->avg(), 2);

        return [
            'total_notas_registradas' => $count,
            'total_notas_esperadas' => 12,
            'promedio_final' => $promedio,
            'estado_final' => $this->estadoPorPromedio($promedio, $count === 12),
        ];
    }

    private function currentPostulacion(Postulante $postulante): ?Postulacion
    {
        $postulaciones = $postulante->postulaciones()
            ->with(['gestion', 'carreraOpcion1', 'carreraOpcion2', 'carreraAdmitida', 'grupo'])
            ->get();
        $activeGestionId = GestionAcademica::query()
            ->where('activo', true)
            ->value('id_gestion');

        return $postulaciones->firstWhere('id_gestion', $activeGestionId)
            ?? $postulaciones->sortByDesc('fecha_postulacion')->first();
    }

    private function serializeSearchResult(Postulante $postulante): array
    {
        $postulacion = $this->currentPostulacion($postulante);
        $user = $postulante->usuario;

        return [
            'id_postulante' => $postulante->id_postulante,
            'ci' => $user?->ci,
            'nombre_completo' => $user?->name,
            'correo' => $user?->correo,
            'carrera' => $postulacion?->carreraOpcion1?->nombre,
            'estado_admision' => $postulacion?->estado_admision,
        ];
    }

    private function serializePostulante(Postulante $postulante): array
    {
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
            'ciudad' => $postulante->ciudad,
            'colegio_procedencia' => $postulante->colegio_procedencia,
            'documentacion_completa' => (bool) $postulante->documentacion_completa,
        ];
    }

    private function serializePostulacion(Postulacion $postulacion): array
    {
        return [
            'id_postulacion' => $postulacion->id_postulacion,
            'estado_admision' => $postulacion->estado_admision,
            'gestion' => $postulacion->gestion?->nombre,
            'grupo' => $postulacion->grupo ? [
                'id_grupo' => $postulacion->grupo->id_grupo,
                'nombre' => $postulacion->grupo->nombre,
            ] : null,
            'carrera_opcion1' => $postulacion->carreraOpcion1 ? [
                'id_carrera' => $postulacion->carreraOpcion1->id_carrera,
                'nombre' => $postulacion->carreraOpcion1->nombre,
            ] : null,
            'carrera_opcion2' => $postulacion->carreraOpcion2 ? [
                'id_carrera' => $postulacion->carreraOpcion2->id_carrera,
                'nombre' => $postulacion->carreraOpcion2->nombre,
            ] : null,
            'carrera_admitida' => $postulacion->carreraAdmitida ? [
                'id_carrera' => $postulacion->carreraAdmitida->id_carrera,
                'nombre' => $postulacion->carreraAdmitida->nombre,
            ] : null,
        ];
    }

    private function notaValue(?Nota $nota): ?float
    {
        return $nota ? (float) $nota->nota : null;
    }

    private function estadoPorPromedio(?float $promedio, bool $completo): string
    {
        if (! $completo || $promedio === null) {
            return 'PENDIENTE';
        }

        return $promedio >= 60 ? 'APROBADO' : 'REPROBADO';
    }

    private function emptyFinalSummary(): array
    {
        return [
            'total_notas_registradas' => 0,
            'total_notas_esperadas' => 12,
            'promedio_final' => null,
            'estado_final' => 'PENDIENTE',
        ];
    }
}
