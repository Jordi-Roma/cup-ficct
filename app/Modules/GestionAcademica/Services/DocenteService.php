<?php

namespace App\Modules\GestionAcademica\Services;

use App\Modules\AccesoSeguridad\Models\Rol;
use App\Modules\AccesoSeguridad\Models\User;
use App\Modules\GestionAcademica\Models\Docente;
use App\Modules\GestionAcademica\Models\DocenteHabilitacionMateria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class DocenteService
{
    public function list(array $filters): Collection
    {
        return Docente::query()
            ->with(['usuario', 'habilitaciones.materia'])
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->whereHas('usuario', function (Builder $userQuery) use ($search): void {
                    $userQuery
                        ->where('ci', 'ilike', "%{$search}%")
                        ->orWhere('nombre', 'ilike', "%{$search}%")
                        ->orWhere('apellido', 'ilike', "%{$search}%")
                        ->orWhere('username', 'ilike', "%{$search}%")
                        ->orWhere('correo', 'ilike', "%{$search}%");
                });
            })
            ->when(isset($filters['contratado']) && $filters['contratado'] !== '', fn (Builder $query) => $query->where('contratado', filter_var($filters['contratado'], FILTER_VALIDATE_BOOLEAN)))
            ->when(isset($filters['activo']) && $filters['activo'] !== '', fn (Builder $query) => $query->where('activo', filter_var($filters['activo'], FILTER_VALIDATE_BOOLEAN)))
            ->when(isset($filters['maestria_educacion_superior']) && $filters['maestria_educacion_superior'] !== '', fn (Builder $query) => $query->where('maestria_educacion_superior', filter_var($filters['maestria_educacion_superior'], FILTER_VALIDATE_BOOLEAN)))
            ->whereHas('usuario')
            ->get()
            ->sortBy(fn (Docente $docente) => $docente->usuario?->apellido.' '.$docente->usuario?->nombre)
            ->map(fn (Docente $docente) => $this->serialize($docente))
            ->values();
    }

    public function create(array $data): Docente
    {
        $data = $this->normalizeAcademicData($data);
        $this->validateContractRequirements($data);

        return DB::transaction(function () use ($data): Docente {
            $user = User::create([
                'ci' => $data['ci'],
                'nombre' => $data['nombre'],
                'apellido' => $data['apellido'],
                'username' => $data['username'],
                'correo' => $data['correo'],
                'password_hash' => Hash::make($data['password']),
                'telefono' => $data['telefono'] ?? null,
                'sexo' => $data['sexo'],
                'estado_acceso' => 'HABILITADO',
                'activo' => true,
            ]);

            $docente = Docente::create([
                'id_usuario' => $user->id_usuario,
                'profesional_area' => $data['profesional_area'],
                'maestria' => $data['maestria'],
                'diplomado_educacion_superior' => $data['diplomado_educacion_superior'],
                'maestria_educacion_superior' => $data['maestria_educacion_superior'],
                'contratado' => $data['contratado'],
                'activo' => true,
            ]);

            $this->syncHabilitaciones($docente, $data['habilitaciones']);
            $this->assignDocenteRole($user);

            return $docente->load(['usuario', 'habilitaciones.materia']);
        });
    }

    public function update(Docente $docente, array $data): Docente
    {
        $data = $this->normalizeAcademicData($data);
        $this->validateContractRequirements($data);

        return DB::transaction(function () use ($docente, $data): Docente {
            $userData = [
                'ci' => $data['ci'],
                'nombre' => $data['nombre'],
                'apellido' => $data['apellido'],
                'username' => $data['username'],
                'correo' => $data['correo'],
                'telefono' => $data['telefono'] ?? null,
                'sexo' => $data['sexo'],
                'estado_acceso' => $data['estado_acceso'],
                'activo' => $data['usuario_activo'],
            ];

            if (! empty($data['password'])) {
                $userData['password_hash'] = Hash::make($data['password']);
            }

            $docente->usuario()->update($userData);
            $docente->update([
                'profesional_area' => $data['profesional_area'],
                'maestria' => $data['maestria'],
                'diplomado_educacion_superior' => $data['diplomado_educacion_superior'],
                'maestria_educacion_superior' => $data['maestria_educacion_superior'],
                'contratado' => $data['contratado'],
                'activo' => $data['activo'],
            ]);
            $this->syncHabilitaciones($docente, $data['habilitaciones']);

            return $docente->load(['usuario', 'habilitaciones.materia']);
        });
    }

    public function toggleActive(Docente $docente): Docente
    {
        if ($docente->activo && $this->hasActiveAcademicAssignments($docente)) {
            throw ValidationException::withMessages([
                'docente' => 'No se puede desactivar un docente con asignaciones academicas activas.',
            ]);
        }

        $docente->update(['activo' => ! $docente->activo]);

        return $docente;
    }

    public function serialize(Docente $docente): array
    {
        $docente->loadMissing(['usuario', 'habilitaciones.materia']);
        $user = $docente->usuario;

        return [
            'id_docente' => $docente->id_docente,
            'id_usuario' => $docente->id_usuario,
            'ci' => $user?->ci,
            'nombre' => $user?->nombre,
            'apellido' => $user?->apellido,
            'nombre_completo' => $user?->name,
            'username' => $user?->username,
            'correo' => $user?->correo,
            'telefono' => $user?->telefono,
            'sexo' => $user?->sexo,
            'estado_acceso' => $user?->estado_acceso,
            'usuario_activo' => $user?->activo ?? false,
            'profesional_area' => $docente->profesional_area,
            'maestria' => $docente->maestria,
            'diplomado_educacion_superior' => $docente->diplomado_educacion_superior,
            'maestria_educacion_superior' => $docente->maestria_educacion_superior,
            'contratado' => $docente->contratado,
            'activo' => $docente->activo,
            'habilitaciones' => $this->serializeHabilitaciones($docente),
            'materias_habilitadas' => $this->serializeMateriasHabilitadas($docente),
        ];
    }

    public function validateContractRequirements(array $data): void
    {
        if (! ($data['contratado'] ?? false)) {
            return;
        }

        if (! ($data['maestria_educacion_superior'] ?? false)) {
            throw ValidationException::withMessages([
                'maestria_educacion_superior' => 'El docente debe tener maestria en educacion superior para ser contratado.',
            ]);
        }

        if ($this->countHabilitaciones($data['habilitaciones'] ?? []) === 0) {
            throw ValidationException::withMessages([
                'habilitaciones' => 'El docente debe estar habilitado en al menos una materia para ser contratado.',
            ]);
        }
    }

    public function syncHabilitaciones(Docente $docente, array $habilitaciones): void
    {
        $normalized = $this->normalizeHabilitaciones($habilitaciones);
        $docente->habilitaciones()->update(['activo' => false]);

        foreach ($normalized as $tipo => $materiaIds) {
            foreach ($materiaIds as $materiaId) {
                DocenteHabilitacionMateria::updateOrCreate(
                    [
                        'id_docente' => $docente->id_docente,
                        'id_materia' => $materiaId,
                        'tipo_habilitacion' => $tipo,
                    ],
                    ['activo' => true],
                );
            }
        }

        $docente->update([
            'profesional_area' => count($normalized[DocenteHabilitacionMateria::PROFESIONAL_AREA]) > 0,
            'diplomado_educacion_superior' => count($normalized[DocenteHabilitacionMateria::DIPLOMADO]) > 0,
            'maestria' => count($normalized[DocenteHabilitacionMateria::MAESTRIA]) > 0,
        ]);
    }

    private function normalizeAcademicData(array $data): array
    {
        $habilitaciones = $this->normalizeHabilitaciones($data['habilitaciones'] ?? []);

        $data['habilitaciones'] = $habilitaciones;
        $data['maestria_educacion_superior'] = filter_var($data['maestria_educacion_superior'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $data['contratado'] = filter_var($data['contratado'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $data['profesional_area'] = count($habilitaciones[DocenteHabilitacionMateria::PROFESIONAL_AREA]) > 0;
        $data['diplomado_educacion_superior'] = count($habilitaciones[DocenteHabilitacionMateria::DIPLOMADO]) > 0;
        $data['maestria'] = count($habilitaciones[DocenteHabilitacionMateria::MAESTRIA]) > 0;

        return $data;
    }

    private function normalizeHabilitaciones(array $habilitaciones): array
    {
        $normalized = [];

        foreach (DocenteHabilitacionMateria::TIPOS as $tipo) {
            $normalized[$tipo] = collect($habilitaciones[$tipo] ?? [])
                ->filter(fn ($id) => $id !== null && $id !== '')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();
        }

        return $normalized;
    }

    private function countHabilitaciones(array $habilitaciones): int
    {
        return collect($this->normalizeHabilitaciones($habilitaciones))->flatten()->unique()->count();
    }

    private function serializeHabilitaciones(Docente $docente): array
    {
        $result = [];

        foreach (DocenteHabilitacionMateria::TIPOS as $tipo) {
            $result[$tipo] = $docente->habilitaciones
                ->where('tipo_habilitacion', $tipo)
                ->where('activo', true)
                ->map(fn (DocenteHabilitacionMateria $habilitacion) => [
                    'id_materia' => $habilitacion->id_materia,
                    'nombre' => $habilitacion->materia?->nombre,
                ])
                ->values()
                ->all();
        }

        return $result;
    }

    private function serializeMateriasHabilitadas(Docente $docente): array
    {
        return collect($this->serializeHabilitaciones($docente))
            ->flatten(1)
            ->unique('id_materia')
            ->values()
            ->all();
    }

    private function assignDocenteRole(User $user): void
    {
        $role = Rol::where('nombre', 'DOCENTE')->first();

        if ($role) {
            $user->roles()->syncWithoutDetaching([
                $role->id_rol => [
                    'activo' => true,
                    'fecha_asignacion' => now(),
                ],
            ]);
        }
    }

    private function hasActiveAcademicAssignments(Docente $docente): bool
    {
        return DB::table('asignacion_academica')
            ->where('id_docente', $docente->id_docente)
            ->where('activo', true)
            ->exists();
    }
}
