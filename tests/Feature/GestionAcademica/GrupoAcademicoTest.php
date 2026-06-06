<?php

namespace Tests\Feature\GestionAcademica;

use App\Modules\AccesoSeguridad\Models\Permiso;
use App\Modules\AccesoSeguridad\Models\Rol;
use App\Modules\AccesoSeguridad\Models\User;
use App\Modules\GestionAcademica\Models\GestionAcademica;
use App\Modules\GestionAcademica\Models\GrupoAcademico;
use App\Modules\GestionAcademica\Services\GrupoAcademicoService;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class GrupoAcademicoTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/academico/grupos')->assertRedirect('/login');
    }

    public function test_user_without_grupos_read_receives_forbidden(): void
    {
        $this->seed(AccessControlSeeder::class);

        $gestion = $this->createGestion();
        $user = User::factory()->create();

        $this->actingAs($user)->get('/academico/grupos')->assertForbidden();

        $this->assertDatabaseHas('gestion_academica', [
            'id_gestion' => $gestion->id_gestion,
        ]);
    }

    public function test_user_with_grupos_read_can_list_groups(): void
    {
        $gestion = $this->createGestion();
        GrupoAcademico::create([
            'id_gestion' => $gestion->id_gestion,
            'nombre' => 'Grupo A',
            'turno' => 'MANANA',
            'capacidad_maxima' => 70,
            'activo' => true,
        ]);

        $user = $this->userWithPermissions(['grupos:read']);

        $this->actingAs($user)->get('/academico/grupos')->assertOk();
    }

    public function test_user_with_grupos_read_can_see_assigned_postulantes_in_group_props(): void
    {
        $gestion = $this->createGestion();
        $grupo = GrupoAcademico::create([
            'id_gestion' => $gestion->id_gestion,
            'nombre' => 'Grupo A',
            'turno' => 'MANANA',
            'capacidad_maxima' => 70,
            'activo' => true,
        ]);
        $this->createAssignedPostulante($gestion, $grupo);
        $user = $this->userWithPermissions(['grupos:read']);

        $response = $this->actingAs($user)->get('/academico/grupos');

        $response->assertOk();
        $grupos = $response->viewData('page')['props']['grupos'];

        $this->assertSame('Grupo A', $grupos[0]['nombre']);
        $this->assertCount(1, $grupos[0]['postulantes']);
        $this->assertSame('12345678', $grupos[0]['postulantes'][0]['ci']);
        $this->assertSame('Ana Rojas', $grupos[0]['postulantes'][0]['nombre_completo']);
        $this->assertSame('ana.rojas@example.com', $grupos[0]['postulantes'][0]['correo']);
    }

    public function test_calculates_required_groups_for_eligible_postulantes(): void
    {
        foreach ([70 => 1, 71 => 2, 140 => 2, 141 => 3] as $total => $expected) {
            $this->refreshDatabase();
            $gestion = $this->createGestion();
            $this->createEligiblePostulaciones($gestion, $total);

            $summary = app(GrupoAcademicoService::class)->calculateRequiredGroups();

            $this->assertSame($total, $summary['total_inscritos']);
            $this->assertSame($expected, $summary['grupos_necesarios']);
        }
    }

    public function test_user_with_grupos_create_can_create_manual_group(): void
    {
        $this->createGestion();
        $user = $this->userWithPermissions(['grupos:create']);

        $this->actingAs($user)
            ->post('/academico/grupos', [
                'nombre' => 'Grupo Manual',
                'turno' => 'MANANA',
                'capacidad_maxima' => 60,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('grupo_academico', [
            'nombre' => 'Grupo Manual',
            'turno' => 'MANANA',
            'capacidad_maxima' => 60,
            'activo' => true,
        ]);
    }

    public function test_group_capacity_cannot_exceed_seventy(): void
    {
        $this->createGestion();
        $user = $this->userWithPermissions(['grupos:create']);

        $this->actingAs($user)
            ->post('/academico/grupos', [
                'nombre' => 'Grupo Grande',
                'turno' => 'MANANA',
                'capacidad_maxima' => 71,
            ])
            ->assertSessionHasErrors('capacidad_maxima');
    }

    public function test_group_name_must_be_unique_in_same_gestion(): void
    {
        $gestion = $this->createGestion();
        GrupoAcademico::create([
            'id_gestion' => $gestion->id_gestion,
            'nombre' => 'Grupo A',
            'turno' => 'MANANA',
            'capacidad_maxima' => 70,
            'activo' => true,
        ]);
        $user = $this->userWithPermissions(['grupos:create']);

        $this->actingAs($user)
            ->post('/academico/grupos', [
                'nombre' => 'Grupo A',
                'turno' => 'MANANA',
                'capacidad_maxima' => 70,
            ])
            ->assertSessionHasErrors('nombre');
    }

    public function test_generate_missing_groups_without_duplicates(): void
    {
        $gestion = $this->createGestion();
        $this->createEligiblePostulaciones($gestion, 141);
        GrupoAcademico::create([
            'id_gestion' => $gestion->id_gestion,
            'nombre' => 'M001',
            'turno' => 'MANANA',
            'capacidad_maxima' => 70,
            'activo' => true,
        ]);
        $user = $this->userWithPermissions(['grupos:create']);

        $this->actingAs($user)
            ->post('/academico/grupos/generar')
            ->assertRedirect();

        $this->assertDatabaseHas('grupo_academico', ['nombre' => 'M001']);
        $this->assertDatabaseHas('grupo_academico', ['nombre' => 'M002']);
        $this->assertDatabaseHas('grupo_academico', ['nombre' => 'M003']);
        $this->assertSame(3, GrupoAcademico::where('id_gestion', $gestion->id_gestion)->count());
    }

    public function test_user_with_grupos_update_can_update_group(): void
    {
        $gestion = $this->createGestion();
        $grupo = GrupoAcademico::create([
            'id_gestion' => $gestion->id_gestion,
            'nombre' => 'Grupo A',
            'turno' => 'MANANA',
            'capacidad_maxima' => 70,
            'activo' => true,
        ]);
        $user = $this->userWithPermissions(['grupos:update']);

        $this->actingAs($user)
            ->put("/academico/grupos/{$grupo->id_grupo}", [
                'nombre' => 'Grupo Alfa',
                'turno' => 'MANANA',
                'capacidad_maxima' => 65,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('grupo_academico', [
            'id_grupo' => $grupo->id_grupo,
            'nombre' => 'Grupo Alfa',
            'capacidad_maxima' => 65,
        ]);
    }

    public function test_capacity_cannot_be_lower_than_assigned_postulantes(): void
    {
        $gestion = $this->createGestion();
        $grupo = GrupoAcademico::create([
            'id_gestion' => $gestion->id_gestion,
            'nombre' => 'Grupo A',
            'turno' => 'MANANA',
            'capacidad_maxima' => 70,
            'activo' => true,
        ]);
        $this->createEligiblePostulaciones($gestion, 2, $grupo);
        $user = $this->userWithPermissions(['grupos:update']);

        $this->actingAs($user)
            ->put("/academico/grupos/{$grupo->id_grupo}", [
                'nombre' => 'Grupo A',
                'turno' => 'MANANA',
                'capacidad_maxima' => 1,
            ])
            ->assertSessionHasErrors('capacidad_maxima');
    }

    public function test_assigns_eligible_postulantes_without_exceeding_capacity(): void
    {
        $gestion = $this->createGestion();
        $this->createEligiblePostulaciones($gestion, 3);
        $grupoA = GrupoAcademico::create([
            'id_gestion' => $gestion->id_gestion,
            'nombre' => 'Grupo A',
            'turno' => 'MANANA',
            'capacidad_maxima' => 2,
            'activo' => true,
        ]);
        $grupoB = GrupoAcademico::create([
            'id_gestion' => $gestion->id_gestion,
            'nombre' => 'Grupo B',
            'turno' => 'MANANA',
            'capacidad_maxima' => 2,
            'activo' => true,
        ]);
        $user = $this->userWithPermissions(['asignaciones:update']);

        $this->actingAs($user)
            ->post('/academico/asignaciones/asignar-postulantes')
            ->assertRedirect();

        $this->assertSame(3, DB::table('postulacion')->whereNotNull('id_grupo')->count());
        $this->assertLessThanOrEqual(2, DB::table('postulacion')->where('id_grupo', $grupoA->id_grupo)->count());
        $this->assertLessThanOrEqual(2, DB::table('postulacion')->where('id_grupo', $grupoB->id_grupo)->count());
    }

    public function test_user_with_grupos_delete_can_deactivate_group_without_physical_delete(): void
    {
        $gestion = $this->createGestion();
        $grupo = GrupoAcademico::create([
            'id_gestion' => $gestion->id_gestion,
            'nombre' => 'Grupo A',
            'turno' => 'MANANA',
            'capacidad_maxima' => 70,
            'activo' => true,
        ]);
        $user = $this->userWithPermissions(['grupos:delete']);

        $this->actingAs($user)
            ->patch("/academico/grupos/{$grupo->id_grupo}/toggle")
            ->assertRedirect();

        $this->assertDatabaseHas('grupo_academico', [
            'id_grupo' => $grupo->id_grupo,
            'activo' => false,
        ]);
        $this->assertSame(1, GrupoAcademico::whereKey($grupo->id_grupo)->count());
    }

    public function test_group_with_assigned_postulantes_cannot_be_deactivated(): void
    {
        $gestion = $this->createGestion();
        $grupo = GrupoAcademico::create([
            'id_gestion' => $gestion->id_gestion,
            'nombre' => 'Grupo A',
            'turno' => 'MANANA',
            'capacidad_maxima' => 70,
            'activo' => true,
        ]);
        $this->createEligiblePostulaciones($gestion, 1, $grupo);
        $user = $this->userWithPermissions(['grupos:delete']);

        $this->actingAs($user)
            ->patch("/academico/grupos/{$grupo->id_grupo}/toggle")
            ->assertSessionHasErrors('grupo');

        $this->assertDatabaseHas('grupo_academico', [
            'id_grupo' => $grupo->id_grupo,
            'activo' => true,
        ]);
    }

    private function userWithPermissions(array $permissions): User
    {
        $this->seed(AccessControlSeeder::class);

        $user = User::factory()->create();
        $role = Rol::create([
            'nombre' => 'ROL_GRUPOS_'.Str::upper(Str::random(8)),
            'descripcion' => 'Rol de prueba para grupos',
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

    private function createGestion(): GestionAcademica
    {
        GestionAcademica::query()->update(['activo' => false]);

        return GestionAcademica::create([
            'nombre' => 'CUP '.Str::upper(Str::random(6)),
            'fecha_inicio' => '2026-01-01',
            'fecha_fin' => '2026-12-31',
            'activo' => true,
        ]);
    }

    private function createEligiblePostulaciones(
        GestionAcademica $gestion,
        int $count,
        ?GrupoAcademico $grupo = null,
    ): void {
        $carreraId = DB::table('carrera')->insertGetId([
            'nombre' => 'Carrera '.Str::upper(Str::random(8)),
            'activo' => true,
        ], 'id_carrera');

        for ($index = 0; $index < $count; $index++) {
            $user = User::factory()->create();
            $postulanteId = DB::table('postulante')->insertGetId([
                'id_usuario' => $user->id_usuario,
                'fecha_nacimiento' => '2005-01-01',
                'direccion' => 'Dirección de prueba',
                'colegio_procedencia' => 'Colegio Test',
                'ciudad' => 'Santa Cruz',
                'documentacion_completa' => true,
            ], 'id_postulante');

            DB::table('postulacion')->insert([
                'id_postulante' => $postulanteId,
                'id_gestion' => $gestion->id_gestion,
                'id_carrera_opcion1' => $carreraId,
                'id_grupo' => $grupo?->id_grupo,
                'turno_preferido' => 'MANANA',
                'estado_admision' => 'PENDIENTE',
                'fecha_postulacion' => now()->addSeconds($index),
            ]);
        }
    }

    private function createAssignedPostulante(GestionAcademica $gestion, GrupoAcademico $grupo): void
    {
        $carreraId = DB::table('carrera')->insertGetId([
            'nombre' => 'Carrera '.Str::upper(Str::random(8)),
            'activo' => true,
        ], 'id_carrera');
        $user = User::factory()->create([
            'ci' => '12345678',
            'nombre' => 'Ana',
            'apellido' => 'Rojas',
            'username' => 'ana.rojas',
            'correo' => 'ana.rojas@example.com',
            'telefono' => '70000000',
        ]);
        $postulanteId = DB::table('postulante')->insertGetId([
            'id_usuario' => $user->id_usuario,
            'fecha_nacimiento' => '2005-01-01',
            'direccion' => 'Direccion de prueba',
            'colegio_procedencia' => 'Colegio Test',
            'ciudad' => 'Santa Cruz',
            'documentacion_completa' => true,
        ], 'id_postulante');

        DB::table('postulacion')->insert([
            'id_postulante' => $postulanteId,
            'id_gestion' => $gestion->id_gestion,
            'id_carrera_opcion1' => $carreraId,
            'id_grupo' => $grupo->id_grupo,
            'turno_preferido' => 'MANANA',
            'estado_admision' => 'PENDIENTE',
            'fecha_postulacion' => now(),
        ]);
    }
}
