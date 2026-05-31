<?php

namespace Database\Seeders;

use App\Modules\Autenticacion\Models\Permiso;
use App\Modules\Autenticacion\Models\Rol;
use App\Modules\Autenticacion\Models\User;
use Illuminate\Database\Seeder;

class AccessControlSeeder extends Seeder
{
    /**
     * Seed roles, permissions, and basic role assignments.
     */
    public function run(): void
    {
        $roles = [
            'ADMINISTRADOR' => 'Acceso completo al sistema',
            'ADMINISTRATIVO' => 'Gestión administrativa del proceso CUP',
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
            'Autenticacion' => [
                'usuarios:read' => ['accion' => 'LEER', 'descripcion' => 'Consultar usuarios'],
                'usuarios:update' => ['accion' => 'ACTUALIZAR', 'descripcion' => 'Actualizar acceso y roles de usuarios'],
                'roles:read' => ['accion' => 'LEER', 'descripcion' => 'Consultar roles'],
                'roles:create' => ['accion' => 'CREAR', 'descripcion' => 'Crear roles'],
                'roles:update' => ['accion' => 'ACTUALIZAR', 'descripcion' => 'Actualizar roles y permisos'],
                'roles:delete' => ['accion' => 'ELIMINAR', 'descripcion' => 'Desactivar roles'],
                'permisos:read' => ['accion' => 'LEER', 'descripcion' => 'Consultar permisos'],
                'permisos:create' => ['accion' => 'CREAR', 'descripcion' => 'Crear permisos'],
                'permisos:update' => ['accion' => 'ACTUALIZAR', 'descripcion' => 'Actualizar permisos'],
                'permisos:delete' => ['accion' => 'ELIMINAR', 'descripcion' => 'Desactivar permisos'],
            ],
            'RegistroPostulantes' => [
                'postulantes:read' => ['accion' => 'LEER', 'descripcion' => 'Consultar postulantes'],
                'postulantes:update' => ['accion' => 'ACTUALIZAR', 'descripcion' => 'Actualizar postulantes'],
                'pagos:read' => ['accion' => 'LEER', 'descripcion' => 'Consultar pagos de inscripción'],
                'pagos:update' => ['accion' => 'ACTUALIZAR', 'descripcion' => 'Actualizar pagos de inscripción'],
            ],
            'GestionAcademica' => [
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
            ],
            'Examenes' => [
                'notas:read' => ['accion' => 'LEER', 'descripcion' => 'Consultar notas'],
                'notas:create' => ['accion' => 'CREAR', 'descripcion' => 'Registrar notas'],
                'notas:update' => ['accion' => 'ACTUALIZAR', 'descripcion' => 'Actualizar notas'],
                'historial:read' => ['accion' => 'LEER', 'descripcion' => 'Consultar historial académico'],
            ],
            'ReportesMonitoreo' => [
                'dashboard:read' => ['accion' => 'LEER', 'descripcion' => 'Consultar dashboard administrativo'],
                'reportes:read' => ['accion' => 'LEER', 'descripcion' => 'Consultar reportes'],
                'reportes:export' => ['accion' => 'EJECUTAR', 'descripcion' => 'Exportar reportes'],
                'bitacora:read' => ['accion' => 'LEER', 'descripcion' => 'Consultar bitácora del sistema'],
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
    }

    private function syncRolePermissions(): void
    {
        $allPermissionIds = Permiso::query()->pluck('id_permiso')->all();

        Rol::where('nombre', 'ADMINISTRADOR')->first()?->permisos()->syncWithPivotValues($allPermissionIds, [
            'activo' => true,
            'fecha_asignacion' => now(),
        ]);

        $administrativo = [
            'dashboard:read',
            'postulantes:read',
            'postulantes:update',
            'pagos:read',
            'pagos:update',
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
            'reportes:read',
            'reportes:export',
        ];

        $docente = [
            'notas:read',
            'notas:create',
            'notas:update',
            'historial:read',
            'materias:read',
            'grupos:read',
            'asignaciones:read',
        ];

        $postulante = [
            'historial:read',
        ];

        $this->syncByNames('ADMINISTRATIVO', $administrativo);
        $this->syncByNames('DOCENTE', $docente);
        $this->syncByNames('POSTULANTE', $postulante);
    }

    private function syncByNames(string $roleName, array $permissionNames): void
    {
        $role = Rol::where('nombre', $roleName)->first();
        $permissionIds = Permiso::whereIn('nombre', $permissionNames)->pluck('id_permiso')->all();

        $role?->permisos()->syncWithPivotValues($permissionIds, [
            'activo' => true,
            'fecha_asignacion' => now(),
        ]);
    }

    private function assignAdminToTestUser(): void
    {
        $admin = Rol::where('nombre', 'ADMINISTRADOR')->first();
        $testUser = User::where('username', 'testuser')->first();

        if ($admin && $testUser) {
            $testUser->roles()->syncWithoutDetaching([
                $admin->id_rol => [
                    'activo' => true,
                    'fecha_asignacion' => now(),
                ],
            ]);
        }
    }
}
