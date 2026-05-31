<?php

namespace Tests\Feature\GestionAcademica;

use App\Modules\Autenticacion\Models\Permiso;
use App\Modules\Autenticacion\Models\Rol;
use App\Modules\Autenticacion\Models\User;
use App\Modules\GestionAcademica\Models\MateriaCup;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class MateriaCupTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/academico/materias')->assertRedirect('/login');
    }

    public function test_user_without_materias_read_receives_forbidden(): void
    {
        $this->seed(AccessControlSeeder::class);

        $user = User::factory()->create();

        $this->actingAs($user)->get('/academico/materias')->assertForbidden();
    }

    public function test_user_with_materias_read_can_list_materias(): void
    {
        $user = $this->userWithPermissions(['materias:read']);

        MateriaCup::create(['nombre' => 'Computación', 'activo' => true]);

        $this->actingAs($user)->get('/academico/materias')->assertOk();
    }

    public function test_user_with_materias_create_can_create_materia(): void
    {
        $user = $this->userWithPermissions(['materias:create']);

        $this->actingAs($user)
            ->post('/academico/materias', [
                'nombre' => 'Robótica',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('materia_cup', [
            'nombre' => 'Robótica',
            'activo' => true,
        ]);
    }

    public function test_materia_name_must_be_unique(): void
    {
        $user = $this->userWithPermissions(['materias:create']);

        MateriaCup::create(['nombre' => 'Matemáticas', 'activo' => true]);

        $this->actingAs($user)
            ->post('/academico/materias', [
                'nombre' => 'Matemáticas',
            ])
            ->assertSessionHasErrors('nombre');
    }

    public function test_user_with_materias_update_can_update_materia(): void
    {
        $user = $this->userWithPermissions(['materias:update']);
        $materia = MateriaCup::create(['nombre' => 'Inglés', 'activo' => true]);

        $this->actingAs($user)
            ->put("/academico/materias/{$materia->id_materia}", [
                'nombre' => 'Inglés Técnico',
                'activo' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('materia_cup', [
            'id_materia' => $materia->id_materia,
            'nombre' => 'Inglés Técnico',
        ]);
    }

    public function test_user_with_materias_delete_can_deactivate_materia_without_physical_delete(): void
    {
        $user = $this->userWithPermissions(['materias:delete']);
        $materia = MateriaCup::create(['nombre' => 'Física', 'activo' => true]);

        $this->actingAs($user)
            ->patch("/academico/materias/{$materia->id_materia}/toggle")
            ->assertRedirect();

        $this->assertDatabaseHas('materia_cup', [
            'id_materia' => $materia->id_materia,
            'activo' => false,
        ]);
        $this->assertSame(1, MateriaCup::whereKey($materia->id_materia)->count());
    }

    public function test_materia_with_nota_in_active_gestion_cannot_be_deactivated(): void
    {
        $user = $this->userWithPermissions(['materias:delete']);
        $materia = MateriaCup::create(['nombre' => 'Computación', 'activo' => true]);

        $this->createNotaForMateriaInActiveGestion($materia);

        $this->actingAs($user)
            ->patch("/academico/materias/{$materia->id_materia}/toggle")
            ->assertSessionHasErrors('materia');

        $this->assertDatabaseHas('materia_cup', [
            'id_materia' => $materia->id_materia,
            'activo' => true,
        ]);
    }

    private function userWithPermissions(array $permissions): User
    {
        $this->seed(AccessControlSeeder::class);

        $user = User::factory()->create();
        $role = Rol::create([
            'nombre' => 'ROL_MATERIAS_'.Str::upper(Str::random(8)),
            'descripcion' => 'Rol de prueba para materias',
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

    private function createNotaForMateriaInActiveGestion(MateriaCup $materia): void
    {
        $gestionId = DB::table('gestion_academica')->insertGetId([
            'nombre' => 'CUP TEST',
            'fecha_inicio' => '2026-01-01',
            'fecha_fin' => '2026-12-31',
            'activo' => true,
        ], 'id_gestion');
        $carreraId = DB::table('carrera')->insertGetId([
            'nombre' => 'Carrera Test',
            'activo' => true,
        ], 'id_carrera');
        $postulanteUser = User::factory()->create();
        $postulanteId = DB::table('postulante')->insertGetId([
            'id_usuario' => $postulanteUser->id_usuario,
            'fecha_nacimiento' => '2005-01-01',
            'direccion' => 'Dirección de prueba',
            'colegio_procedencia' => 'Colegio Test',
            'ciudad' => 'Santa Cruz',
            'documentacion_completa' => true,
        ], 'id_postulante');
        $postulacionId = DB::table('postulacion')->insertGetId([
            'id_postulante' => $postulanteId,
            'id_gestion' => $gestionId,
            'id_carrera_opcion1' => $carreraId,
            'estado_admision' => 'PENDIENTE',
            'fecha_postulacion' => now(),
        ], 'id_postulacion');

        DB::table('nota')->insert([
            'id_postulacion' => $postulacionId,
            'id_materia' => $materia->id_materia,
            'nro_examen' => 1,
            'nota' => 80,
            'fecha_registro' => now(),
        ]);
    }
}
