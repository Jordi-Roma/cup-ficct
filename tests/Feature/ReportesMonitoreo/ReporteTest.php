<?php

namespace Tests\Feature\ReportesMonitoreo;

use App\Modules\AccesoSeguridad\Models\Permiso;
use App\Modules\AccesoSeguridad\Models\Rol;
use App\Modules\AccesoSeguridad\Models\User;
use App\Modules\Examenes\Models\Nota;
use App\Modules\GestionAcademica\Models\AsignacionAcademica;
use App\Modules\GestionAcademica\Models\Aula;
use App\Modules\GestionAcademica\Models\Docente;
use App\Modules\GestionAcademica\Models\DocenteHabilitacionMateria;
use App\Modules\GestionAcademica\Models\GestionAcademica;
use App\Modules\GestionAcademica\Models\GrupoAcademico;
use App\Modules\GestionAcademica\Models\Horario;
use App\Modules\GestionAcademica\Models\MateriaCup;
use App\Modules\RegistroPostulantes\Models\Postulante;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReporteTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/reportes')->assertRedirect('/login');
    }

    public function test_user_without_reportes_read_receives_forbidden(): void
    {
        $this->seed(AccessControlSeeder::class);
        $user = User::factory()->create();

        $this->actingAs($user)->get('/reportes')->assertForbidden();
    }

    public function test_user_with_reportes_read_can_load_reportes(): void
    {
        $this->createContext();
        $user = $this->userWithPermissions(['reportes:read']);

        $this->actingAs($user)->get('/reportes')->assertOk();
    }

    public function test_reportes_exposes_dynamic_filter_options(): void
    {
        $context = $this->createContext();
        $user = $this->userWithPermissions(['reportes:read']);

        $response = $this->actingAs($user)->get('/reportes');
        $props = $response->viewData('page')['props'];

        $this->assertSame($context['gestion']->id_gestion, $props['filters']['id_gestion']);
        $this->assertTrue(collect($props['options']['grupos'])->contains('id_grupo', $context['grupo']->id_grupo));
        $this->assertTrue(collect($props['options']['materias'])->contains('id_materia', $context['materias'][0]->id_materia));
        $this->assertTrue(collect($props['options']['estados'])->contains('value', 'ADMITIDO'));
    }

    public function test_can_filter_reportes_by_group(): void
    {
        $context = $this->createContext();
        $otherGroup = GrupoAcademico::create([
            'id_gestion' => $context['gestion']->id_gestion,
            'nombre' => 'Grupo Filtro '.Str::upper(Str::random(6)),
            'turno' => 'TARDE',
            'capacidad_maxima' => 70,
            'activo' => true,
        ]);
        $other = $this->createPostulante($context['gestion'], $otherGroup);
        $this->createFullNotas($other['postulacion_id'], $context['materias'], 90);
        $user = $this->userWithPermissions(['reportes:read']);

        $response = $this->actingAs($user)->get('/reportes?id_grupo='.$otherGroup->id_grupo);
        $lista = $response->viewData('page')['props']['listaGeneral'];

        $this->assertCount(1, $lista);
        $this->assertSame($otherGroup->nombre, $lista[0]['grupo']);
    }

    public function test_can_filter_reportes_by_materia(): void
    {
        $context = $this->createContext();
        $user = $this->userWithPermissions(['reportes:read']);

        $response = $this->actingAs($user)->get('/reportes?id_materia='.$context['materias'][0]->id_materia);
        $stats = $response->viewData('page')['props']['estadisticasPorMateria'];

        $this->assertCount(1, $stats);
        $this->assertSame($context['materias'][0]->id_materia, $stats[0]['id_materia']);
    }

    public function test_can_filter_reportes_by_estado_admitido(): void
    {
        $context = $this->createContext();
        DB::table('postulacion')
            ->where('id_postulacion', $context['approved']['postulacion_id'])
            ->update(['estado_admision' => 'ADMITIDO']);
        $user = $this->userWithPermissions(['reportes:read']);

        $response = $this->actingAs($user)->get('/reportes?estado=ADMITIDO');
        $lista = $response->viewData('page')['props']['listaGeneral'];

        $this->assertCount(1, $lista);
        $this->assertSame('ADMITIDO', $lista[0]['estado_admision']);
    }

    public function test_lista_general_includes_postulante_carrera_and_estado(): void
    {
        $context = $this->createContext();
        $user = $this->userWithPermissions(['reportes:read']);

        $response = $this->actingAs($user)->get('/reportes');
        $lista = $response->viewData('page')['props']['listaGeneral'];

        $this->assertSame($context['approved']['user']->ci, $lista[0]['ci']);
        $this->assertSame($context['approved']['carrera1'], $lista[0]['carrera_opcion1']);
        $this->assertSame('PENDIENTE', $lista[0]['estado_admision']);
    }

    public function test_reporte_aprobados_includes_complete_average_sixty_or_more(): void
    {
        $context = $this->createContext();
        $user = $this->userWithPermissions(['reportes:read']);

        $response = $this->actingAs($user)->get('/reportes');
        $aprobados = $response->viewData('page')['props']['aprobados'];

        $this->assertCount(1, $aprobados);
        $this->assertSame($context['approved']['user']->ci, $aprobados[0]['ci']);
        $this->assertSame(75.0, $aprobados[0]['promedio_final']);
    }

    public function test_reporte_reprobados_includes_complete_average_lower_than_sixty(): void
    {
        $context = $this->createContext();
        $user = $this->userWithPermissions(['reportes:read']);

        $response = $this->actingAs($user)->get('/reportes');
        $reprobados = $response->viewData('page')['props']['reprobados'];

        $this->assertCount(1, $reprobados);
        $this->assertSame($context['failed']['user']->ci, $reprobados[0]['ci']);
        $this->assertSame(55.0, $reprobados[0]['promedio_final']);
    }

    public function test_incomplete_notes_are_pending(): void
    {
        $context = $this->createContext();
        $user = $this->userWithPermissions(['reportes:read']);

        $response = $this->actingAs($user)->get('/reportes');
        $lista = collect($response->viewData('page')['props']['listaGeneral']);
        $pending = $lista->firstWhere('ci', $context['pending']['user']->ci);

        $this->assertSame('PENDIENTE', $pending['estado_final']);
    }

    public function test_materia_statistics_calculate_average_approved_and_failed(): void
    {
        $user = $this->userWithPermissions(['reportes:read']);
        $this->createContext();

        $response = $this->actingAs($user)->get('/reportes');
        $stats = $response->viewData('page')['props']['estadisticasPorMateria'][0];

        $this->assertSame(69.29, $stats['promedio']);
        $this->assertSame(1, $stats['aprobados']);
        $this->assertSame(1, $stats['reprobados']);
    }

    public function test_grupos_show_capacity_assigned_and_available_slots(): void
    {
        $user = $this->userWithPermissions(['reportes:read']);
        $this->createContext();

        $response = $this->actingAs($user)->get('/reportes');
        $grupo = $response->viewData('page')['props']['grupos'][0];

        $this->assertSame(70, $grupo['capacidad_maxima']);
        $this->assertSame(3, $grupo['postulantes_asignados']);
        $this->assertSame(67, $grupo['cupos_disponibles']);
    }

    public function test_docentes_por_grupo_shows_assignment_data(): void
    {
        $user = $this->userWithPermissions(['reportes:read']);
        $context = $this->createContext();

        $response = $this->actingAs($user)->get('/reportes');
        $assignment = $response->viewData('page')['props']['docentesPorGrupo'][0];

        $this->assertSame($context['grupo']->nombre, $assignment['grupo']);
        $this->assertSame($context['materias'][0]->nombre, $assignment['materia']);
        $this->assertSame($context['docente']->usuario->name, $assignment['docente']);
        $this->assertSame($context['aula']->nombre, $assignment['aula']);
    }

    public function test_groups_with_most_approved_are_ordered(): void
    {
        $user = $this->userWithPermissions(['reportes:read']);
        $context = $this->createContext();

        $response = $this->actingAs($user)->get('/reportes');
        $ranking = $response->viewData('page')['props']['gruposConMasAprobados'];

        $this->assertSame($context['grupo']->nombre, $ranking[0]['grupo']);
        $this->assertSame(1, $ranking[0]['aprobados']);
    }

    public function test_user_without_reportes_export_cannot_export(): void
    {
        $this->createContext();
        $user = $this->userWithPermissions(['reportes:read']);

        $this->actingAs($user)->get('/reportes/export/postulantes')->assertForbidden();
    }

    public function test_user_with_reportes_export_can_export_excel_compatible_csv(): void
    {
        $context = $this->createContext();
        $user = $this->userWithPermissions(['reportes:read', 'reportes:export']);

        $response = $this->actingAs($user)->get('/reportes/export/postulantes');
        $content = $response->streamedContent();

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('reporte-postulantes.csv', $response->headers->get('content-disposition'));
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString('CI;"Nombre completo";Correo', $content);
        $this->assertStringContainsString($context['approved']['user']->ci, $content);
    }

    public function test_export_csv_respects_group_filter(): void
    {
        $context = $this->createContext();
        $otherGroup = GrupoAcademico::create([
            'id_gestion' => $context['gestion']->id_gestion,
            'nombre' => 'Grupo Export '.Str::upper(Str::random(6)),
            'turno' => 'NOCHE',
            'capacidad_maxima' => 70,
            'activo' => true,
        ]);
        $other = $this->createPostulante($context['gestion'], $otherGroup);
        $this->createFullNotas($other['postulacion_id'], $context['materias'], 88);
        $user = $this->userWithPermissions(['reportes:read', 'reportes:export']);

        $response = $this->actingAs($user)->get('/reportes/export/postulantes?id_grupo='.$otherGroup->id_grupo);
        $content = $response->streamedContent();

        $response->assertOk();
        $this->assertStringContainsString($other['user']->ci, $content);
        $this->assertStringNotContainsString($context['approved']['user']->ci, $content);
    }

    public function test_export_csv_does_not_modify_data(): void
    {
        $this->createContext();
        $user = $this->userWithPermissions(['reportes:read', 'reportes:export']);
        $notesBefore = Nota::count();
        $postulacionesBefore = DB::table('postulacion')->count();

        $this->actingAs($user)->get('/reportes/export/postulantes')->assertOk();

        $this->assertSame($notesBefore, Nota::count());
        $this->assertSame($postulacionesBefore, DB::table('postulacion')->count());
    }

    private function userWithPermissions(array $permissions): User
    {
        $this->seed(AccessControlSeeder::class);

        $user = User::factory()->create();
        $role = Rol::create([
            'nombre' => 'ROL_REPORTES_'.Str::upper(Str::random(8)),
            'descripcion' => 'Rol de prueba para reportes',
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
        GestionAcademica::query()->update(['activo' => false]);

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
        $materias = collect(range(1, 4))
            ->map(fn (int $index) => MateriaCup::create([
                'nombre' => 'Materia '.$index.' '.Str::upper(Str::random(5)),
                'activo' => true,
            ]))
            ->values();
        $docente = $this->createDocente();
        $this->habilitateDocenteForMateria($docente, $materias[0]);
        $aula = Aula::create([
            'nombre' => 'Aula '.Str::upper(Str::random(6)),
            'capacidad' => 70,
        ]);
        $horario = Horario::query()
            ->where('turno', 'MANANA')
            ->where('hora_inicio', '07:00')
            ->where('hora_fin', '08:00')
            ->first()
            ?? Horario::create([
                'turno' => 'MANANA',
                'hora_inicio' => '06:00',
                'hora_fin' => '07:00',
                'activo' => true,
            ]);
        AsignacionAcademica::create([
            'id_grupo' => $grupo->id_grupo,
            'id_materia' => $materias[0]->id_materia,
            'id_docente' => $docente->id_docente,
            'id_aula' => $aula->id_aula,
            'id_horario' => $horario->id_horario,
            'activo' => true,
        ]);

        $approved = $this->createPostulante($gestion, $grupo);
        $failed = $this->createPostulante($gestion, $grupo);
        $pending = $this->createPostulante($gestion, $grupo);
        $this->createFullNotas($approved['postulacion_id'], $materias, 75);
        $this->createFullNotas($failed['postulacion_id'], $materias, 55);
        $this->createNota($pending['postulacion_id'], $materias[0], 95, 1);

        return compact('gestion', 'grupo', 'materias', 'docente', 'aula', 'horario', 'approved', 'failed', 'pending');
    }

    private function createDocente(): Docente
    {
        return Docente::create([
            'id_usuario' => User::factory()->create()->id_usuario,
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

    private function createPostulante(GestionAcademica $gestion, GrupoAcademico $grupo): array
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
            'user' => $user,
            'postulante' => $postulante,
            'postulacion_id' => $postulacionId,
            'carrera1' => DB::table('carrera')->where('id_carrera', $carrera1)->value('nombre'),
        ];
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
