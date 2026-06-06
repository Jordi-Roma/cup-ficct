<?php

namespace Tests\Feature\GestionAcademica;

use App\Modules\AccesoSeguridad\Models\Permiso;
use App\Modules\AccesoSeguridad\Models\Rol;
use App\Modules\AccesoSeguridad\Models\User;
use App\Modules\GestionAcademica\Models\AsignacionAcademica;
use App\Modules\GestionAcademica\Models\Aula;
use App\Modules\GestionAcademica\Models\Docente;
use App\Modules\GestionAcademica\Models\DocenteHabilitacionMateria;
use App\Modules\GestionAcademica\Models\GestionAcademica;
use App\Modules\GestionAcademica\Models\GrupoAcademico;
use App\Modules\GestionAcademica\Models\Horario;
use App\Modules\GestionAcademica\Models\MateriaCup;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class AsignacionAcademicaTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/academico/asignaciones')->assertRedirect('/login');
    }

    public function test_user_without_asignaciones_read_receives_forbidden(): void
    {
        $this->seed(AccessControlSeeder::class);

        $user = User::factory()->create();

        $this->actingAs($user)->get('/academico/asignaciones')->assertForbidden();
    }

    public function test_user_with_asignaciones_read_can_list_asignaciones(): void
    {
        $this->createAsignacion();
        $user = $this->userWithPermissions(['asignaciones:read']);

        $this->actingAs($user)->get('/academico/asignaciones')->assertOk();
    }

    public function test_user_with_asignaciones_create_can_create_valid_assignment(): void
    {
        $context = $this->createContext();
        $user = $this->userWithPermissions(['asignaciones:create']);

        $this->actingAs($user)
            ->post('/academico/asignaciones', $this->payload($context))
            ->assertRedirect();

        $this->assertDatabaseHas('asignacion_academica', [
            'id_grupo' => $context['grupo']->id_grupo,
            'id_materia' => $context['materia']->id_materia,
            'id_docente' => $context['docente']->id_docente,
            'id_aula' => $context['aula']->id_aula,
            'id_horario' => $context['horario']->id_horario,
            'activo' => true,
        ]);
    }

    public function test_cannot_assign_docente_not_hired(): void
    {
        $context = $this->createContext([
            'docente' => ['contratado' => false],
        ]);
        $user = $this->userWithPermissions(['asignaciones:create']);

        $this->actingAs($user)
            ->post('/academico/asignaciones', $this->payload($context))
            ->assertSessionHasErrors('id_docente');
    }

    public function test_cannot_assign_inactive_docente(): void
    {
        $context = $this->createContext([
            'docente' => ['activo' => false],
        ]);
        $user = $this->userWithPermissions(['asignaciones:create']);

        $this->actingAs($user)
            ->post('/academico/asignaciones', $this->payload($context))
            ->assertSessionHasErrors('id_docente');
    }

    public function test_cannot_assign_inactive_materia(): void
    {
        $context = $this->createContext([
            'materia' => ['activo' => false],
        ]);
        $user = $this->userWithPermissions(['asignaciones:create']);

        $this->actingAs($user)
            ->post('/academico/asignaciones', $this->payload($context))
            ->assertSessionHasErrors('id_materia');
    }

    public function test_cannot_assign_inactive_group(): void
    {
        $context = $this->createContext([
            'grupo' => ['activo' => false],
        ]);
        $user = $this->userWithPermissions(['asignaciones:create']);

        $this->actingAs($user)
            ->post('/academico/asignaciones', $this->payload($context))
            ->assertSessionHasErrors('id_grupo');
    }

    public function test_cannot_assign_room_with_lower_capacity_than_group(): void
    {
        $context = $this->createContext([
            'grupo' => ['capacidad_maxima' => 70],
            'aula' => ['capacidad' => 30],
        ]);
        $user = $this->userWithPermissions(['asignaciones:create']);

        $this->actingAs($user)
            ->post('/academico/asignaciones', $this->payload($context))
            ->assertSessionHasErrors('id_aula');
    }

    public function test_cannot_duplicate_group_and_materia(): void
    {
        $asignacion = $this->createAsignacion();
        $context = $this->createContext([
            'grupo' => $asignacion->grupo,
            'materia' => $asignacion->materia,
        ]);
        $user = $this->userWithPermissions(['asignaciones:create']);

        $this->actingAs($user)
            ->post('/academico/asignaciones', $this->payload($context))
            ->assertSessionHasErrors('id_materia');
    }

    public function test_cannot_duplicate_docente_and_horario(): void
    {
        $asignacion = $this->createAsignacion();
        $context = $this->createContext([
            'docente' => $asignacion->docente,
            'horario' => $asignacion->horario,
        ]);
        $user = $this->userWithPermissions(['asignaciones:create']);

        $this->actingAs($user)
            ->post('/academico/asignaciones', $this->payload($context))
            ->assertSessionHasErrors('id_docente');
    }

    public function test_cannot_duplicate_aula_and_horario(): void
    {
        $asignacion = $this->createAsignacion();
        $context = $this->createContext([
            'aula' => $asignacion->aula,
            'horario' => $asignacion->horario,
        ]);
        $user = $this->userWithPermissions(['asignaciones:create']);

        $this->actingAs($user)
            ->post('/academico/asignaciones', $this->payload($context))
            ->assertSessionHasErrors('id_aula');
    }

    public function test_docente_cannot_exceed_four_active_groups(): void
    {
        $gestion = $this->createGestion();
        $docente = $this->createDocente();
        $user = $this->userWithPermissions(['asignaciones:create']);

        for ($index = 0; $index < 4; $index++) {
            AsignacionAcademica::create([
                'id_grupo' => $this->createGrupo($gestion, "Grupo {$index}")->id_grupo,
                'id_materia' => $this->createMateria("Materia {$index}")->id_materia,
                'id_docente' => $docente->id_docente,
                'id_aula' => $this->createAula("Aula {$index}")->id_aula,
                'id_horario' => $this->createHorario('MANANA', sprintf('%02d:00', 8 + $index), sprintf('%02d:00', 9 + $index))->id_horario,
                'activo' => true,
            ]);
        }

        $context = $this->createContext([
            'gestion' => $gestion,
            'docente' => $docente,
            'horario' => $this->createHorario('MANANA', '12:00', '13:00'),
        ]);

        $this->actingAs($user)
            ->post('/academico/asignaciones', $this->payload($context))
            ->assertSessionHasErrors('id_docente');
    }

    public function test_user_with_asignaciones_update_can_update_assignment(): void
    {
        $asignacion = $this->createAsignacion();
        $context = $this->createContext([
            'gestion' => $asignacion->grupo->gestion,
        ]);
        $user = $this->userWithPermissions(['asignaciones:update']);

        $this->actingAs($user)
            ->put("/academico/asignaciones/{$asignacion->id_asignacion}", $this->payload($context))
            ->assertRedirect();

        $this->assertDatabaseHas('asignacion_academica', [
            'id_asignacion' => $asignacion->id_asignacion,
            'id_grupo' => $context['grupo']->id_grupo,
            'id_materia' => $context['materia']->id_materia,
        ]);
    }

    public function test_user_with_asignaciones_delete_can_deactivate_without_physical_delete(): void
    {
        $asignacion = $this->createAsignacion();
        $user = $this->userWithPermissions(['asignaciones:delete']);

        $this->actingAs($user)
            ->patch("/academico/asignaciones/{$asignacion->id_asignacion}/toggle")
            ->assertRedirect();

        $this->assertDatabaseHas('asignacion_academica', [
            'id_asignacion' => $asignacion->id_asignacion,
            'activo' => false,
        ]);
        $this->assertSame(1, AsignacionAcademica::whereKey($asignacion->id_asignacion)->count());
    }

    public function test_cannot_deactivate_assignment_with_registered_notes(): void
    {
        $asignacion = $this->createAsignacion();
        $this->createNotaForAssignment($asignacion);
        $user = $this->userWithPermissions(['asignaciones:delete']);

        $this->actingAs($user)
            ->patch("/academico/asignaciones/{$asignacion->id_asignacion}/toggle")
            ->assertSessionHasErrors('asignacion');

        $this->assertDatabaseHas('asignacion_academica', [
            'id_asignacion' => $asignacion->id_asignacion,
            'activo' => true,
        ]);
    }

    private function userWithPermissions(array $permissions): User
    {
        $this->seed(AccessControlSeeder::class);

        $user = User::factory()->create();
        $role = Rol::create([
            'nombre' => 'ROL_ASIGNACIONES_'.Str::upper(Str::random(8)),
            'descripcion' => 'Rol de prueba para asignaciones',
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

    private function createAsignacion(array $overrides = []): AsignacionAcademica
    {
        $context = $this->createContext($overrides);

        return AsignacionAcademica::create([
            'id_grupo' => $context['grupo']->id_grupo,
            'id_materia' => $context['materia']->id_materia,
            'id_docente' => $context['docente']->id_docente,
            'id_aula' => $context['aula']->id_aula,
            'id_horario' => $context['horario']->id_horario,
            'activo' => true,
        ])->load(['grupo.gestion', 'materia', 'docente.usuario', 'aula', 'horario']);
    }

    private function createContext(array $overrides = []): array
    {
        $gestion = $overrides['gestion'] ?? $this->createGestion();
        $grupo = $overrides['grupo'] ?? null;
        $materia = $overrides['materia'] ?? null;
        $docente = $overrides['docente'] ?? null;
        $aula = $overrides['aula'] ?? null;
        $horario = $overrides['horario'] ?? null;

        $grupo = $grupo instanceof GrupoAcademico ? $grupo : $this->createGrupo($gestion, overrides: is_array($grupo) ? $grupo : []);
        $materia = $materia instanceof MateriaCup ? $materia : $this->createMateria(overrides: is_array($materia) ? $materia : []);
        $docente = $docente instanceof Docente ? $docente : $this->createDocente(is_array($docente) ? $docente : []);
        $aula = $aula instanceof Aula ? $aula : $this->createAula(overrides: is_array($aula) ? $aula : []);
        $horario = $horario instanceof Horario ? $horario : $this->createHorario();

        $this->habilitateDocenteForMateria($docente, $materia);

        return [
            'gestion' => $gestion,
            'grupo' => $grupo,
            'materia' => $materia,
            'docente' => $docente,
            'aula' => $aula,
            'horario' => $horario,
        ];
    }

    private function payload(array $context): array
    {
        return [
            'id_grupo' => $context['grupo']->id_grupo,
            'id_materia' => $context['materia']->id_materia,
            'id_docente' => $context['docente']->id_docente,
            'id_aula' => $context['aula']->id_aula,
            'id_horario' => $context['horario']->id_horario,
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

    private function createGrupo(GestionAcademica $gestion, ?string $nombre = null, array $overrides = []): GrupoAcademico
    {
        return GrupoAcademico::create([
            'id_gestion' => $gestion->id_gestion,
            'nombre' => $nombre ?? 'Grupo '.Str::upper(Str::random(6)),
            'turno' => $overrides['turno'] ?? 'MANANA',
            'capacidad_maxima' => $overrides['capacidad_maxima'] ?? 70,
            'activo' => $overrides['activo'] ?? true,
        ]);
    }

    private function createMateria(?string $nombre = null, array $overrides = []): MateriaCup
    {
        return MateriaCup::create([
            'nombre' => $nombre ?? 'Materia '.Str::upper(Str::random(8)),
            'activo' => $overrides['activo'] ?? true,
        ]);
    }

    private function createDocente(array $overrides = []): Docente
    {
        $user = User::factory()->create();

        return Docente::create([
            'id_usuario' => $user->id_usuario,
            'profesional_area' => true,
            'maestria' => true,
            'diplomado_educacion_superior' => true,
            'maestria_educacion_superior' => true,
            'contratado' => $overrides['contratado'] ?? true,
            'activo' => $overrides['activo'] ?? true,
        ])->load('usuario');
    }

    private function habilitateDocenteForMateria(Docente $docente, MateriaCup $materia): void
    {
        DocenteHabilitacionMateria::updateOrCreate(
            [
                'id_docente' => $docente->id_docente,
                'id_materia' => $materia->id_materia,
                'tipo_habilitacion' => DocenteHabilitacionMateria::PROFESIONAL_AREA,
            ],
            ['activo' => true]
        );
    }

    private function createAula(?string $nombre = null, array $overrides = []): Aula
    {
        return Aula::create([
            'nombre' => $nombre ?? 'Aula '.Str::upper(Str::random(6)),
            'capacidad' => $overrides['capacidad'] ?? 70,
        ]);
    }

    private function createHorario(string $turno = 'MANANA', string $inicio = '08:00', string $fin = '10:00'): Horario
    {
        return Horario::firstOrCreate(
            [
                'turno' => $turno,
                'hora_inicio' => $inicio,
                'hora_fin' => $fin,
            ],
            ['activo' => true]
        );
    }

    private function createNotaForAssignment(AsignacionAcademica $asignacion): void
    {
        $carreraId = DB::table('carrera')->insertGetId([
            'nombre' => 'Carrera '.Str::upper(Str::random(8)),
            'activo' => true,
        ], 'id_carrera');
        $postulanteUser = User::factory()->create();
        $postulanteId = DB::table('postulante')->insertGetId([
            'id_usuario' => $postulanteUser->id_usuario,
            'fecha_nacimiento' => '2005-01-01',
            'direccion' => 'Direccion de prueba',
            'colegio_procedencia' => 'Colegio Test',
            'ciudad' => 'Santa Cruz',
            'documentacion_completa' => true,
        ], 'id_postulante');
        $postulacionId = DB::table('postulacion')->insertGetId([
            'id_postulante' => $postulanteId,
            'id_gestion' => $asignacion->grupo->id_gestion,
            'id_carrera_opcion1' => $carreraId,
            'id_grupo' => $asignacion->id_grupo,
            'turno_preferido' => $asignacion->grupo->turno,
            'estado_admision' => 'PENDIENTE',
            'fecha_postulacion' => now(),
        ], 'id_postulacion');

        DB::table('nota')->insert([
            'id_postulacion' => $postulacionId,
            'id_materia' => $asignacion->id_materia,
            'nro_examen' => 1,
            'nota' => 80,
            'fecha_registro' => now(),
        ]);
    }
}
