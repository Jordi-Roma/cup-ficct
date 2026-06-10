<?php

namespace Database\Seeders;

use App\Modules\AccesoSeguridad\Models\Permiso;
use App\Modules\AccesoSeguridad\Models\Rol;
use App\Modules\AccesoSeguridad\Models\User;
use App\Modules\GestionAcademica\Models\GestionAcademica;
use Illuminate\Database\Seeder;

class AccessControlSeeder extends Seeder
{
    /**
     * Seed roles, permissions, and basic role assignments.
     */
    public function run(): void
    {
        $rolAdministrativo = Rol::where('nombre', 'ADMINISTRATIVO')->first();
        $rolCoordinador = Rol::where('nombre', 'COORDINADOR_ACADEMICO')->first();

        if ($rolAdministrativo && ! $rolCoordinador) {
            $rolAdministrativo->update([
                'nombre' => 'COORDINADOR_ACADEMICO',
                'descripcion' => 'Gestiona el proceso academico del CUP',
            ]);
        } elseif ($rolAdministrativo && $rolCoordinador) {
            $rolAdministrativo->update(['activo' => false]);
        }

        $roles = [
            'ADMINISTRADOR' => 'Acceso completo al sistema',
            'COORDINADOR_ACADEMICO' => 'Gestiona el proceso academico del CUP',
            'DOCENTE' => 'Gestión de notas y consulta académica',
            'POSTULANTE' => 'Acceso de postulante al proceso de admisión',
        ];

        foreach ($roles as $nombre => $descripcion) {
            Rol::updateOrCreate(
                ['nombre' => $nombre],
                ['descripcion' => $descripcion, 'activo' => true],
            );
        }

        $permissions = [
            'AccesoSeguridad' => [
                'usuarios:read' => ['accion' => 'LEER', 'descripcion' => 'Consultar usuarios'],
                'usuarios:create' => ['accion' => 'CREAR', 'descripcion' => 'Crear usuarios por tipo'],
                'usuarios:update' => ['accion' => 'ACTUALIZAR', 'descripcion' => 'Actualizar acceso y roles de usuarios'],
                'roles:read' => ['accion' => 'LEER', 'descripcion' => 'Consultar roles'],
                'roles:create' => ['accion' => 'CREAR', 'descripcion' => 'Crear roles'],
                'roles:update' => ['accion' => 'ACTUALIZAR', 'descripcion' => 'Actualizar roles y permisos'],
                'roles:delete' => ['accion' => 'ELIMINAR', 'descripcion' => 'Desactivar roles'],
                'permisos:read' => ['accion' => 'LEER', 'descripcion' => 'Consultar permisos'],
                'permisos:create' => ['accion' => 'CREAR', 'descripcion' => 'Crear permisos'],
                'permisos:update' => ['accion' => 'ACTUALIZAR', 'descripcion' => 'Actualizar permisos'],
                'permisos:delete' => ['accion' => 'ELIMINAR', 'descripcion' => 'Desactivar permisos'],
                'bitacora:read' => ['accion' => 'LEER', 'descripcion' => 'Consultar bitácora del sistema'],
            ],
            'RegistroPostulantes' => [
                'postulantes:read' => ['accion' => 'LEER', 'descripcion' => 'Consultar postulantes'],
                'postulantes:update' => ['accion' => 'ACTUALIZAR', 'descripcion' => 'Actualizar postulantes'],
                'pagos:read' => ['accion' => 'LEER', 'descripcion' => 'Consultar pagos de inscripción'],
                'pagos:update' => ['accion' => 'ACTUALIZAR', 'descripcion' => 'Actualizar pagos de inscripción'],
                'admision:read' => ['accion' => 'LEER', 'descripcion' => 'Consultar admisión por cupos'],
                'admision:update' => ['accion' => 'ACTUALIZAR', 'descripcion' => 'Configurar cupos por carrera'],
                'admision:process' => ['accion' => 'EJECUTAR', 'descripcion' => 'Procesar admisión por cupos'],
            ],
            'GestionAcademica' => [
                'gestiones:read' => ['accion' => 'LEER', 'descripcion' => 'Consultar gestiones academicas'],
                'gestiones:create' => ['accion' => 'CREAR', 'descripcion' => 'Crear gestiones academicas'],
                'gestiones:update' => ['accion' => 'ACTUALIZAR', 'descripcion' => 'Actualizar gestiones academicas'],
                'gestiones:delete' => ['accion' => 'ELIMINAR', 'descripcion' => 'Activar o desactivar gestiones academicas'],
                'materias:read' => ['accion' => 'LEER', 'descripcion' => 'Consultar materias CUP'],
                'materias:create' => ['accion' => 'CREAR', 'descripcion' => 'Crear materias CUP'],
                'materias:update' => ['accion' => 'ACTUALIZAR', 'descripcion' => 'Actualizar materias CUP'],
                'materias:delete' => ['accion' => 'ELIMINAR', 'descripcion' => 'Desactivar materias CUP'],
                'grupos:read' => ['accion' => 'LEER', 'descripcion' => 'Consultar grupos académicos'],
                'grupos:create' => ['accion' => 'CREAR', 'descripcion' => 'Crear grupos académicos'],
                'grupos:update' => ['accion' => 'ACTUALIZAR', 'descripcion' => 'Actualizar grupos académicos'],
                'grupos:delete' => ['accion' => 'ELIMINAR', 'descripcion' => 'Desactivar grupos académicos'],
                'docentes:read' => ['accion' => 'LEER', 'descripcion' => 'Consultar docentes'],
                'docentes:create' => ['accion' => 'CREAR', 'descripcion' => 'Crear docentes'],
                'docentes:update' => ['accion' => 'ACTUALIZAR', 'descripcion' => 'Actualizar docentes'],
                'docentes:delete' => ['accion' => 'ELIMINAR', 'descripcion' => 'Desactivar docentes'],
                'asignaciones:read' => ['accion' => 'LEER', 'descripcion' => 'Consultar asignaciones academicas'],
                'asignaciones:create' => ['accion' => 'CREAR', 'descripcion' => 'Crear asignaciones academicas'],
                'asignaciones:update' => ['accion' => 'ACTUALIZAR', 'descripcion' => 'Actualizar asignaciones academicas'],
                'asignaciones:delete' => ['accion' => 'ELIMINAR', 'descripcion' => 'Desactivar asignaciones academicas'],
                'aulas:read' => ['accion' => 'LEER', 'descripcion' => 'Consultar aulas'],
                'aulas:create' => ['accion' => 'CREAR', 'descripcion' => 'Crear aulas'],
                'aulas:update' => ['accion' => 'ACTUALIZAR', 'descripcion' => 'Actualizar aulas'],
                'aulas:delete' => ['accion' => 'ELIMINAR', 'descripcion' => 'Desactivar aulas'],
                'horarios:read' => ['accion' => 'LEER', 'descripcion' => 'Consultar horarios'],
                'horarios:create' => ['accion' => 'CREAR', 'descripcion' => 'Crear horarios'],
                'horarios:update' => ['accion' => 'ACTUALIZAR', 'descripcion' => 'Actualizar horarios'],
                'horarios:delete' => ['accion' => 'ELIMINAR', 'descripcion' => 'Desactivar horarios'],
            ],
            'Examenes' => [
                'mis-asignaciones:read' => ['accion' => 'LEER', 'descripcion' => 'Consultar mis asignaciones academicas'],
                'notas:read' => ['accion' => 'LEER', 'descripcion' => 'Consultar notas'],
                'notas:create' => ['accion' => 'CREAR', 'descripcion' => 'Registrar notas'],
                'notas:update' => ['accion' => 'ACTUALIZAR', 'descripcion' => 'Actualizar notas'],
                'notas:delete' => ['accion' => 'ELIMINAR', 'descripcion' => 'Anular notas si el flujo lo habilita'],
                'historial:read-own' => ['accion' => 'LEER', 'descripcion' => 'Consultar historial academico propio'],
                'historial:read' => ['accion' => 'LEER', 'descripcion' => 'Consultar historial académico'],
            ],
            'ReportesMonitoreo' => [
                'dashboard:read' => ['accion' => 'LEER', 'descripcion' => 'Consultar dashboard administrativo'],
                'reportes:read' => ['accion' => 'LEER', 'descripcion' => 'Consultar reportes'],
                'reportes:export' => ['accion' => 'EJECUTAR', 'descripcion' => 'Exportar reportes'],
            ],
        ];

        foreach ($permissions as $modulo => $modulePermissions) {
            foreach ($modulePermissions as $nombre => $data) {
                Permiso::updateOrCreate(
                    ['nombre' => $nombre],
                    [
                        'modulo' => $modulo,
                        'accion' => $data['accion'],
                        'descripcion' => $data['descripcion'],
                        'activo' => true,
                    ],
                );
            }
        }

        $this->syncRolePermissions();
        $this->assignAdminToTestUser();
        $this->ensureDefaultGestionAcademica();
    }

