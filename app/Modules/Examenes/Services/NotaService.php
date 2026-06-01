<?php

namespace App\Modules\Examenes\Services;

use App\Modules\Autenticacion\Models\User;
use App\Modules\Examenes\Models\Nota;
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

class NotaService
{
    public function list(array $filters, User $user): Collection
    {
        return $this->getPostulantesParaRegistro($filters, $user);
    }

    public function getFormOptions(User $user): array
    {
        $gestion = $this->activeGestion();
        $canManageAll = $this->canManageAllNotas($user);
        $docente = $canManageAll ? null : $this->docenteForUser($user);

        $asignaciones = AsignacionAcademica::query()
            ->with(['grupo', 'materia'])
            ->where('activo', true)
            ->whereHas('grupo', fn (Builder $query) => $query
                ->where('id_gestion', $gestion->id_gestion)
                ->where('activo', true))
            ->when($docente, fn (Builder $query) => $query->where('id_docente', $docente->id_docente))
            ->get();

        $grupoIds = $asignaciones->pluck('id_grupo')->unique()->values();
        $materiaIds = $asignaciones->pluck('id_materia')->unique()->values();

        return [
            'grupos' => GrupoAcademico::query()
                ->whereIn('id_grupo', $grupoIds)
                ->orderBy('nombre')
                ->get()
                ->map(fn (GrupoAcademico $grupo) => [
                    'id_grupo' => $grupo->id_grupo,
                    'nombre' => $grupo->nombre,
                    'capacidad_maxima' => $grupo->capacidad_maxima,
                ])
                ->values(),
            'materias' => MateriaCup::query()
                ->whereIn('id_materia', $materiaIds)
                ->where('activo', true)
                ->orderBy('nombre')
                ->get()
                ->map(fn (MateriaCup $materia) => [
                    'id_materia' => $materia->id_materia,
                    'nombre' => $materia->nombre,
                ])
                ->values(),
            'examenes' => [1, 2, 3],
        ];
    }

    public function getPostulantesParaRegistro(array $filters, User $user): Collection
    {
        $gestion = $this->activeGestion();
        $canManageAll = $this->canManageAllNotas($user);
        $docente = $canManageAll ? null : $this->docenteForUser($user);
        $idMateria = filled($filters['id_materia'] ?? null) ? (int) $filters['id_materia'] : null;
        $nroExamen = filled($filters['nro_examen'] ?? null) ? (int) $filters['nro_examen'] : null;

        $query = Postulacion::query()
            ->with(['postulante.usuario', 'grupo'])
            ->where('id_gestion', $gestion->id_gestion)
            ->whereNotNull('id_grupo')
            ->whereHas('postulante.usuario')
            ->when(filled($filters['id_grupo'] ?? null), fn (Builder $query) => $query->where('id_grupo', $filters['id_grupo']))
            ->when(filled($filters['search'] ?? null), function (Builder $query) use ($filters) {
                $search = $filters['search'];

                $query->whereHas('postulante.usuario', function (Builder $query) use ($search) {
                    $query->where('ci', 'ilike', "%{$search}%")
                        ->orWhere('nombre', 'ilike', "%{$search}%")
                        ->orWhere('apellido', 'ilike', "%{$search}%")
                        ->orWhere('correo', 'ilike', "%{$search}%");
                });
            })
            ->when($docente, function (Builder $query) use ($docente, $idMateria) {
                $query->whereHas('grupo', function (Builder $query) use ($docente, $idMateria) {
                    $query->whereHas('postulaciones');
                })->whereExists(function ($query) use ($docente, $idMateria) {
                    $query->selectRaw('1')
                        ->from('asignacion_academica')
                        ->whereColumn('asignacion_academica.id_grupo', 'postulacion.id_grupo')
                        ->where('asignacion_academica.id_docente', $docente->id_docente)
                        ->where('asignacion_academica.activo', true)
                        ->when($idMateria, fn ($query) => $query->where('asignacion_academica.id_materia', $idMateria));
                });
            })
            ->orderBy('fecha_postulacion');

        return $query->get()
            ->map(fn (Postulacion $postulacion) => $this->serializePostulacionForNotas($postulacion, $idMateria, $nroExamen))
            ->when(filled($filters['estado_final'] ?? null), fn (Collection $items) => $items
                ->filter(fn (array $item) => $item['estado_final'] === $filters['estado_final'])
                ->values())
            ->values();
    }

