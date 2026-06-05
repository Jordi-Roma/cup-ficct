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

class AulaTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/academico/aulas')->assertRedirect('/login');
    }

    public function test_user_without_aulas_read_receives_forbidden(): void
    {
        $this->seed(AccessControlSeeder::class);

        $this->actingAs(User::factory()->create())->get('/academico/aulas')->assertForbidden();
    }

    public function test_user_with_aulas_read_can_list_aulas(): void
    {
        Aula::create(['nombre' => 'Aula Test', 'capacidad' => 70, 'activo' => true]);

        $response = $this->actingAs($this->userWithPermissions(['aulas:read']))->get('/academico/aulas');

        $response->assertOk();
        $this->assertSame('Aula Test', $response->viewData('page')['props']['aulas'][0]['nombre']);
    }

    public function test_user_with_aulas_create_can_create_aula(): void
    {
        $this->actingAs($this->userWithPermissions(['aulas:create']))
            ->post('/academico/aulas', ['nombre' => 'Aula Nueva', 'capacidad' => 80])
            ->assertRedirect();

        $this->assertDatabaseHas('aula', [
            'nombre' => 'Aula Nueva',
            'capacidad' => 80,
            'activo' => true,
        ]);
    }

    public function test_capacity_must_be_greater_than_zero(): void
    {
        $this->actingAs($this->userWithPermissions(['aulas:create']))
            ->post('/academico/aulas', ['nombre' => 'Aula Mala', 'capacidad' => 0])
            ->assertSessionHasErrors('capacidad');
    }

    public function test_name_must_be_unique(): void
    {
        Aula::create(['nombre' => 'Aula Duplicada', 'capacidad' => 70, 'activo' => true]);

        $this->actingAs($this->userWithPermissions(['aulas:create']))
            ->post('/academico/aulas', ['nombre' => 'Aula Duplicada', 'capacidad' => 60])
            ->assertSessionHasErrors('nombre');
    }

    public function test_user_with_aulas_update_can_update_aula(): void
    {
        $aula = Aula::create(['nombre' => 'Aula Vieja', 'capacidad' => 70, 'activo' => true]);

        $this->actingAs($this->userWithPermissions(['aulas:update']))
            ->put("/academico/aulas/{$aula->id_aula}", ['nombre' => 'Aula Editada', 'capacidad' => 90])
            ->assertRedirect();

        $this->assertDatabaseHas('aula', ['id_aula' => $aula->id_aula, 'nombre' => 'Aula Editada', 'capacidad' => 90]);
    }

    public function test_user_with_aulas_delete_can_deactivate_without_physical_delete(): void
    {
        $aula = Aula::create(['nombre' => 'Aula Libre', 'capacidad' => 70, 'activo' => true]);

        $this->actingAs($this->userWithPermissions(['aulas:delete']))
            ->patch("/academico/aulas/{$aula->id_aula}/toggle")
            ->assertRedirect();

        $this->assertDatabaseHas('aula', ['id_aula' => $aula->id_aula, 'activo' => false]);
        $this->assertSame(1, Aula::whereKey($aula->id_aula)->count());
    }

    public function test_cannot_deactivate_aula_with_active_assignments(): void
    {
        $asignacion = $this->createAsignacion();

        $this->actingAs($this->userWithPermissions(['aulas:delete']))
            ->patch("/academico/aulas/{$asignacion->id_aula}/toggle")
            ->assertSessionHasErrors('aula');

        $this->assertDatabaseHas('aula', ['id_aula' => $asignacion->id_aula, 'activo' => true]);
    }

    private function userWithPermissions(array $permissions): User
    {
        $this->seed(AccessControlSeeder::class);

        $user = User::factory()->create();
        $role = Rol::create([
            'nombre' => 'ROL_AULAS_'.Str::upper(Str::random(8)),
            'descripcion' => 'Rol de prueba para aulas',
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
