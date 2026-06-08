<?php

namespace Tests\Feature\GestionAcademica;

use App\Modules\AccesoSeguridad\Models\Permiso;
use App\Modules\AccesoSeguridad\Models\Rol;
use App\Modules\AccesoSeguridad\Models\User;
use App\Modules\GestionAcademica\Models\GestionAcademica;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class GestionAcademicaTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/academico/gestiones')->assertRedirect('/login');
    }

    public function test_user_without_gestiones_read_receives_forbidden(): void
    {
        $this->seed(AccessControlSeeder::class);

        $this->actingAs(User::factory()->create())
            ->get('/academico/gestiones')
            ->assertForbidden();
    }

    public function test_user_with_gestiones_read_can_list_gestiones(): void
    {
        GestionAcademica::create([
            'nombre' => '1-2026',
            'fecha_inicio' => '2026-01-01',
            'fecha_fin' => '2026-06-30',
            'activo' => true,
        ]);

        $response = $this->actingAs($this->userWithPermissions(['gestiones:read']))
            ->get('/academico/gestiones');

        $response->assertOk();
        $this->assertSame('1-2026', $response->viewData('page')['props']['gestiones'][0]['nombre']);
    }

    public function test_user_with_gestiones_create_can_create_gestion(): void
    {
        $this->actingAs($this->userWithPermissions(['gestiones:create']))
            ->post('/academico/gestiones', [
                'nombre' => '2-2026',
                'fecha_inicio' => '2026-07-01',
                'fecha_fin' => '2026-12-31',
                'activo' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('gestion_academica', [
            'nombre' => '2-2026',
            'activo' => true,
        ]);
    }

    public function test_fecha_fin_must_be_after_fecha_inicio(): void
    {
        $this->actingAs($this->userWithPermissions(['gestiones:create']))
            ->post('/academico/gestiones', [
                'nombre' => 'Gestion invalida',
                'fecha_inicio' => '2026-07-01',
                'fecha_fin' => '2026-07-01',
                'activo' => true,
            ])
            ->assertSessionHasErrors('fecha_fin');
    }

    public function test_creating_active_gestion_deactivates_others(): void
    {
        $old = GestionAcademica::create([
            'nombre' => '1-2026',
            'fecha_inicio' => '2026-01-01',
            'fecha_fin' => '2026-06-30',
            'activo' => true,
        ]);

        $this->actingAs($this->userWithPermissions(['gestiones:create']))
            ->post('/academico/gestiones', [
                'nombre' => '2-2026',
                'fecha_inicio' => '2026-07-01',
                'fecha_fin' => '2026-12-31',
                'activo' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('gestion_academica', ['id_gestion' => $old->id_gestion, 'activo' => false]);
        $this->assertSame(1, GestionAcademica::where('activo', true)->count());
    }

    public function test_user_with_gestiones_update_can_edit_gestion(): void
    {
        $gestion = GestionAcademica::create([
            'nombre' => '1-2026',
            'fecha_inicio' => '2026-01-01',
            'fecha_fin' => '2026-06-30',
            'activo' => true,
        ]);

        $this->actingAs($this->userWithPermissions(['gestiones:update']))
            ->put("/academico/gestiones/{$gestion->id_gestion}", [
                'nombre' => '1-2026 actualizado',
                'fecha_inicio' => '2026-01-15',
                'fecha_fin' => '2026-06-30',
                'activo' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('gestion_academica', [
            'id_gestion' => $gestion->id_gestion,
            'nombre' => '1-2026 actualizado',
        ]);
    }

    public function test_editing_gestion_as_active_deactivates_others(): void
    {
        $old = GestionAcademica::create([
            'nombre' => '1-2026',
            'fecha_inicio' => '2026-01-01',
            'fecha_fin' => '2026-06-30',
            'activo' => true,
        ]);
        $new = GestionAcademica::create([
            'nombre' => '2-2026',
            'fecha_inicio' => '2026-07-01',
            'fecha_fin' => '2026-12-31',
            'activo' => false,
        ]);

        $this->actingAs($this->userWithPermissions(['gestiones:update']))
            ->put("/academico/gestiones/{$new->id_gestion}", [
                'nombre' => '2-2026',
                'fecha_inicio' => '2026-07-01',
                'fecha_fin' => '2026-12-31',
                'activo' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('gestion_academica', ['id_gestion' => $old->id_gestion, 'activo' => false]);
        $this->assertDatabaseHas('gestion_academica', ['id_gestion' => $new->id_gestion, 'activo' => true]);
    }

    public function test_user_with_gestiones_delete_can_activate_inactive_gestion(): void
    {
        $old = GestionAcademica::create([
            'nombre' => '1-2026',
            'fecha_inicio' => '2026-01-01',
            'fecha_fin' => '2026-06-30',
            'activo' => true,
        ]);
        $new = GestionAcademica::create([
            'nombre' => '2-2026',
            'fecha_inicio' => '2026-07-01',
            'fecha_fin' => '2026-12-31',
            'activo' => false,
        ]);

        $this->actingAs($this->userWithPermissions(['gestiones:delete']))
            ->patch("/academico/gestiones/{$new->id_gestion}/toggle")
            ->assertRedirect();

        $this->assertDatabaseHas('gestion_academica', ['id_gestion' => $old->id_gestion, 'activo' => false]);
        $this->assertDatabaseHas('gestion_academica', ['id_gestion' => $new->id_gestion, 'activo' => true]);
    }

    public function test_cannot_deactivate_only_active_gestion(): void
    {
        $gestion = GestionAcademica::create([
            'nombre' => '1-2026',
            'fecha_inicio' => '2026-01-01',
            'fecha_fin' => '2026-06-30',
            'activo' => true,
        ]);

        $this->actingAs($this->userWithPermissions(['gestiones:delete']))
            ->patch("/academico/gestiones/{$gestion->id_gestion}/toggle")
            ->assertSessionHasErrors('gestion');

        $this->assertDatabaseHas('gestion_academica', ['id_gestion' => $gestion->id_gestion, 'activo' => true]);
    }

    public function test_toggle_does_not_delete_gestion(): void
    {
        $active = GestionAcademica::create([
            'nombre' => '1-2026',
            'fecha_inicio' => '2026-01-01',
            'fecha_fin' => '2026-06-30',
            'activo' => true,
        ]);
        GestionAcademica::create([
            'nombre' => '2-2026',
            'fecha_inicio' => '2026-07-01',
            'fecha_fin' => '2026-12-31',
            'activo' => true,
        ]);

        $this->actingAs($this->userWithPermissions(['gestiones:delete']))
            ->patch("/academico/gestiones/{$active->id_gestion}/toggle")
            ->assertRedirect();

        $this->assertSame(2, GestionAcademica::count());
    }

    private function userWithPermissions(array $permissions): User
    {
        $this->seed(AccessControlSeeder::class);

        $user = User::factory()->create();
        $role = Rol::create([
            'nombre' => 'ROL_GESTIONES_'.Str::upper(Str::random(8)),
            'descripcion' => 'Rol de prueba para gestiones academicas',
            'activo' => true,
        ]);
        $permissionIds = Permiso::whereIn('nombre', $permissions)->pluck('id_permiso')->all();

        $role->permisos()->attach($permissionIds, ['activo' => true, 'fecha_asignacion' => now()]);
        $user->roles()->attach($role->id_rol, ['activo' => true, 'fecha_asignacion' => now()]);

        return $user;
    }
}
