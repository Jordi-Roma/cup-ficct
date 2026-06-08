<?php

namespace Tests\Feature\Examenes;

use App\Modules\AccesoSeguridad\Models\Permiso;
use App\Modules\AccesoSeguridad\Models\Rol;
use App\Modules\AccesoSeguridad\Models\User;
use App\Modules\Examenes\Models\Nota;
use App\Modules\Examenes\Services\NotaService;
use App\Modules\GestionAcademica\Models\AsignacionAcademica;
use App\Modules\GestionAcademica\Models\Aula;
use App\Modules\GestionAcademica\Models\Docente;
use App\Modules\GestionAcademica\Models\DocenteHabilitacionMateria;
use App\Modules\GestionAcademica\Models\GestionAcademica;
use App\Modules\GestionAcademica\Models\GrupoAcademico;
use App\Modules\GestionAcademica\Models\Horario;
use App\Modules\GestionAcademica\Models\MateriaCup;
use App\Modules\RegistroPostulantes\Models\Postulacion;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotaTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/examenes/notas')->assertRedirect('/login');
    }

    public function test_user_without_notas_read_receives_forbidden(): void
    {
        $this->seed(AccessControlSeeder::class);
        $this->createContext();
        $user = User::factory()->create();

        $this->actingAs($user)->get('/examenes/notas')->assertForbidden();
    }

    public function test_user_with_notas_read_can_load_page(): void
    {
        $this->createContext();
        $user = $this->userWithPermissions(['notas:read']);

        $this->actingAs($user)->get('/examenes/notas')->assertOk();
    }

    public function test_docente_only_sees_assigned_groups_and_subjects(): void
    {
        $context = $this->createContext();
        $otherGrupo = GrupoAcademico::create([
            'id_gestion' => $context['gestion']->id_gestion,
            'nombre' => 'Grupo '.Str::upper(Str::random(6)),
            'turno' => 'MANANA',
            'capacidad_maxima' => 70,
            'activo' => true,
        ]);
        $otherMateria = $this->createMateria();
        AsignacionAcademica::create([
            'id_grupo' => $otherGrupo->id_grupo,
            'id_materia' => $otherMateria->id_materia,
            'id_docente' => $this->createDocente()->id_docente,
            'id_aula' => Aula::create([
                'nombre' => 'Aula '.Str::upper(Str::random(6)),
                'capacidad' => 70,
            ])->id_aula,
            'id_horario' => $this->createHorario('MANANA', '09:00', '10:00')->id_horario,
            'activo' => true,
        ]);
        $teacher = $this->teacherUserWithPermissions($context['docente'], ['notas:read']);

        $response = $this->actingAs($teacher)->get('/examenes/notas');

        $response->assertOk();
        $options = $response->viewData('page')['props']['options'];

        $this->assertSame([$context['grupo']->id_grupo], collect($options['grupos'])->pluck('id_grupo')->all());
        $this->assertSame([$context['materia']->id_materia], collect($options['materias'])->pluck('id_materia')->all());
        $this->assertNotContains($otherGrupo->id_grupo, collect($options['grupos'])->pluck('id_grupo')->all());
    }

    public function test_user_with_notas_create_can_register_valid_note(): void
    {
        $context = $this->createContext();
        $teacher = $this->teacherUserWithPermissions($context['docente'], ['notas:create']);

        $this->actingAs($teacher)
            ->post('/examenes/notas', $this->notaPayload($context, ['nota' => 87]))
            ->assertRedirect();

        $this->assertDatabaseHas('nota', [
            'id_postulacion' => $context['postulacion']->id_postulacion,
            'id_materia' => $context['materia']->id_materia,
            'nro_examen' => 1,
            'nota' => '87.00',
            'registrado_por' => $teacher->id_usuario,
        ]);
    }

    public function test_nro_examen_must_be_between_one_and_three(): void
    {
        $context = $this->createContext();
        $teacher = $this->teacherUserWithPermissions($context['docente'], ['notas:create']);

        $this->actingAs($teacher)
            ->post('/examenes/notas', $this->notaPayload($context, ['nro_examen' => 4]))
            ->assertSessionHasErrors('nro_examen');
    }

    public function test_note_cannot_be_less_than_zero(): void
    {
        $context = $this->createContext();
        $teacher = $this->teacherUserWithPermissions($context['docente'], ['notas:create']);

        $this->actingAs($teacher)
            ->post('/examenes/notas', $this->notaPayload($context, ['nota' => -1]))
            ->assertSessionHasErrors('nota');
    }

    public function test_note_cannot_be_greater_than_one_hundred(): void
    {
        $context = $this->createContext();
        $teacher = $this->teacherUserWithPermissions($context['docente'], ['notas:create']);

        $this->actingAs($teacher)
            ->post('/examenes/notas', $this->notaPayload($context, ['nota' => 101]))
            ->assertSessionHasErrors('nota');
    }

    public function test_cannot_duplicate_postulacion_materia_and_exam(): void
    {
        $context = $this->createContext();
        $teacher = $this->teacherUserWithPermissions($context['docente'], ['notas:create']);
        $this->createNota($context, ['registrado_por' => $teacher->id_usuario]);

        $this->actingAs($teacher)
            ->post('/examenes/notas', $this->notaPayload($context))
            ->assertSessionHasErrors('nota');
    }

    public function test_docente_cannot_register_without_active_assignment(): void
    {
        $context = $this->createContext();
        $teacher = $this->teacherUserWithPermissions($context['docente'], ['notas:create']);
        AsignacionAcademica::query()->whereKey($context['asignacion']->id_asignacion)->update(['activo' => false]);

        $this->actingAs($teacher)
            ->post('/examenes/notas', $this->notaPayload($context))
            ->assertSessionHasErrors('id_materia');
    }

    public function test_docente_can_register_with_active_assignment(): void
    {
        $context = $this->createContext();
        $teacher = $this->teacherUserWithPermissions($context['docente'], ['notas:create']);

        $this->actingAs($teacher)
            ->post('/examenes/notas', $this->notaPayload($context))
            ->assertRedirect();

        $this->assertSame(1, Nota::count());
    }

    public function test_docente_who_registered_note_can_edit_it(): void
    {
        $context = $this->createContext();
        $teacher = $this->teacherUserWithPermissions($context['docente'], ['notas:update']);
        $nota = $this->createNota($context, ['registrado_por' => $teacher->id_usuario]);

        $this->actingAs($teacher)
            ->put("/examenes/notas/{$nota->id_nota}", ['nota' => 91])
            ->assertRedirect();

        $this->assertDatabaseHas('nota', [
            'id_nota' => $nota->id_nota,
            'nota' => '91.00',
        ]);
    }

    public function test_different_docente_cannot_edit_note_from_other_teacher(): void
    {
        $context = $this->createContext();
        $teacher = $this->teacherUserWithPermissions($context['docente'], ['notas:update']);
        $otherDocente = $this->createDocente();
        $otherTeacher = $this->teacherUserWithPermissions($otherDocente, ['notas:update']);
        $nota = $this->createNota($context, ['registrado_por' => $teacher->id_usuario]);

        $this->actingAs($otherTeacher)
            ->put("/examenes/notas/{$nota->id_nota}", ['nota' => 70])
            ->assertSessionHasErrors('nota');
    }

    public function test_admin_or_coordinator_with_notas_update_can_edit_note(): void
    {
        $context = $this->createContext();
        $nota = $this->createNota($context);
        $admin = $this->userWithPermissions(['notas:update']);

        $this->actingAs($admin)
            ->put("/examenes/notas/{$nota->id_nota}", ['nota' => 95])
            ->assertRedirect();

        $this->assertDatabaseHas('nota', [
            'id_nota' => $nota->id_nota,
            'nota' => '95.00',
        ]);
    }

    public function test_calculates_average_by_subject(): void
    {
        $context = $this->createContext();
        $this->createNota($context, ['nro_examen' => 1, 'nota' => 60]);
        $this->createNota($context, ['nro_examen' => 2, 'nota' => 70]);
        $this->createNota($context, ['nro_examen' => 3, 'nota' => 80]);

        $average = app(NotaService::class)->calculatePromedioMateria(
            $context['postulacion']->id_postulacion,
            $context['materia']->id_materia,
        );

        $this->assertSame(70.0, $average);
    }

    public function test_final_average_is_approved_when_sixty_or_more(): void
    {
        $context = $this->createContext();
        $this->createFullNotas($context, 75);

        $result = app(NotaService::class)->calculateResultadoFinal($context['postulacion']->id_postulacion);

        $this->assertSame(75.0, $result['promedio_final']);
        $this->assertSame('APROBADO', $result['estado_final']);
    }

    public function test_final_average_is_failed_when_lower_than_sixty(): void
    {
        $context = $this->createContext();
        $this->createFullNotas($context, 55);

        $result = app(NotaService::class)->calculateResultadoFinal($context['postulacion']->id_postulacion);

        $this->assertSame(55.0, $result['promedio_final']);
        $this->assertSame('REPROBADO', $result['estado_final']);
    }

    public function test_final_status_is_pending_when_notes_are_missing(): void
    {
        $context = $this->createContext();
        $this->createNota($context, ['nro_examen' => 1, 'nota' => 90]);

        $result = app(NotaService::class)->calculateResultadoFinal($context['postulacion']->id_postulacion);

        $this->assertSame(90.0, $result['promedio_final']);
        $this->assertSame('PENDIENTE', $result['estado_final']);
    }

    public function test_user_without_notas_create_cannot_generate_test_scores(): void
    {
        $this->createContext();
        $user = $this->userWithPermissions(['notas:read']);

        $this->actingAs($user)
            ->post('/examenes/notas/generar-prueba', [
                'nota_minima' => 50,
                'nota_maxima' => 95,
            ])
            ->assertForbidden();
    }

    public function test_user_with_notas_create_can_generate_test_scores_for_group_and_subject(): void
    {
        $context = $this->createContext();
        $admin = $this->userWithPermissions(['notas:create']);

        $this->actingAs($admin)
            ->post('/examenes/notas/generar-prueba', [
                'id_grupo' => $context['grupo']->id_grupo,
                'id_materia' => $context['materia']->id_materia,
                'nota_minima' => 50,
                'nota_maxima' => 95,
            ])
            ->assertRedirect()
            ->assertSessionHas('notas_generate_summary');

        $summary = session('notas_generate_summary');

        $this->assertSame(3, $summary['creadas']);
        $this->assertSame(0, $summary['omitidas']);
        $this->assertSame([1, 2, 3], Nota::query()->orderBy('nro_examen')->pluck('nro_examen')->all());
        $this->assertDatabaseHas('nota', [
            'id_postulacion' => $context['postulacion']->id_postulacion,
            'id_materia' => $context['materia']->id_materia,
            'nro_examen' => 1,
            'registrado_por' => $admin->id_usuario,
        ]);
    }

    public function test_generate_test_scores_does_not_overwrite_existing_notes(): void
    {
        $context = $this->createContext();
        $admin = $this->userWithPermissions(['notas:create']);
        $this->createNota($context, ['nro_examen' => 1, 'nota' => 88, 'registrado_por' => $admin->id_usuario]);

        $this->actingAs($admin)
            ->post('/examenes/notas/generar-prueba', [
                'id_grupo' => $context['grupo']->id_grupo,
                'id_materia' => $context['materia']->id_materia,
                'nota_minima' => 50,
                'nota_maxima' => 95,
            ])
            ->assertRedirect();

        $summary = session('notas_generate_summary');

        $this->assertSame(2, $summary['creadas']);
        $this->assertSame(1, $summary['omitidas']);
        $this->assertDatabaseHas('nota', [
            'id_postulacion' => $context['postulacion']->id_postulacion,
            'id_materia' => $context['materia']->id_materia,
            'nro_examen' => 1,
            'nota' => '88.00',
        ]);
    }

    public function test_generate_test_scores_for_all_groups_when_group_is_empty(): void
    {
        $context = $this->createContext();
        $secondGroup = GrupoAcademico::create([
            'id_gestion' => $context['gestion']->id_gestion,
            'nombre' => 'Grupo '.Str::upper(Str::random(6)),
            'turno' => 'MANANA',
            'capacidad_maxima' => 70,
            'activo' => true,
        ]);
        $secondPostulacion = $this->createPostulacion($context['gestion'], $secondGroup);
        AsignacionAcademica::create([
            'id_grupo' => $secondGroup->id_grupo,
            'id_materia' => $context['materia']->id_materia,
            'id_docente' => $context['docente']->id_docente,
            'id_aula' => $context['aula']->id_aula,
            'id_horario' => $this->createHorario('MANANA', '09:00', '10:00')->id_horario,
            'activo' => true,
        ]);
        $admin = $this->userWithPermissions(['notas:create']);

        $this->actingAs($admin)
            ->post('/examenes/notas/generar-prueba', [
                'id_materia' => $context['materia']->id_materia,
                'nota_minima' => 50,
                'nota_maxima' => 95,
            ])
            ->assertRedirect();

        $summary = session('notas_generate_summary');

        $this->assertSame(6, $summary['creadas']);
        $this->assertSame(2, $summary['grupos_procesados']);
        $this->assertDatabaseHas('nota', [
            'id_postulacion' => $secondPostulacion->id_postulacion,
            'id_materia' => $context['materia']->id_materia,
            'nro_examen' => 3,
        ]);
    }

    public function test_generate_test_scores_for_all_subjects_when_subject_is_empty(): void
    {
        $context = $this->createContext();
        $secondMateria = $this->createMateria('Materia adicional');
        AsignacionAcademica::create([
            'id_grupo' => $context['grupo']->id_grupo,
            'id_materia' => $secondMateria->id_materia,
            'id_docente' => $context['docente']->id_docente,
            'id_aula' => $context['aula']->id_aula,
            'id_horario' => $this->createHorario('MANANA', '09:00', '10:00')->id_horario,
            'activo' => true,
        ]);
        $admin = $this->userWithPermissions(['notas:create']);

        $this->actingAs($admin)
            ->post('/examenes/notas/generar-prueba', [
                'id_grupo' => $context['grupo']->id_grupo,
                'nota_minima' => 60,
                'nota_maxima' => 90,
            ])
            ->assertRedirect();

        $summary = session('notas_generate_summary');

        $this->assertSame(6, $summary['creadas']);
        $this->assertSame(2, $summary['materias_procesadas']);
    }

    public function test_generate_test_scores_omits_subject_without_active_assignment(): void
    {
        $context = $this->createContext();
        $unassignedMateria = $this->createMateria('Materia sin asignacion');
        $admin = $this->userWithPermissions(['notas:create']);

        $this->actingAs($admin)
            ->post('/examenes/notas/generar-prueba', [
                'id_grupo' => $context['grupo']->id_grupo,
                'id_materia' => $unassignedMateria->id_materia,
                'nota_minima' => 50,
                'nota_maxima' => 95,
            ])
            ->assertRedirect();

        $summary = session('notas_generate_summary');

        $this->assertSame(0, $summary['creadas']);
        $this->assertSame(1, $summary['omitidas']);
        $this->assertDatabaseMissing('nota', [
            'id_materia' => $unassignedMateria->id_materia,
        ]);
    }

    public function test_generate_test_scores_respects_score_range(): void
    {
        $context = $this->createContext();
        $admin = $this->userWithPermissions(['notas:create']);

        $this->actingAs($admin)
            ->post('/examenes/notas/generar-prueba', [
                'id_grupo' => $context['grupo']->id_grupo,
                'id_materia' => $context['materia']->id_materia,
                'nota_minima' => 70,
                'nota_maxima' => 70,
            ])
            ->assertRedirect();

        $this->assertSame([70.0, 70.0, 70.0], Nota::query()
            ->orderBy('nro_examen')
            ->pluck('nota')
            ->map(fn ($score) => (float) $score)
            ->all());
    }

    public function test_docente_normal_cannot_generate_test_scores(): void
    {
        $context = $this->createContext();
        $teacher = $this->teacherUserWithPermissions($context['docente'], ['notas:create']);

        $this->actingAs($teacher)
            ->post('/examenes/notas/generar-prueba', [
                'id_grupo' => $context['grupo']->id_grupo,
                'id_materia' => $context['materia']->id_materia,
                'nota_minima' => 50,
                'nota_maxima' => 95,
            ])
            ->assertSessionHasErrors('notas');

        $this->assertSame(0, Nota::count());
    }

    private function userWithPermissions(array $permissions): User
    {
        $this->seed(AccessControlSeeder::class);

        $user = User::factory()->create();
        $role = Rol::create([
            'nombre' => 'ROL_NOTAS_'.Str::upper(Str::random(8)),
            'descripcion' => 'Rol de prueba para notas',
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

    private function teacherUserWithPermissions(Docente $docente, array $permissions): User
    {
        $this->seed(AccessControlSeeder::class);
        $docenteRole = Rol::where('nombre', 'DOCENTE')->firstOrFail();
        $permissionIds = Permiso::whereIn('nombre', $permissions)->pluck('id_permiso')->all();

        $docenteRole->permisos()->syncWithoutDetaching(
            collect($permissionIds)->mapWithKeys(fn ($id) => [
                $id => [
                    'activo' => true,
                    'fecha_asignacion' => now(),
                ],
            ])->all(),
        );
        $docente->usuario->roles()->syncWithoutDetaching([
            $docenteRole->id_rol => [
                'activo' => true,
                'fecha_asignacion' => now(),
            ],
        ]);

        return $docente->usuario->fresh();
    }

    private function createContext(): array
    {
        $gestion = $this->createGestion();
        $grupo = GrupoAcademico::create([
            'id_gestion' => $gestion->id_gestion,
            'nombre' => 'Grupo '.Str::upper(Str::random(6)),
            'turno' => 'MANANA',
            'capacidad_maxima' => 70,
            'activo' => true,
        ]);
        $materia = $this->createMateria();
        $docente = $this->createDocente();
        $this->habilitateDocenteForMateria($docente, $materia);
        $aula = Aula::create([
            'nombre' => 'Aula '.Str::upper(Str::random(6)),
            'capacidad' => 70,
        ]);
        $horario = $this->createHorario();
        $asignacion = AsignacionAcademica::create([
            'id_grupo' => $grupo->id_grupo,
            'id_materia' => $materia->id_materia,
            'id_docente' => $docente->id_docente,
            'id_aula' => $aula->id_aula,
            'id_horario' => $horario->id_horario,
            'activo' => true,
        ]);
        $postulacion = $this->createPostulacion($gestion, $grupo);

        return compact('gestion', 'grupo', 'materia', 'docente', 'aula', 'horario', 'asignacion', 'postulacion');
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

    private function createMateria(?string $nombre = null): MateriaCup
    {
        return MateriaCup::create([
            'nombre' => $nombre ?? 'Materia '.Str::upper(Str::random(6)),
            'activo' => true,
        ]);
    }

    private function createHorario(string $turno = 'MANANA', string $inicio = '08:00', string $fin = '09:00'): Horario
    {
        return Horario::firstOrCreate(
            [
                'turno' => $turno,
                'hora_inicio' => $inicio,
                'hora_fin' => $fin,
            ],
            ['activo' => true],
        );
    }

    private function createDocente(): Docente
    {
        $user = User::factory()->create();

        return Docente::create([
            'id_usuario' => $user->id_usuario,
            'profesional_area' => true,
            'maestria' => true,
            'diplomado_educacion_superior' => true,
            'maestria_educacion_superior' => true,
            'contratado' => true,
            'activo' => true,
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

    private function createPostulacion(GestionAcademica $gestion, GrupoAcademico $grupo): Postulacion
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
            'id_gestion' => $gestion->id_gestion,
            'id_carrera_opcion1' => $carreraId,
            'id_grupo' => $grupo->id_grupo,
            'estado_admision' => 'PENDIENTE',
            'fecha_postulacion' => now(),
        ], 'id_postulacion');

        return Postulacion::findOrFail($postulacionId);
    }

    private function notaPayload(array $context, array $overrides = []): array
    {
        return array_merge([
            'id_postulacion' => $context['postulacion']->id_postulacion,
            'id_materia' => $context['materia']->id_materia,
            'nro_examen' => 1,
            'nota' => 80,
        ], $overrides);
    }

    private function createNota(array $context, array $overrides = []): Nota
    {
        return Nota::create($this->notaPayload($context, array_merge([
            'registrado_por' => $context['docente']->id_usuario ?? null,
        ], $overrides)));
    }

    private function createFullNotas(array $context, int $score): void
    {
        $materias = [$context['materia']];

        for ($index = 1; $index <= 3; $index++) {
            $materias[] = $this->createMateria("Materia {$score} {$index}");
        }

        foreach ($materias as $materia) {
            foreach ([1, 2, 3] as $exam) {
                Nota::create([
                    'id_postulacion' => $context['postulacion']->id_postulacion,
                    'id_materia' => $materia->id_materia,
                    'nro_examen' => $exam,
                    'nota' => $score,
                    'registrado_por' => null,
                ]);
            }
        }
    }
}
