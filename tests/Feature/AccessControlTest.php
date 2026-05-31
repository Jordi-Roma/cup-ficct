<?php

namespace Tests\Feature;

use App\Modules\Autenticacion\Models\Permiso;
use App\Modules\Autenticacion\Models\Rol;
use App\Modules\Autenticacion\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_access_control_seeder_creates_base_roles_and_permissions(): void
    {
        $this->seed(AccessControlSeeder::class);

        $this->assertDatabaseHas('rol', ['nombre' => 'ADMINISTRADOR']);
        $this->assertDatabaseHas('rol', ['nombre' => 'ADMINISTRATIVO']);
        $this->assertDatabaseHas('rol', ['nombre' => 'DOCENTE']);
        $this->assertDatabaseHas('rol', ['nombre' => 'POSTULANTE']);
        $this->assertDatabaseHas('permiso', ['nombre' => 'roles:read']);
        $this->assertDatabaseHas('permiso', ['nombre' => 'usuarios:update']);
    }

    public function test_administrador_has_all_permissions(): void
    {
        $this->seed(AccessControlSeeder::class);

        $admin = Rol::where('nombre', 'ADMINISTRADOR')->firstOrFail();

        $this->assertSame(Permiso::count(), $admin->permisos()->count());
    }

    public function test_user_role_and_permission_helpers_work(): void
    {
        $this->seed(AccessControlSeeder::class);

        $user = User::factory()->create();
        $admin = Rol::where('nombre', 'ADMINISTRADOR')->firstOrFail();

        $user->roles()->attach($admin->id_rol, [
            'activo' => true,
            'fecha_asignacion' => now(),
        ]);

        $this->assertTrue($user->hasRole('ADMINISTRADOR'));
        $this->assertTrue($user->hasPermission('roles:read'));
        $this->assertTrue($user->isAdmin());
    }

    public function test_admin_routes_require_authentication(): void
    {
        $this->get('/admin/roles')->assertRedirect('/login');
    }

    public function test_authenticated_admin_can_load_roles_page(): void
    {
        $this->seed(AccessControlSeeder::class);

        $user = User::factory()->create();
        $admin = Rol::where('nombre', 'ADMINISTRADOR')->firstOrFail();

        $user->roles()->attach($admin->id_rol, [
            'activo' => true,
            'fecha_asignacion' => now(),
        ]);

        $this->actingAs($user)
            ->get('/admin/roles')
            ->assertOk();
    }
}
