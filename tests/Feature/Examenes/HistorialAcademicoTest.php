<?php

namespace Tests\Feature\Examenes;

use App\Modules\Autenticacion\Models\Permiso;
use App\Modules\Autenticacion\Models\Rol;
use App\Modules\Autenticacion\Models\User;
use App\Modules\Examenes\Models\Nota;
use App\Modules\GestionAcademica\Models\GestionAcademica;
use App\Modules\GestionAcademica\Models\GrupoAcademico;
use App\Modules\GestionAcademica\Models\MateriaCup;
use App\Modules\RegistroPostulantes\Models\Postulante;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class HistorialAcademicoTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/examenes/historial')->assertRedirect('/login');
    }

    public function test_user_without_historial_read_own_receives_forbidden(): void
    {
        $this->seed(AccessControlSeeder::class);
        $user = User::factory()->create();

        $this->actingAs($user)->get('/examenes/historial')->assertForbidden();
    }

    public function test_postulante_can_see_own_historial(): void
    {
        $context = $this->createContext();
        $user = $this->assignPermissions($context['postulante']->usuario, ['historial:read-own']);
        $this->createNota($context, $context['materias'][0], 1, 80);

        $response = $this->actingAs($user)->get('/examenes/historial');

        $response->assertOk();
        $historial = $response->viewData('page')['props']['historial'];

        $this->assertSame($context['postulante']->id_postulante, $historial['postulante']['id_postulante']);
        $this->assertSame('PENDIENTE', $historial['resumen']['estado_final']);
    }

    public function test_postulante_cannot_see_another_postulante_historial(): void
    {
        $context = $this->createContext();
        $other = $this->createPostulanteWithPostulacion($context['gestion'], $context['grupo']);
        $user = $this->assignPermissions($context['postulante']->usuario, ['historial:read-own']);

        $this->actingAs($user)
            ->get("/examenes/historial?id_postulante={$other['postulante']->id_postulante}")
            ->assertForbidden();
    }

    public function test_admin_can_search_postulantes(): void
    {
        $context = $this->createContext();
        $admin = $this->userWithPermissions(['historial:read-own', 'historial:read']);

        $response = $this->actingAs($admin)
            ->get('/examenes/historial?search='.$context['postulante']->usuario->ci);

        $response->assertOk();
        $postulantes = $response->viewData('page')['props']['postulantes'];

        $this->assertCount(1, $postulantes);
        $this->assertSame($context['postulante']->id_postulante, $postulantes[0]['id_postulante']);
    }

    public function test_admin_can_see_any_postulante_historial(): void
    {
        $context = $this->createContext();
        $admin = $this->userWithPermissions(['historial:read-own', 'historial:read']);
        $this->createNota($context, $context['materias'][0], 1, 90);

        $response = $this->actingAs($admin)
            ->get("/examenes/historial?id_postulante={$context['postulante']->id_postulante}");

        $response->assertOk();
        $historial = $response->viewData('page')['props']['historial'];

        $this->assertSame($context['postulante']->id_postulante, $historial['postulante']['id_postulante']);
        $this->assertSame(90.0, $historial['materias'][0]['examen_1']);
    }

    public function test_historial_shows_notes_by_subject_and_exam(): void
    {
        $context = $this->createContext();
        $this->createNota($context, $context['materias'][0], 1, 70);
        $this->createNota($context, $context['materias'][0], 2, 80);
        $this->createNota($context, $context['materias'][0], 3, 90);
        $admin = $this->userWithPermissions(['historial:read-own', 'historial:read']);

        $response = $this->actingAs($admin)
            ->get("/examenes/historial?id_postulante={$context['postulante']->id_postulante}");

        $materia = $response->viewData('page')['props']['historial']['materias'][0];

        $this->assertSame(70.0, $materia['examen_1']);
        $this->assertSame(80.0, $materia['examen_2']);
        $this->assertSame(90.0, $materia['examen_3']);
    }

    public function test_calculates_average_by_subject(): void
    {
        $context = $this->createContext();
        $this->createNota($context, $context['materias'][0], 1, 60);
        $this->createNota($context, $context['materias'][0], 2, 70);
        $this->createNota($context, $context['materias'][0], 3, 80);
        $admin = $this->userWithPermissions(['historial:read-own', 'historial:read']);

        $response = $this->actingAs($admin)
            ->get("/examenes/historial?id_postulante={$context['postulante']->id_postulante}");

        $materia = $response->viewData('page')['props']['historial']['materias'][0];

        $this->assertSame(70.0, $materia['promedio']);
        $this->assertSame('APROBADO', $materia['estado_materia']);
    }

    public function test_final_average_is_approved_when_complete_and_sixty_or_more(): void
    {
        $context = $this->createContext();
        $this->createFullNotas($context, 75);
        $admin = $this->userWithPermissions(['historial:read-own', 'historial:read']);

        $response = $this->actingAs($admin)
            ->get("/examenes/historial?id_postulante={$context['postulante']->id_postulante}");

        $resumen = $response->viewData('page')['props']['historial']['resumen'];

        $this->assertSame(75.0, $resumen['promedio_final']);
        $this->assertSame('APROBADO', $resumen['estado_final']);
    }

    public function test_final_average_is_failed_when_complete_and_lower_than_sixty(): void
    {
        $context = $this->createContext();
        $this->createFullNotas($context, 55);
        $admin = $this->userWithPermissions(['historial:read-own', 'historial:read']);

        $response = $this->actingAs($admin)
            ->get("/examenes/historial?id_postulante={$context['postulante']->id_postulante}");

        $resumen = $response->viewData('page')['props']['historial']['resumen'];

        $this->assertSame(55.0, $resumen['promedio_final']);
        $this->assertSame('REPROBADO', $resumen['estado_final']);
    }

    public function test_final_status_is_pending_when_notes_are_missing(): void
    {
        $context = $this->createContext();
        $this->createNota($context, $context['materias'][0], 1, 95);
        $admin = $this->userWithPermissions(['historial:read-own', 'historial:read']);

        $response = $this->actingAs($admin)
            ->get("/examenes/historial?id_postulante={$context['postulante']->id_postulante}");

        $resumen = $response->viewData('page')['props']['historial']['resumen'];

        $this->assertSame(95.0, $resumen['promedio_final']);
        $this->assertSame('PENDIENTE', $resumen['estado_final']);
    }

    public function test_historial_does_not_modify_notes_or_postulaciones(): void
    {
        $context = $this->createContext();
        $this->createNota($context, $context['materias'][0], 1, 85);
        $admin = $this->userWithPermissions(['historial:read-own', 'historial:read']);
        $notesBefore = Nota::count();
        $postulacionesBefore = DB::table('postulacion')->count();

        $this->actingAs($admin)
            ->get("/examenes/historial?id_postulante={$context['postulante']->id_postulante}")
            ->assertOk();

        $this->assertSame($notesBefore, Nota::count());
        $this->assertSame($postulacionesBefore, DB::table('postulacion')->count());
    }

    private function userWithPermissions(array $permissions): User
    {
        return $this->assignPermissions(User::factory()->create(), $permissions);
    }

    private function assignPermissions(User $user, array $permissions): User
    {
        $this->seed(AccessControlSeeder::class);

        $role = Rol::create([
            'nombre' => 'ROL_HISTORIAL_'.Str::upper(Str::random(8)),
            'descripcion' => 'Rol de prueba para historial academico',
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

    private function createContext(): array
    {
        $gestion = $this->createGestion();
        $grupo = GrupoAcademico::create([
            'id_gestion' => $gestion->id_gestion,
            'nombre' => 'Grupo '.Str::upper(Str::random(6)),
            'capacidad_maxima' => 70,
            'activo' => true,
        ]);
        $materias = collect(range(1, 4))
            ->map(fn (int $index) => $this->createMateria("Materia {$index} ".Str::upper(Str::random(5))))
            ->values();
        $postulanteContext = $this->createPostulanteWithPostulacion($gestion, $grupo);

        return [
            'gestion' => $gestion,
            'grupo' => $grupo,
            'materias' => $materias,
            'postulante' => $postulanteContext['postulante'],
            'postulacion' => $postulanteContext['postulacion'],
        ];
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

    private function createMateria(string $nombre): MateriaCup
    {
        return MateriaCup::create([
            'nombre' => $nombre,
            'activo' => true,
        ]);
    }

    private function createPostulanteWithPostulacion(GestionAcademica $gestion, GrupoAcademico $grupo): array
    {
        $carrera1 = DB::table('carrera')->insertGetId([
            'nombre' => 'Carrera '.Str::upper(Str::random(8)),
            'activo' => true,
        ], 'id_carrera');
        $carrera2 = DB::table('carrera')->insertGetId([
            'nombre' => 'Carrera '.Str::upper(Str::random(8)),
            'activo' => true,
        ], 'id_carrera');
        $user = User::factory()->create();
        $postulante = Postulante::create([
            'id_usuario' => $user->id_usuario,
            'fecha_nacimiento' => '2005-01-01',
            'direccion' => 'Direccion de prueba',
            'colegio_procedencia' => 'Colegio Test',
            'ciudad' => 'Santa Cruz',
            'documentacion_completa' => true,
        ]);

        $postulacionId = DB::table('postulacion')->insertGetId([
            'id_postulante' => $postulante->id_postulante,
            'id_gestion' => $gestion->id_gestion,
            'id_carrera_opcion1' => $carrera1,
            'id_carrera_opcion2' => $carrera2,
            'id_grupo' => $grupo->id_grupo,
            'estado_admision' => 'PENDIENTE',
            'fecha_postulacion' => now(),
        ], 'id_postulacion');

        return [
            'postulante' => $postulante->load('usuario'),
            'postulacion' => DB::table('postulacion')->where('id_postulacion', $postulacionId)->first(),
        ];
    }

    private function createNota(array $context, MateriaCup $materia, int $exam, int $score): Nota
    {
        return Nota::create([
            'id_postulacion' => $context['postulacion']->id_postulacion,
            'id_materia' => $materia->id_materia,
            'nro_examen' => $exam,
            'nota' => $score,
            'registrado_por' => null,
        ]);
    }

    private function createFullNotas(array $context, int $score): void
    {
        foreach ($context['materias'] as $materia) {
            foreach ([1, 2, 3] as $exam) {
                $this->createNota($context, $materia, $exam, $score);
            }
        }
    }
}
