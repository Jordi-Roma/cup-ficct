<?php

namespace App\Modules\AccesoSeguridad\Services;

use App\Modules\AccesoSeguridad\Models\Rol;
use App\Modules\AccesoSeguridad\Models\User;
use App\Modules\GestionAcademica\Models\Docente;
use App\Modules\GestionAcademica\Models\DocenteHabilitacionMateria;
use App\Modules\GestionAcademica\Services\DocenteService;
use App\Modules\RegistroPostulantes\Models\Postulacion;
use App\Modules\RegistroPostulantes\Models\Postulante;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsuarioService
{
    public function __construct(private readonly DocenteService $docenteService)
    {
    }

    public function list(): Collection
    {
        return User::query()
            ->with(['roles' => fn ($query) => $query->orderBy('nombre')])
            ->orderBy('apellido')
            ->orderBy('nombre')
            ->get()
            ->map(fn (User $user) => $this->serialize($user));
    }

    public function createByType(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $user = User::create([
                'ci' => $data['ci'],
                'nombre' => $data['nombre'],
                'apellido' => $data['apellido'],
                'username' => $data['username'],
                'correo' => $data['correo'],
                'password_hash' => Hash::make($data['password']),
                'telefono' => $data['telefono'] ?? null,
                'sexo' => $data['sexo'],
                'estado_acceso' => $data['estado_acceso'] ?? 'HABILITADO',
                'activo' => $data['activo'] ?? true,
            ]);

            match ($data['tipo_usuario']) {
                'POSTULANTE' => $this->createPostulanteProfile($user, $data),
                'DOCENTE' => $this->createDocenteProfile($user, $data),
                'COORDINADOR_ACADEMICO' => $this->assignRoleByName($user, $this->coordinatorRoleName()),
                'ADMINISTRADOR' => $this->assignRoleByName($user, 'ADMINISTRADOR'),
            };

            return $user->load('roles');
        });
    }

    public function updateAccess(User $user, array $data): User
    {
        $user->update([
            'estado_acceso' => $data['estado_acceso'],
            'activo' => $data['activo'],
        ]);

        return $user;
    }

    public function syncRoles(User $user, array $roleIds): void
    {
        $user->roles()->syncWithPivotValues($roleIds, [
            'activo' => true,
            'fecha_asignacion' => now(),
        ]);
    }

    public function serialize(User $user): array
    {
        return [
            'id_usuario' => $user->id_usuario,
            'ci' => $user->ci,
            'nombre' => $user->nombre,
            'apellido' => $user->apellido,
            'name' => $user->name,
            'username' => $user->username,
            'correo' => $user->correo,
            'telefono' => $user->telefono,
            'sexo' => $user->sexo,
            'estado_acceso' => $user->estado_acceso,
            'activo' => $user->activo,
            'roles' => $user->relationLoaded('roles')
                ? $user->roles->map(fn ($rol) => [
                    'id_rol' => $rol->id_rol,
                    'nombre' => $rol->nombre,
                    'descripcion' => $rol->descripcion,
                    'activo' => $rol->activo,
                ])->values()
                : [],
        ];
    }

    private function createPostulanteProfile(User $user, array $data): void
    {
        $postulante = Postulante::create([
            'id_usuario' => $user->id_usuario,
            'fecha_nacimiento' => $data['fecha_nacimiento'],
            'direccion' => $data['direccion'] ?? null,
            'colegio_procedencia' => $data['colegio_procedencia'] ?? null,
            'ciudad' => $data['ciudad'] ?? null,
            'documentacion_completa' => true,
            'presento_titulo_bachiller' => true,
            'presento_fotocopia_carnet' => true,
            'documentacion_validada' => true,
            'fecha_validacion_documentos' => now(),
            'validado_por' => auth()->id(),
            'creado_por_admin' => true,
            'requiere_pago' => false,
        ]);

        Postulacion::create([
            'id_postulante' => $postulante->id_postulante,
            'id_gestion' => $data['id_gestion'],
            'id_carrera_opcion1' => $data['id_carrera_opcion1'],
            'id_carrera_opcion2' => $data['id_carrera_opcion2'] ?? null,
            'estado_admision' => 'PENDIENTE',
            'estado_proceso' => 'HABILITADO_CUP',
            'turno_preferido' => $data['turno_preferido'],
        ]);

        $this->assignRoleByName($user, 'POSTULANTE');
    }

    private function createDocenteProfile(User $user, array $data): void
    {
        $habilitaciones = $this->normalizeHabilitaciones($data['habilitaciones'] ?? []);
        $docenteData = [
            ...$data,
            'habilitaciones' => $habilitaciones,
            'profesional_area' => count($habilitaciones[DocenteHabilitacionMateria::PROFESIONAL_AREA]) > 0,
            'maestria' => count($habilitaciones[DocenteHabilitacionMateria::MAESTRIA]) > 0,
            'diplomado_educacion_superior' => count($habilitaciones[DocenteHabilitacionMateria::DIPLOMADO]) > 0,
        ];
        $this->docenteService->validateContractRequirements($docenteData);

        $docente = Docente::create([
            'id_usuario' => $user->id_usuario,
            'profesional_area' => $docenteData['profesional_area'],
            'maestria' => $docenteData['maestria'],
            'diplomado_educacion_superior' => $docenteData['diplomado_educacion_superior'],
            'maestria_educacion_superior' => $data['maestria_educacion_superior'] ?? false,
            'contratado' => $data['contratado'] ?? false,
            'activo' => true,
        ]);

        $this->docenteService->syncHabilitaciones($docente, $habilitaciones);
        $this->assignRoleByName($user, 'DOCENTE');
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

    private function coordinatorRoleName(): string
    {
        return Rol::where('nombre', 'COORDINADOR_ACADEMICO')->exists()
            ? 'COORDINADOR_ACADEMICO'
            : 'ADMINISTRATIVO';
    }

    private function assignRoleByName(User $user, string $roleName): void
    {
        $role = Rol::where('nombre', $roleName)->first();

        if (! $role) {
            return;
        }

        $user->roles()->syncWithoutDetaching([
            $role->id_rol => [
                'activo' => true,
                'fecha_asignacion' => now(),
            ],
        ]);
    }
}
