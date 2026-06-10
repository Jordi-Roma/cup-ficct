<?php

namespace Tests\Feature;

use App\Modules\AccesoSeguridad\Models\Permiso;
use App\Modules\AccesoSeguridad\Models\Rol;
use App\Modules\AccesoSeguridad\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PermissionMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_from_admin_routes(): void
    {
        $this->get('/admin/roles')->assertRedirect('/login');
    }

    public function test_postulante_cannot_access_roles_admin(): void
    {
        $this->seed(AccessControlSeeder::class);

        $user = User::factory()->create();
        $postulante = Rol::where('nombre', 'POSTULANTE')->firstOrFail();

        $user->roles()->attach($postulante->id_rol, [
            'activo' => true,
            'fecha_asignacion' => now(),
        ]);

        $this->actingAs($user)->get('/admin/roles')->assertForbidden();
    }

    public function test_user_with_roles_read_can_access_roles_admin(): void
    {
        $user = $this->userWithPermissions(['roles:read']);

        $this->actingAs($user)->get('/admin/roles')->assertOk();
    }

    public function test_user_with_roles_read_cannot_access_permissions_admin(): void
    {
        $user = $this->userWithPermissions(['roles:read']);

        $this->actingAs($user)->get('/admin/permisos')->assertForbidden();
    }

    public function test_user_without_roles_update_cannot_update_role(): void
    {
        $user = $this->userWithPermissions(['roles:read']);
        $role = Rol::where('nombre', 'COORDINADOR_ACADEMICO')->firstOrFail();

        $this->actingAs($user)
            ->put("/admin/roles/{$role->id_rol}", [
                'nombre' => 'OPERADOR_CUP',
                'descripcion' => 'Rol operativo CUP',
                'activo' => true,
                'permisos' => [],
            ])
            ->assertForbidden();
    }

    public function test_user_with_roles_update_can_update_role(): void
    {
        $user = $this->userWithPermissions(['roles:update']);
        $role = Rol::where('nombre', 'COORDINADOR_ACADEMICO')->firstOrFail();

        $this->actingAs($user)
            ->put("/admin/roles/{$role->id_rol}", [
                'nombre' => 'OPERADOR_CUP',
                'descripcion' => 'Rol operativo CUP',
                'activo' => true,
                'permisos' => [],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('rol', [
            'id_rol' => $role->id_rol,
            'nombre' => 'OPERADOR_CUP',
        ]);
    }

    public function test_user_without_roles_delete_cannot_toggle_role(): void
    {
        $user = $this->userWithPermissions(['roles:read']);
        $role = Rol::where('nombre', 'DOCENTE')->firstOrFail();

        $this->actingAs($user)
            ->patch("/admin/roles/{$role->id_rol}/toggle")
            ->assertForbidden();
    }

    public function test_user_with_roles_delete_can_toggle_role(): void
    {
        $user = $this->userWithPermissions(['roles:delete']);
        $role = Rol::where('nombre', 'DOCENTE')->firstOrFail();

        $this->actingAs($user)
            ->patch("/admin/roles/{$role->id_rol}/toggle")
            ->assertRedirect();

        $this->assertDatabaseHas('rol', [
            'id_rol' => $role->id_rol,
            'activo' => false,
        ]);
    }

    public function test_administrador_can_access_admin_sections(): void
    {
        $this->seed(AccessControlSeeder::class);

        $user = User::factory()->create();
        $admin = Rol::where('nombre', 'ADMINISTRADOR')->firstOrFail();

        $user->roles()->attach($admin->id_rol, [
            'activo' => true,
            'fecha_asignacion' => now(),
        ]);

        $this->actingAs($user)->get('/admin/roles')->assertOk();
        $this->actingAs($user)->get('/admin/permisos')->assertOk();
        $this->actingAs($user)->get('/admin/usuarios')->assertOk();
    }

    private function userWithPermissions(array $permissions): User
    {
        $this->seed(AccessControlSeeder::class);

        $user = User::factory()->create();
        $role = Rol::create([
            'nombre' => 'ROL_PRUEBA_'.Str::upper(Str::random(8)),
            'descripcion' => 'Rol de prueba para permisos granulares',
            'activo' => true,
        ]);

        $permissionIds = Permiso::whereIn('nombre', $permissions)->pluck('id_permiso')->all();

        $role->permisos()->attach($permissionIds, [
            'activo' => true,
            'fecha_asignacion' => now(),
        ]);

        $user->roles()->attach($role->id_rol, [
            'activo' => true,
            'fecha_asignacion' => now(),
        ]);

        return $user;
    }
}
