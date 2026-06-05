<?php

namespace Tests\Feature\RegistroPostulantes;

use App\Modules\AccesoSeguridad\Models\Permiso;
use App\Modules\AccesoSeguridad\Models\Rol;
use App\Modules\AccesoSeguridad\Models\User;
use App\Modules\Examenes\Models\Nota;
use App\Modules\GestionAcademica\Models\Carrera;
use App\Modules\GestionAcademica\Models\CupoCarrera;
use App\Modules\GestionAcademica\Models\GestionAcademica;
use App\Modules\GestionAcademica\Models\GrupoAcademico;
use App\Modules\GestionAcademica\Models\MateriaCup;
use App\Modules\RegistroPostulantes\Models\Postulante;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdmisionCupoTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/postulantes/admision-cupos')->assertRedirect('/login');
    }

    public function test_user_without_admision_read_receives_forbidden(): void
    {
        $this->seed(AccessControlSeeder::class);
        $user = User::factory()->create();

        $this->actingAs($user)->get('/postulantes/admision-cupos')->assertForbidden();
    }

    public function test_user_with_admision_read_can_load_page(): void
    {
        $this->createBaseContext();
        $user = $this->userWithPermissions(['admision:read']);

        $this->actingAs($user)->get('/postulantes/admision-cupos')->assertOk();
    }

    public function test_user_with_admision_update_can_configure_cupo(): void
    {
        $context = $this->createBaseContext();
        $user = $this->userWithPermissions(['admision:update']);

        $this->actingAs($user)
            ->post('/postulantes/admision-cupos/cupos', [
                'id_carrera' => $context['carrera1']->id_carrera,
                'id_gestion' => $context['gestion']->id_gestion,
                'cupo_maximo' => 5,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('cupo_carrera', [
            'id_carrera' => $context['carrera1']->id_carrera,
            'id_gestion' => $context['gestion']->id_gestion,
            'cupo_maximo' => 5,
        ]);
    }

    public function test_cupo_maximo_must_be_greater_than_zero(): void
    {
        $context = $this->createBaseContext();
        $user = $this->userWithPermissions(['admision:update']);

        $this->actingAs($user)
            ->post('/postulantes/admision-cupos/cupos', [
                'id_carrera' => $context['carrera1']->id_carrera,
                'id_gestion' => $context['gestion']->id_gestion,
                'cupo_maximo' => 0,
            ])
            ->assertSessionHasErrors('cupo_maximo');
    }

    public function test_cannot_reduce_cupo_below_current_admitted(): void
    {
        $context = $this->createBaseContext();
        CupoCarrera::create([
            'id_carrera' => $context['carrera1']->id_carrera,
            'id_gestion' => $context['gestion']->id_gestion,
            'cupo_maximo' => 2,
        ]);
        $postulacion = $this->createPostulacion($context, [
            'id_carrera_opcion1' => $context['carrera1']->id_carrera,
            'estado_admision' => 'ADMITIDO',
            'id_carrera_admitida' => $context['carrera1']->id_carrera,
        ]);
        $this->createPostulacion($context, [
            'id_carrera_opcion1' => $context['carrera1']->id_carrera,
            'estado_admision' => 'ADMITIDO',
            'id_carrera_admitida' => $context['carrera1']->id_carrera,
        ]);
        $this->assertDatabaseHas('postulacion', ['id_postulacion' => $postulacion]);
        $user = $this->userWithPermissions(['admision:update']);

        $this->actingAs($user)
            ->post('/postulantes/admision-cupos/cupos', [
                'id_carrera' => $context['carrera1']->id_carrera,
                'id_gestion' => $context['gestion']->id_gestion,
                'cupo_maximo' => 1,
            ])
            ->assertSessionHasErrors('cupo_maximo');
    }

    public function test_shows_cupo_availability_by_carrera(): void
    {
        $context = $this->createBaseContext();
        CupoCarrera::create([
            'id_carrera' => $context['carrera1']->id_carrera,
            'id_gestion' => $context['gestion']->id_gestion,
            'cupo_maximo' => 3,
        ]);
        $this->createPostulacion($context, [
            'id_carrera_opcion1' => $context['carrera1']->id_carrera,
            'estado_admision' => 'ADMITIDO',
            'id_carrera_admitida' => $context['carrera1']->id_carrera,
        ]);
        $user = $this->userWithPermissions(['admision:read']);

        $response = $this->actingAs($user)->get('/postulantes/admision-cupos');
        $cupos = collect($response->viewData('page')['props']['cupos']);
        $cupo = $cupos->firstWhere('id_carrera', $context['carrera1']->id_carrera);

        $this->assertSame(3, $cupo['cupo_maximo']);
        $this->assertSame(1, $cupo['admitidos']);
        $this->assertSame(2, $cupo['disponibles']);
    }

    public function test_process_admits_in_first_option_when_available(): void
    {
        $context = $this->contextWithCupos(1, 0);
        $postulacion = $this->createApprovedPostulacion($context, 90);
        $user = $this->userWithPermissions(['admision:process']);

        $this->actingAs($user)->post('/postulantes/admision-cupos/procesar', [
            'id_gestion' => $context['gestion']->id_gestion,
        ])->assertRedirect();

        $this->assertDatabaseHas('postulacion', [
            'id_postulacion' => $postulacion,
            'estado_admision' => 'ADMITIDO',
            'id_carrera_admitida' => $context['carrera1']->id_carrera,
        ]);
    }

    public function test_process_admits_in_second_option_when_first_is_full(): void
    {
        $context = $this->contextWithCupos(1, 1);
        $first = $this->createApprovedPostulacion($context, 95);
        $second = $this->createApprovedPostulacion($context, 90);
        $user = $this->userWithPermissions(['admision:process']);

        $this->actingAs($user)->post('/postulantes/admision-cupos/procesar', [
            'id_gestion' => $context['gestion']->id_gestion,
        ])->assertRedirect();

        $this->assertDatabaseHas('postulacion', [
            'id_postulacion' => $first,
            'id_carrera_admitida' => $context['carrera1']->id_carrera,
            'estado_admision' => 'ADMITIDO',
        ]);
        $this->assertDatabaseHas('postulacion', [
            'id_postulacion' => $second,
            'id_carrera_admitida' => $context['carrera2']->id_carrera,
            'estado_admision' => 'ADMITIDO',
        ]);
    }

    public function test_process_marks_not_admitted_when_no_cupo_available(): void
    {
        $context = $this->contextWithCupos(0, 0);
        $postulacion = $this->createApprovedPostulacion($context, 90);
        $user = $this->userWithPermissions(['admision:process']);

        $this->actingAs($user)->post('/postulantes/admision-cupos/procesar', [
            'id_gestion' => $context['gestion']->id_gestion,
        ])->assertRedirect();

        $this->assertDatabaseHas('postulacion', [
            'id_postulacion' => $postulacion,
            'estado_admision' => 'NO_ADMITIDO',
            'id_carrera_admitida' => null,
        ]);
    }

    public function test_process_marks_failed_average_as_not_admitted(): void
    {
        $context = $this->contextWithCupos(1, 1);
        $postulacion = $this->createApprovedPostulacion($context, 55);
        $user = $this->userWithPermissions(['admision:process']);

        $this->actingAs($user)->post('/postulantes/admision-cupos/procesar', [
            'id_gestion' => $context['gestion']->id_gestion,
        ])->assertRedirect();

        $this->assertDatabaseHas('postulacion', [
            'id_postulacion' => $postulacion,
            'estado_admision' => 'NO_ADMITIDO',
            'id_carrera_admitida' => null,
        ]);
    }

    public function test_process_keeps_pending_when_notes_are_missing(): void
    {
        $context = $this->contextWithCupos(1, 1);
        $postulacion = $this->createPostulacion($context);
        $this->createNota($postulacion, $context['materias'][0], 95, 1);
        $user = $this->userWithPermissions(['admision:process']);

        $this->actingAs($user)->post('/postulantes/admision-cupos/procesar', [
            'id_gestion' => $context['gestion']->id_gestion,
        ])->assertRedirect();

        $this->assertDatabaseHas('postulacion', [
            'id_postulacion' => $postulacion,
            'estado_admision' => 'PENDIENTE',
            'id_carrera_admitida' => null,
        ]);
    }

    public function test_process_keeps_pending_when_documentation_is_incomplete(): void
    {
        $context = $this->contextWithCupos(1, 1);
        $postulacion = $this->createApprovedPostulacion($context, 90, [
            'documentacion_completa' => false,
        ]);
        $user = $this->userWithPermissions(['admision:process']);

        $this->actingAs($user)->post('/postulantes/admision-cupos/procesar', [
            'id_gestion' => $context['gestion']->id_gestion,
        ])->assertRedirect();

        $this->assertDatabaseHas('postulacion', [
            'id_postulacion' => $postulacion,
            'estado_admision' => 'PENDIENTE',
            'id_carrera_admitida' => null,
        ]);
    }

    public function test_process_orders_by_average_descending(): void
    {
        $context = $this->contextWithCupos(1, 0);
        $low = $this->createApprovedPostulacion($context, 70);
        $high = $this->createApprovedPostulacion($context, 95);
        $user = $this->userWithPermissions(['admision:process']);

        $this->actingAs($user)->post('/postulantes/admision-cupos/procesar', [
            'id_gestion' => $context['gestion']->id_gestion,
        ])->assertRedirect();

        $this->assertDatabaseHas('postulacion', [
            'id_postulacion' => $high,
            'estado_admision' => 'ADMITIDO',
        ]);
        $this->assertDatabaseHas('postulacion', [
            'id_postulacion' => $low,
            'estado_admision' => 'NO_ADMITIDO',
        ]);
    }

    public function test_process_uses_fecha_postulacion_as_tiebreaker(): void
    {
        $context = $this->contextWithCupos(1, 0);
        $older = $this->createApprovedPostulacion($context, 80, [
            'fecha_postulacion' => '2026-01-01 08:00:00',
        ]);
        $newer = $this->createApprovedPostulacion($context, 80, [
            'fecha_postulacion' => '2026-01-02 08:00:00',
        ]);
        $user = $this->userWithPermissions(['admision:process']);

        $this->actingAs($user)->post('/postulantes/admision-cupos/procesar', [
            'id_gestion' => $context['gestion']->id_gestion,
        ])->assertRedirect();

        $this->assertDatabaseHas('postulacion', [
            'id_postulacion' => $older,
            'estado_admision' => 'ADMITIDO',
        ]);
        $this->assertDatabaseHas('postulacion', [
            'id_postulacion' => $newer,
            'estado_admision' => 'NO_ADMITIDO',
        ]);
    }

    public function test_process_does_not_modify_notes_or_delete_postulaciones(): void
    {
        $context = $this->contextWithCupos(1, 1);
        $this->createApprovedPostulacion($context, 90);
        $notesBefore = Nota::count();
        $postulacionesBefore = DB::table('postulacion')->count();
        $user = $this->userWithPermissions(['admision:process']);

        $this->actingAs($user)->post('/postulantes/admision-cupos/procesar', [
            'id_gestion' => $context['gestion']->id_gestion,
        ])->assertRedirect();

        $this->assertSame($notesBefore, Nota::count());
        $this->assertSame($postulacionesBefore, DB::table('postulacion')->count());
    }

    public function test_user_without_admision_process_cannot_process(): void
    {
        $context = $this->contextWithCupos(1, 1);
        $user = $this->userWithPermissions(['admision:read']);

        $this->actingAs($user)->post('/postulantes/admision-cupos/procesar', [
            'id_gestion' => $context['gestion']->id_gestion,
        ])->assertForbidden();
    }

    private function userWithPermissions(array $permissions): User
    {
        $this->seed(AccessControlSeeder::class);

        $user = User::factory()->create();
        $role = Rol::create([
            'nombre' => 'ROL_ADMISION_'.Str::upper(Str::random(8)),
            'descripcion' => 'Rol de prueba para admision',
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

        return $user->fresh();
    }

    private function contextWithCupos(int $cupo1, int $cupo2): array
    {
        $context = $this->createBaseContext();

        if ($cupo1 > 0) {
            CupoCarrera::create([
                'id_carrera' => $context['carrera1']->id_carrera,
                'id_gestion' => $context['gestion']->id_gestion,
                'cupo_maximo' => $cupo1,
            ]);
        }

        if ($cupo2 > 0) {
            CupoCarrera::create([
                'id_carrera' => $context['carrera2']->id_carrera,
                'id_gestion' => $context['gestion']->id_gestion,
                'cupo_maximo' => $cupo2,
            ]);
        }

        return $context;
    }

    private function createBaseContext(): array
    {
        $gestion = GestionAcademica::create([
            'nombre' => 'CUP '.Str::upper(Str::random(6)),
            'fecha_inicio' => '2026-01-01',
            'fecha_fin' => '2026-12-31',
            'activo' => true,
        ]);
        $grupo = GrupoAcademico::create([
            'id_gestion' => $gestion->id_gestion,
            'nombre' => 'Grupo '.Str::upper(Str::random(6)),
            'capacidad_maxima' => 70,
            'activo' => true,
        ]);
        $carrera1 = Carrera::create([
            'nombre' => 'Carrera '.Str::upper(Str::random(8)),
            'activo' => true,
        ]);
        $carrera2 = Carrera::create([
            'nombre' => 'Carrera '.Str::upper(Str::random(8)),
            'activo' => true,
        ]);
        $materias = collect(range(1, 4))
            ->map(fn (int $index) => MateriaCup::create([
                'nombre' => 'Materia '.$index.' '.Str::upper(Str::random(5)),
                'activo' => true,
            ]))
            ->values();

        return compact('gestion', 'grupo', 'carrera1', 'carrera2', 'materias');
    }

    private function createApprovedPostulacion(array $context, int $score, array $overrides = []): int
    {
        $postulacion = $this->createPostulacion($context, $overrides);
        $this->createFullNotas($postulacion, $context['materias'], $score);

        return $postulacion;
    }

    private function createPostulacion(array $context, array $overrides = []): int
    {
        $user = User::factory()->create();
        $postulante = Postulante::create([
            'id_usuario' => $user->id_usuario,
            'fecha_nacimiento' => '2005-01-01',
            'direccion' => 'Direccion de prueba',
            'colegio_procedencia' => 'Colegio Test',
            'ciudad' => 'Santa Cruz',
            'documentacion_completa' => $overrides['documentacion_completa'] ?? true,
        ]);

        return DB::table('postulacion')->insertGetId([
            'id_postulante' => $postulante->id_postulante,
            'id_gestion' => $context['gestion']->id_gestion,
            'id_carrera_opcion1' => $overrides['id_carrera_opcion1'] ?? $context['carrera1']->id_carrera,
            'id_carrera_opcion2' => $overrides['id_carrera_opcion2'] ?? $context['carrera2']->id_carrera,
            'id_carrera_admitida' => $overrides['id_carrera_admitida'] ?? null,
            'id_grupo' => $context['grupo']->id_grupo,
            'estado_admision' => $overrides['estado_admision'] ?? 'PENDIENTE',
            'fecha_postulacion' => $overrides['fecha_postulacion'] ?? now(),
        ], 'id_postulacion');
    }

    private function createFullNotas(int $idPostulacion, Collection $materias, int $score): void
    {
        foreach ($materias as $materia) {
            foreach ([1, 2, 3] as $exam) {
                $this->createNota($idPostulacion, $materia, $score, $exam);
            }
        }
    }

    private function createNota(int $idPostulacion, MateriaCup $materia, int $score, int $exam): void
    {
        Nota::create([
            'id_postulacion' => $idPostulacion,
            'id_materia' => $materia->id_materia,
            'nro_examen' => $exam,
            'nota' => $score,
            'registrado_por' => null,
        ]);
    }
}