    public function store(array $data, User $user): Nota
    {
        $postulacion = Postulacion::findOrFail($data['id_postulacion']);
        $this->validateCanGrade($postulacion, (int) $data['id_materia'], $user);
        $this->ensureNotaDoesNotExist($postulacion->id_postulacion, (int) $data['id_materia'], (int) $data['nro_examen']);

        return Nota::create([
            'id_postulacion' => $postulacion->id_postulacion,
            'id_materia' => $data['id_materia'],
            'nro_examen' => $data['nro_examen'],
            'nota' => $data['nota'],
            'registrado_por' => $user->id_usuario,
        ]);
    }

    public function update(Nota $nota, array $data, User $user): Nota
    {
        $nota->load('postulacion');
        $this->validateCanEdit($nota, $user);

        $nota->update([
            'nota' => $data['nota'],
        ]);

        return $nota;
    }

    public function upsertBatch(array $notas, User $user): array
    {
        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($notas, $user, &$created, &$updated) {
            foreach ($notas as $notaData) {
                if (! empty($notaData['id_nota'])) {
                    $this->update(Nota::findOrFail($notaData['id_nota']), $notaData, $user);
                    $updated++;

                    continue;
                }

                $this->store($notaData, $user);
                $created++;
            }
        });

        return [
            'created' => $created,
            'updated' => $updated,
        ];
    }

    public function validateTeacherCanGrade(Postulacion $postulacion, int $idMateria, User $user): void
    {
        $docente = $this->docenteForUser($user);

        if (! $docente) {
            throw ValidationException::withMessages([
                'docente' => 'El usuario no tiene perfil docente para registrar notas.',
            ]);
        }

        if (! $this->hasActiveAssignment($postulacion, $idMateria, $docente)) {
            throw ValidationException::withMessages([
                'id_materia' => 'El docente no tiene una asignacion academica activa para ese grupo y materia.',
            ]);
        }
    }

    public function canManageAllNotas(User $user): bool
    {
        return $user->isAdmin()
            || $user->hasRole('ADMINISTRATIVO')
            || ($user->hasPermission('notas:update') && ! $user->hasRole('DOCENTE'));
    }

    public function calculatePromedioMateria(int $idPostulacion, int $idMateria): ?float
    {
        $average = Nota::query()
            ->where('id_postulacion', $idPostulacion)
            ->where('id_materia', $idMateria)
            ->avg('nota');

        return $average === null ? null : round((float) $average, 2);
    }

    public function calculateResultadoFinal(int $idPostulacion): array
    {
        $count = Nota::query()
            ->where('id_postulacion', $idPostulacion)
            ->count();
        $average = Nota::query()
            ->where('id_postulacion', $idPostulacion)
            ->avg('nota');

        if ($count < 12 || $average === null) {
            return [
                'promedio_final' => $average === null ? null : round((float) $average, 2),
                'estado_final' => 'PENDIENTE',
            ];
        }

        $promedio = round((float) $average, 2);

        return [
            'promedio_final' => $promedio,
            'estado_final' => $promedio >= 60 ? 'APROBADO' : 'REPROBADO',
        ];
    }

    public function serializeNota(Nota $nota): array
    {
        $nota->loadMissing(['materia', 'registradoPor']);

        return [
            'id_nota' => $nota->id_nota,
            'id_postulacion' => $nota->id_postulacion,
            'id_materia' => $nota->id_materia,
            'materia_nombre' => $nota->materia?->nombre,
            'nro_examen' => $nota->nro_examen,
            'nota' => (float) $nota->nota,
            'registrado_por' => $nota->registrado_por,
            'registrado_por_nombre' => $nota->registradoPor?->name,
        ];
    }

    public function resumen(Collection $postulantes): array
    {
        $notasRegistradas = $postulantes->filter(fn (array $item) => $item['nota'] !== null)->count();
        $promedios = $postulantes
            ->pluck('promedio_final')
            ->filter(fn ($promedio) => $promedio !== null);

        return [
            'total_postulantes' => $postulantes->count(),
            'notas_registradas' => $notasRegistradas,
            'pendientes' => $postulantes->count() - $notasRegistradas,
            'promedio_general' => $promedios->isEmpty() ? null : round($promedios->avg(), 2),
        ];
    }

    private function serializePostulacionForNotas(Postulacion $postulacion, ?int $idMateria, ?int $nroExamen): array
    {
        $user = $postulacion->postulante?->usuario;
        $nota = $idMateria && $nroExamen
            ? Nota::query()
                ->with('registradoPor')
                ->where('id_postulacion', $postulacion->id_postulacion)
                ->where('id_materia', $idMateria)
                ->where('nro_examen', $nroExamen)
                ->first()
            : null;
        $materia = $idMateria ? MateriaCup::find($idMateria) : null;
        $resultado = $this->calculateResultadoFinal($postulacion->id_postulacion);

        return [
            'id_postulacion' => $postulacion->id_postulacion,
            'id_postulante' => $postulacion->id_postulante,
            'ci' => $user?->ci,
            'nombre_completo' => $user?->name,
            'correo' => $user?->correo,
            'grupo' => [
                'id_grupo' => $postulacion->grupo?->id_grupo,
                'nombre' => $postulacion->grupo?->nombre,
            ],
            'materia' => $materia ? [
                'id_materia' => $materia->id_materia,
                'nombre' => $materia->nombre,
            ] : null,
            'nro_examen' => $nroExamen,
            'id_nota' => $nota?->id_nota,
            'nota' => $nota ? (float) $nota->nota : null,
            'promedio_materia' => $idMateria ? $this->calculatePromedioMateria($postulacion->id_postulacion, $idMateria) : null,
            'promedio_final' => $resultado['promedio_final'],
            'estado_final' => $resultado['estado_final'],
        ];
    }

    private function validateCanGrade(Postulacion $postulacion, int $idMateria, User $user): void
    {
        if ($this->canManageAllNotas($user)) {
            return;
        }

        $this->validateTeacherCanGrade($postulacion, $idMateria, $user);
    }

    private function validateCanEdit(Nota $nota, User $user): void
    {
        if ($this->canManageAllNotas($user)) {
            return;
        }

        if ((int) $nota->registrado_por !== (int) $user->id_usuario) {
            throw ValidationException::withMessages([
                'nota' => 'Solo el docente que registro la nota puede editarla.',
            ]);
        }

        $this->validateTeacherCanGrade($nota->postulacion, $nota->id_materia, $user);
    }

    private function ensureNotaDoesNotExist(int $idPostulacion, int $idMateria, int $nroExamen): void
    {
        if (Nota::query()
            ->where('id_postulacion', $idPostulacion)
            ->where('id_materia', $idMateria)
            ->where('nro_examen', $nroExamen)
            ->exists()) {
            throw ValidationException::withMessages([
                'nota' => 'Ya existe una nota registrada para ese postulante, materia y examen.',
            ]);
        }
    }

    private function hasActiveAssignment(Postulacion $postulacion, int $idMateria, Docente $docente): bool
    {
        return AsignacionAcademica::query()
            ->where('activo', true)
            ->where('id_docente', $docente->id_docente)
            ->where('id_grupo', $postulacion->id_grupo)
            ->where('id_materia', $idMateria)
            ->exists();
    }

    private function docenteForUser(User $user): ?Docente
    {
        return Docente::query()
            ->where('id_usuario', $user->id_usuario)
            ->where('activo', true)
            ->first();
    }

    private function activeGestion(): GestionAcademica
    {
        $gestion = GestionAcademica::where('activo', true)->first();

        if (! $gestion) {
            throw ValidationException::withMessages([
                'gestion' => 'No existe una gestion academica activa.',
            ]);
        }

        return $gestion;
    }
}
