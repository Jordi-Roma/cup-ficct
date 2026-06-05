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
use Illuminate\Support\Str;
use Tests\TestCase;

class HorarioTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/academico/horarios')->assertRedirect('/login');
    }

    public function test_user_without_horarios_read_receives_forbidden(): void
    {
        $this->seed(AccessControlSeeder::class);

        $this->actingAs(User::factory()->create())->get('/academico/horarios')->assertForbidden();
    }

    public function test_user_with_horarios_read_can_list_horarios(): void
    {
        Horario::create(['dia' => 'LUNES', 'hora_inicio' => '08:00', 'hora_fin' => '10:00', 'activo' => true]);

        $response = $this->actingAs($this->userWithPermissions(['horarios:read']))->get('/academico/horarios');

        $response->assertOk();
        $this->assertSame('LUNES', $response->viewData('page')['props']['horarios'][0]['dia']);
    }

    public function test_user_with_horarios_create_can_create_horario(): void
    {
        $this->actingAs($this->userWithPermissions(['horarios:create']))
            ->post('/academico/horarios', ['dia' => 'MARTES', 'hora_inicio' => '08:00', 'hora_fin' => '10:00'])
            ->assertRedirect();

        $this->assertDatabaseHas('horario', [
            'dia' => 'MARTES',
            'hora_inicio' => '08:00',
            'hora_fin' => '10:00',
            'activo' => true,
        ]);
    }

    public function test_day_must_be_valid(): void
    {
        $this->actingAs($this->userWithPermissions(['horarios:create']))
            ->post('/academico/horarios', ['dia' => 'DOMINGO', 'hora_inicio' => '08:00', 'hora_fin' => '10:00'])
            ->assertSessionHasErrors('dia');
    }

    public function test_end_time_must_be_after_start_time(): void
    {
        $this->actingAs($this->userWithPermissions(['horarios:create']))
            ->post('/academico/horarios', ['dia' => 'LUNES', 'hora_inicio' => '10:00', 'hora_fin' => '08:00'])
            ->assertSessionHasErrors('hora_fin');
    }

    public function test_cannot_duplicate_day_start_and_end_time(): void
    {
        Horario::create(['dia' => 'LUNES', 'hora_inicio' => '08:00', 'hora_fin' => '10:00', 'activo' => true]);

        $this->actingAs($this->userWithPermissions(['horarios:create']))
            ->post('/academico/horarios', ['dia' => 'LUNES', 'hora_inicio' => '08:00', 'hora_fin' => '10:00'])
            ->assertSessionHasErrors('hora_inicio');
    }

    public function test_user_with_horarios_update_can_update_horario(): void
    {
        $horario = Horario::create(['dia' => 'LUNES', 'hora_inicio' => '08:00', 'hora_fin' => '10:00', 'activo' => true]);

        $this->actingAs($this->userWithPermissions(['horarios:update']))
            ->put("/academico/horarios/{$horario->id_horario}", ['dia' => 'MIERCOLES', 'hora_inicio' => '09:00', 'hora_fin' => '11:00'])
            ->assertRedirect();

        $this->assertDatabaseHas('horario', [
            'id_horario' => $horario->id_horario,
            'dia' => 'MIERCOLES',
            'hora_inicio' => '09:00',
            'hora_fin' => '11:00',
        ]);
    }

    public function test_user_with_horarios_delete_can_deactivate_without_physical_delete(): void
    {
        $horario = Horario::create(['dia' => 'LUNES', 'hora_inicio' => '08:00', 'hora_fin' => '10:00', 'activo' => true]);

        $this->actingAs($this->userWithPermissions(['horarios:delete']))
            ->patch("/academico/horarios/{$horario->id_horario}/toggle")
            ->assertRedirect();

        $this->assertDatabaseHas('horario', ['id_horario' => $horario->id_horario, 'activo' => false]);
        $this->assertSame(1, Horario::whereKey($horario->id_horario)->count());
    }

    public function test_cannot_deactivate_horario_with_active_assignments(): void
    {
        $asignacion = $this->createAsignacion();

        $this->actingAs($this->userWithPermissions(['horarios:delete']))
            ->patch("/academico/horarios/{$asignacion->id_horario}/toggle")
            ->assertSessionHasErrors('horario');

        $this->assertDatabaseHas('horario', ['id_horario' => $asignacion->id_horario, 'activo' => true]);
    }

    private function userWithPermissions(array $permissions): User
    {
        $this->seed(AccessControlSeeder::class);

        $user = User::factory()->create();
        $role = Rol::create([
            'nombre' => 'ROL_HORARIOS_'.Str::upper(Str::random(8)),
            'descripcion' => 'Rol de prueba para horarios',
            'activo' => true,
        ]);
        $permissionIds = Permiso::whereIn('nombre', $permissions)->pluck('id_permiso')->all();

        $role->permisos()->attach($permissionIds, ['activo' => true, 'fecha_asignacion' => now()]);
        $user->roles()->attach($role->id_rol, ['activo' => true, 'fecha_asignacion' => now()]);

        return $user;
    }

    private function createAsignacion(): AsignacionAcademica
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
        $materia = MateriaCup::create(['nombre' => 'Materia '.Str::upper(Str::random(6)), 'activo' => true]);
        $docente = Docente::create([
            'id_usuario' => User::factory()->create()->id_usuario,
            'profesional_area' => true,
            'maestria' => false,
            'diplomado_educacion_superior' => false,
            'maestria_educacion_superior' => true,
            'contratado' => true,
            'activo' => true,
        ]);
        DocenteHabilitacionMateria::create([
            'id_docente' => $docente->id_docente,
            'id_materia' => $materia->id_materia,
            'tipo_habilitacion' => DocenteHabilitacionMateria::PROFESIONAL_AREA,
            'activo' => true,
        ]);
        $aula = Aula::create(['nombre' => 'Aula '.Str::upper(Str::random(6)), 'capacidad' => 70, 'activo' => true]);
        $horario = Horario::create(['dia' => 'LUNES', 'hora_inicio' => '08:00', 'hora_fin' => '10:00', 'activo' => true]);

        return AsignacionAcademica::create([
            'id_grupo' => $grupo->id_grupo,
            'id_materia' => $materia->id_materia,
            'id_docente' => $docente->id_docente,
            'id_aula' => $aula->id_aula,
            'id_horario' => $horario->id_horario,
            'activo' => true,
        ]);
    }
}
