<?php

namespace App\Modules\GestionAcademica\Services;

use App\Modules\GestionAcademica\Models\AsignacionAcademica;
use App\Modules\GestionAcademica\Models\Aula;
use App\Modules\GestionAcademica\Models\Docente;
use App\Modules\GestionAcademica\Models\GestionAcademica;
use App\Modules\GestionAcademica\Models\GrupoAcademico;
use App\Modules\GestionAcademica\Models\Horario;
use App\Modules\GestionAcademica\Models\MateriaCup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AsignacionAcademicaService
{
    public function list(array $filters = []): Collection
    {
        $gestion = $this->activeGestion();

        return AsignacionAcademica::query()
            ->with(['grupo', 'materia', 'docente.usuario', 'aula', 'horario'])
            ->whereHas('grupo', fn (Builder $query) => $query->where('id_gestion', $gestion->id_gestion))
            ->get()
            ->sortBy([
                fn (AsignacionAcademica $asignacion) => $asignacion->grupo?->nombre,
                fn (AsignacionAcademica $asignacion) => $asignacion->materia?->nombre,
            ])
            ->map(fn (AsignacionAcademica $asignacion) => $this->serialize($asignacion))
            ->values();
    }

    public function create(array $data): AsignacionAcademica
    {
        $this->validateBusinessRules($data);

        return AsignacionAcademica::create([
            'id_grupo' => $data['id_grupo'],
            'id_materia' => $data['id_materia'],
            'id_docente' => $data['id_docente'],
            'id_aula' => $data['id_aula'],
            'id_horario' => $data['id_horario'],
            'activo' => true,
        ]);
    }

    public function update(AsignacionAcademica $asignacion, array $data): AsignacionAcademica
    {
        $this->validateBusinessRules($data, $asignacion);

        $asignacion->update([
            'id_grupo' => $data['id_grupo'],
            'id_materia' => $data['id_materia'],
            'id_docente' => $data['id_docente'],
            'id_aula' => $data['id_aula'],
            'id_horario' => $data['id_horario'],
            'activo' => $data['activo'] ?? $asignacion->activo,
        ]);

        return $asignacion;
    }

    public function toggleActive(AsignacionAcademica $asignacion): AsignacionAcademica
    {
        if ($asignacion->activo && $this->hasNotasInActiveGestion($asignacion)) {
            throw ValidationException::withMessages([
                'asignacion' => 'No se puede desactivar una asignacion con notas registradas para el grupo y materia.',
            ]);
        }

        if (! $asignacion->activo) {
            $this->validateBusinessRules([
                'id_grupo' => $asignacion->id_grupo,
                'id_materia' => $asignacion->id_materia,
                'id_docente' => $asignacion->id_docente,
                'id_aula' => $asignacion->id_aula,
                'id_horario' => $asignacion->id_horario,
            ], $asignacion);
        }

        $asignacion->update(['activo' => ! $asignacion->activo]);

        return $asignacion;
    }

    public function validateBusinessRules(array $data, ?AsignacionAcademica $ignore = null): void
    {
        $grupo = GrupoAcademico::findOrFail($data['id_grupo']);
        $materia = MateriaCup::findOrFail($data['id_materia']);
        $docente = Docente::with('usuario')->findOrFail($data['id_docente']);
        $aula = Aula::findOrFail($data['id_aula']);
        Horario::findOrFail($data['id_horario']);

        if (! $grupo->activo) {
            throw ValidationException::withMessages(['id_grupo' => 'Solo se pueden asignar grupos activos.']);
        }

        if (! $materia->activo) {
            throw ValidationException::withMessages(['id_materia' => 'Solo se pueden asignar materias activas.']);
        }

        if (! $docente->activo || ! $docente->contratado) {
            throw ValidationException::withMessages(['id_docente' => 'Solo se pueden asignar docentes activos y contratados.']);
        }

        if ($aula->capacidad < $grupo->capacidad_maxima) {
            throw ValidationException::withMessages(['id_aula' => 'El aula no tiene capacidad suficiente para el grupo.']);
        }

        if ($this->existsConflict('id_grupo', $data['id_grupo'], 'id_materia', $data['id_materia'], $ignore)) {
            throw ValidationException::withMessages(['id_materia' => 'El grupo ya tiene asignada esa materia.']);
        }

        if ($this->existsConflict('id_docente', $data['id_docente'], 'id_horario', $data['id_horario'], $ignore)) {
            throw ValidationException::withMessages(['id_docente' => 'El docente ya tiene asignado ese horario.']);
        }

        if ($this->existsConflict('id_aula', $data['id_aula'], 'id_horario', $data['id_horario'], $ignore)) {
            throw ValidationException::withMessages(['id_aula' => 'El aula ya esta ocupada en ese horario.']);
        }

        if ($this->docenteGroupCount($docente, $ignore) >= 4 && ! $this->ignoreHasSameDocente($ignore, $docente)) {
            throw ValidationException::withMessages(['id_docente' => 'El docente no puede superar 4 grupos activos.']);
        }
    }

    public function getFormOptions(): array
    {
        $gestion = $this->activeGestion();

        return [
            'grupos' => GrupoAcademico::query()
                ->where('id_gestion', $gestion->id_gestion)
                ->where('activo', true)
                ->orderBy('nombre')
                ->get()
                ->map(fn (GrupoAcademico $grupo) => [
                    'id_grupo' => $grupo->id_grupo,
                    'nombre' => $grupo->nombre,
                    'capacidad_maxima' => $grupo->capacidad_maxima,
                ]),
            'materias' => MateriaCup::query()
                ->where('activo', true)
                ->orderBy('nombre')
                ->get()
                ->map(fn (MateriaCup $materia) => [
                    'id_materia' => $materia->id_materia,
                    'nombre' => $materia->nombre,
                ]),
            'docentes' => Docente::query()
                ->with('usuario')
                ->where('activo', true)
                ->where('contratado', true)
                ->get()
                ->sortBy(fn (Docente $docente) => $docente->usuario?->apellido.' '.$docente->usuario?->nombre)
                ->map(fn (Docente $docente) => [
                    'id_docente' => $docente->id_docente,
                    'nombre_completo' => $docente->usuario?->name,
                    'correo' => $docente->usuario?->correo,
                ])
                ->values(),
            'aulas' => Aula::query()
                ->orderBy('nombre')
                ->get()
                ->map(fn (Aula $aula) => [
                    'id_aula' => $aula->id_aula,
                    'nombre' => $aula->nombre,
                    'capacidad' => $aula->capacidad,
                ]),
            'horarios' => Horario::query()
                ->orderByRaw("array_position(ARRAY['LUNES','MARTES','MIERCOLES','JUEVES','VIERNES','SABADO'], dia)")
                ->orderBy('hora_inicio')
                ->get()
                ->map(fn (Horario $horario) => [
                    'id_horario' => $horario->id_horario,
                    'dia' => $horario->dia,
                    'hora_inicio' => substr((string) $horario->hora_inicio, 0, 5),
                    'hora_fin' => substr((string) $horario->hora_fin, 0, 5),
                ]),
        ];
    }

    public function serialize(AsignacionAcademica $asignacion): array
    {
        $docenteUser = $asignacion->docente?->usuario;

        return [
            'id_asignacion' => $asignacion->id_asignacion,
            'activo' => $asignacion->activo,
            'grupo' => [
                'id_grupo' => $asignacion->grupo?->id_grupo,
                'nombre' => $asignacion->grupo?->nombre,
                'capacidad_maxima' => $asignacion->grupo?->capacidad_maxima,
            ],
            'materia' => [
                'id_materia' => $asignacion->materia?->id_materia,
                'nombre' => $asignacion->materia?->nombre,
            ],
            'docente' => [
                'id_docente' => $asignacion->docente?->id_docente,
                'nombre_completo' => $docenteUser?->name,
                'correo' => $docenteUser?->correo,
            ],
            'aula' => [
                'id_aula' => $asignacion->aula?->id_aula,
                'nombre' => $asignacion->aula?->nombre,
                'capacidad' => $asignacion->aula?->capacidad,
            ],
            'horario' => [
                'id_horario' => $asignacion->horario?->id_horario,
                'dia' => $asignacion->horario?->dia,
                'hora_inicio' => substr((string) $asignacion->horario?->hora_inicio, 0, 5),
                'hora_fin' => substr((string) $asignacion->horario?->hora_fin, 0, 5),
            ],
        ];
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

    private function existsConflict(
        string $firstColumn,
        mixed $firstValue,
        string $secondColumn,
        mixed $secondValue,
        ?AsignacionAcademica $ignore = null,
    ): bool {
        return AsignacionAcademica::query()
            ->where('activo', true)
            ->where($firstColumn, $firstValue)
            ->where($secondColumn, $secondValue)
            ->when($ignore, fn (Builder $query) => $query->whereKeyNot($ignore->getKey()))
            ->exists();
    }

    private function docenteGroupCount(Docente $docente, ?AsignacionAcademica $ignore = null): int
    {
        return AsignacionAcademica::query()
            ->where('activo', true)
            ->where('id_docente', $docente->id_docente)
            ->when($ignore, fn (Builder $query) => $query->whereKeyNot($ignore->getKey()))
            ->distinct('id_grupo')
            ->count('id_grupo');
    }

    private function ignoreHasSameDocente(?AsignacionAcademica $ignore, Docente $docente): bool
    {
        return $ignore?->id_docente === $docente->id_docente;
    }

    private function hasNotasInActiveGestion(AsignacionAcademica $asignacion): bool
    {
        $gestion = $this->activeGestion();

        return DB::table('nota')
            ->join('postulacion', 'postulacion.id_postulacion', '=', 'nota.id_postulacion')
            ->where('nota.id_materia', $asignacion->id_materia)
            ->where('postulacion.id_grupo', $asignacion->id_grupo)
            ->where('postulacion.id_gestion', $gestion->id_gestion)
            ->exists();
    }
}
