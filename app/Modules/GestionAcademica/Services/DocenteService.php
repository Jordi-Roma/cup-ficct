<?php

namespace App\Modules\GestionAcademica\Services;

use App\Modules\Autenticacion\Models\Rol;
use App\Modules\Autenticacion\Models\User;
use App\Modules\GestionAcademica\Models\Docente;
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
            ->with('usuario')
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
            ->when(isset($filters['profesional_area']) && $filters['profesional_area'] !== '', fn (Builder $query) => $query->where('profesional_area', filter_var($filters['profesional_area'], FILTER_VALIDATE_BOOLEAN)))
            ->when(isset($filters['maestria']) && $filters['maestria'] !== '', fn (Builder $query) => $query->where('maestria', filter_var($filters['maestria'], FILTER_VALIDATE_BOOLEAN)))
            ->when(isset($filters['diplomado_educacion_superior']) && $filters['diplomado_educacion_superior'] !== '', fn (Builder $query) => $query->where('diplomado_educacion_superior', filter_var($filters['diplomado_educacion_superior'], FILTER_VALIDATE_BOOLEAN)))
            ->whereHas('usuario')
            ->get()
            ->sortBy(fn (Docente $docente) => $docente->usuario?->apellido.' '.$docente->usuario?->nombre)
            ->map(fn (Docente $docente) => $this->serialize($docente))
            ->values();
    }

    public function create(array $data): Docente
    {
        $data['contratado'] = $data['contratado'] ?? false;
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
                'contratado' => $data['contratado'],
                'activo' => true,
            ]);

            $this->assignDocenteRole($user);

            return $docente;
        });
    }

    public function update(Docente $docente, array $data): Docente
    {
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
                'contratado' => $data['contratado'],
                'activo' => $data['activo'],
            ]);

            return $docente;
        });
    }

    public function toggleActive(Docente $docente): Docente
    {
        if ($docente->activo && $this->hasActiveAcademicAssignments($docente)) {
            throw ValidationException::withMessages([
                'docente' => 'No se puede desactivar un docente con asignaciones académicas activas.',
            ]);
        }

        $docente->update(['activo' => ! $docente->activo]);

        return $docente;
    }

    public function serialize(Docente $docente): array
    {
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
            'contratado' => $docente->contratado,
            'activo' => $docente->activo,
        ];
    }

    public function validateContractRequirements(array $data): void
    {
        if (
            ($data['contratado'] ?? false)
            && (! $data['profesional_area'] || ! $data['maestria'] || ! $data['diplomado_educacion_superior'])
        ) {
            throw ValidationException::withMessages([
                'contratado' => 'El docente debe cumplir profesional en área, maestría y diplomado para ser contratado.',
            ]);
        }
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