    private function syncRolePermissions(): void
    {
        $allPermissionIds = Permiso::query()->pluck('id_permiso');

        Rol::where('nombre', 'ADMINISTRADOR')->first()?->permisos()->sync($this->pivotRecords($allPermissionIds));

        $coordinadorAcademico = [
            'usuarios:read',
            'usuarios:create',
            'usuarios:update',
            'dashboard:read',
            'postulantes:read',
            'postulantes:update',
            'pagos:read',
            'pagos:update',
            'gestiones:read',
            'gestiones:create',
            'gestiones:update',
            'gestiones:delete',
            'materias:read',
            'materias:create',
            'materias:update',
            'materias:delete',
            'grupos:read',
            'grupos:create',
            'grupos:update',
            'grupos:delete',
            'docentes:read',
            'docentes:create',
            'docentes:update',
            'docentes:delete',
            'asignaciones:read',
            'asignaciones:create',
            'asignaciones:update',
            'asignaciones:delete',
            'aulas:read',
            'aulas:create',
            'aulas:update',
            'aulas:delete',
            'horarios:read',
            'horarios:create',
            'horarios:update',
            'horarios:delete',
                'admision:read',
            'admision:update',
            'admision:process',
            'notas:read',
            'notas:create',
            'notas:update',
            'historial:read-own',
            'historial:read',
            'reportes:read',
            'reportes:export',
        ];

        $docente = [
            'mis-asignaciones:read',
            'notas:read',
            'notas:create',
            'notas:update',
            'materias:read',
            'grupos:read',
            'asignaciones:read',
        ];

        $postulante = [
            'historial:read-own',
        ];

        $this->syncByNames('COORDINADOR_ACADEMICO', $coordinadorAcademico);
        $this->syncByNames('DOCENTE', $docente);
        $this->syncByNames('POSTULANTE', $postulante);
    }

    private function syncByNames(string $roleName, array $permissionNames): void
    {
        $role = Rol::where('nombre', $roleName)->first();
        $permissionIds = Permiso::whereIn('nombre', $permissionNames)->pluck('id_permiso');

        $role?->permisos()->sync($this->pivotRecords($permissionIds));
    }

    private function pivotRecords($ids): array
    {
        return collect($ids)->mapWithKeys(fn ($id) => [
            $id => [
                'activo' => true,
                'fecha_asignacion' => now(),
            ],
        ])->all();
    }

    private function assignAdminToTestUser(): void
    {
        $admin = Rol::where('nombre', 'ADMINISTRADOR')->first();
        $testUser = User::where('username', 'testuser')->first();

        if ($admin && $testUser) {
            $testUser->roles()->syncWithoutDetaching($this->pivotRecords([$admin->id_rol]));
        }
    }

    private function ensureDefaultGestionAcademica(): void
    {
        if (GestionAcademica::query()->exists()) {
            return;
        }

        GestionAcademica::create([
            'nombre' => '1-2026',
            'fecha_inicio' => '2026-01-01',
            'fecha_fin' => '2026-6-31',
            'activo' => true,
        ]);
    }
}
