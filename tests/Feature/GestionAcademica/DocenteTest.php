<?php

namespace Tests\Feature\GestionAcademica;

use App\Modules\AccesoSeguridad\Models\Permiso;
use App\Modules\AccesoSeguridad\Models\Rol;
use App\Modules\AccesoSeguridad\Models\User;
use App\Modules\GestionAcademica\Models\Docente;
use App\Modules\GestionAcademica\Models\GestionAcademica;
use App\Modules\GestionAcademica\Models\GrupoAcademico;
use App\Modules\GestionAcademica\Models\MateriaCup;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class DocenteTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/academico/docentes')->assertRedirect('/login');
    }

    public function test_user_without_docentes_read_receives_forbidden(): void
    {
        $this->seed(AccessControlSeeder::class);

        $user = User::factory()->create();

        $this->actingAs($user)->get('/academico/docentes')->assertForbidden();
    }

    public function test_user_with_docentes_read_can_list_docentes(): void
    {
        $this->createDocente();
        $user = $this->userWithPermissions(['docentes:read']);

        $this->actingAs($user)->get('/academico/docentes')->assertOk();
    }

    public function test_user_with_docentes_create_can_create_docente_and_usuario(): void
    {
        $user = $this->userWithPermissions(['docentes:create']);

        $this->actingAs($user)
            ->post('/academico/docentes', $this->validDocentePayload([
                'ci' => 'DOC-100',
                'username' => 'docente100',
                'correo' => 'docente100@example.com',
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('usuario', [
            'ci' => 'DOC-100',
            'username' => 'docente100',
            'correo' => 'docente100@example.com',
        ]);
        $this->assertDatabaseHas('docente', [
            'contratado' => false,
            'activo' => true,
        ]);
    }

    public function test_creating_docente_assigns_docente_role_when_it_exists(): void
    {
        $this->seed(AccessControlSeeder::class);
        $user = $this->userWithPermissions(['docentes:create']);

        $this->actingAs($user)
            ->post('/academico/docentes', $this->validDocentePayload([
                'ci' => 'DOC-101',
                'username' => 'docente101',
                'correo' => 'docente101@example.com',
            ]))
            ->assertRedirect();

        $docenteUser = User::where('username', 'docente101')->firstOrFail();
        $docenteRole = Rol::where('nombre', 'DOCENTE')->firstOrFail();

        $this->assertDatabaseHas('rol_usuario', [
            'id_usuario' => $docenteUser->id_usuario,
            'id_rol' => $docenteRole->id_rol,
            'activo' => true,
        ]);
    }

    public function test_cannot_contract_docente_without_all_requirements(): void
    {
        $user = $this->userWithPermissions(['docentes:create']);

        $this->actingAs($user)
            ->post('/academico/docentes', $this->validDocentePayload([
                'contratado' => true,
                'maestria_educacion_superior' => false,
            ]))
            ->assertSessionHasErrors('maestria_educacion_superior');

        $this->assertDatabaseMissing('docente', [
            'contratado' => true,
        ]);
    }

    public function test_user_with_docentes_update_can_update_docente(): void
    {
        $docente = $this->createDocente();
        $user = $this->userWithPermissions(['docentes:update']);

        $this->actingAs($user)
            ->put("/academico/docentes/{$docente->id_docente}", $this->validDocentePayload([
                'ci' => 'DOC-200',
                'nombre' => 'Maria',
                'apellido' => 'Vargas',
                'username' => 'maria.vargas',
                'correo' => 'maria.vargas@example.com',
                'password' => '',
                'password_confirmation' => '',
                'estado_acceso' => 'HABILITADO',
                'usuario_activo' => true,
                'profesional_area' => true,
                'maestria' => true,
                'diplomado_educacion_superior' => true,
                'contratado' => true,
                'activo' => true,
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('usuario', [
            'id_usuario' => $docente->id_usuario,
            'ci' => 'DOC-200',
            'username' => 'maria.vargas',
            'correo' => 'maria.vargas@example.com',
        ]);
        $this->assertDatabaseHas('docente', [
            'id_docente' => $docente->id_docente,
            'contratado' => true,
        ]);
    }

    public function test_empty_password_on_update_does_not_change_password_hash(): void
    {
        $docente = $this->createDocente();
        $oldHash = $docente->usuario->password_hash;
        $user = $this->userWithPermissions(['docentes:update']);

        $this->actingAs($user)
            ->put("/academico/docentes/{$docente->id_docente}", $this->validDocentePayload([
                'ci' => $docente->usuario->ci,
                'nombre' => $docente->usuario->nombre,
                'apellido' => $docente->usuario->apellido,
                'username' => $docente->usuario->username,
                'correo' => $docente->usuario->correo,
                'password' => '',
                'password_confirmation' => '',
                'estado_acceso' => 'HABILITADO',
                'usuario_activo' => true,
                'activo' => true,
            ]))
            ->assertRedirect();

        $this->assertSame($oldHash, $docente->usuario->fresh()->password_hash);
    }

    public function test_password_on_update_changes_password_hash(): void
    {
        $docente = $this->createDocente();
        $oldHash = $docente->usuario->password_hash;
        $user = $this->userWithPermissions(['docentes:update']);

        $this->actingAs($user)
            ->put("/academico/docentes/{$docente->id_docente}", $this->validDocentePayload([
                'ci' => $docente->usuario->ci,
                'nombre' => $docente->usuario->nombre,
                'apellido' => $docente->usuario->apellido,
                'username' => $docente->usuario->username,
                'correo' => $docente->usuario->correo,
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
                'estado_acceso' => 'HABILITADO',
                'usuario_activo' => true,
                'activo' => true,
            ]))
            ->assertRedirect();

        $newHash = $docente->usuario->fresh()->password_hash;

        $this->assertNotSame($oldHash, $newHash);
        $this->assertTrue(Hash::check('new-password', $newHash));
    }

    public function test_user_with_docentes_delete_can_deactivate_docente_without_physical_delete(): void
    {
        $docente = $this->createDocente();
        $user = $this->userWithPermissions(['docentes:delete']);

        $this->actingAs($user)
            ->patch("/academico/docentes/{$docente->id_docente}/toggle")
            ->assertRedirect();

        $this->assertDatabaseHas('docente', [
            'id_docente' => $docente->id_docente,
            'activo' => false,
        ]);
        $this->assertSame(1, Docente::whereKey($docente->id_docente)->count());
    }

    public function test_docente_with_active_assignment_cannot_be_deactivated(): void
    {
        $docente = $this->createDocente();
        $this->createActiveAssignmentForDocente($docente);
        $user = $this->userWithPermissions(['docentes:delete']);

        $this->actingAs($user)
            ->patch("/academico/docentes/{$docente->id_docente}/toggle")
            ->assertSessionHasErrors('docente');

        $this->assertDatabaseHas('docente', [
            'id_docente' => $docente->id_docente,
            'activo' => true,
        ]);
    }

    private function userWithPermissions(array $permissions): User
    {
        $this->seed(AccessControlSeeder::class);

        $user = User::factory()->create();
        $role = Rol::create([
            'nombre' => 'ROL_DOCENTES_'.Str::upper(Str::random(8)),
            'descripcion' => 'Rol de prueba para docentes',
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

    private function createDocente(array $overrides = []): Docente
    {
        $user = User::factory()->create([
            'ci' => $overrides['ci'] ?? fake()->unique()->numerify('########'),
            'nombre' => $overrides['nombre'] ?? 'Docente',
            'apellido' => $overrides['apellido'] ?? 'Prueba',
            'username' => $overrides['username'] ?? fake()->unique()->userName(),
            'correo' => $overrides['correo'] ?? fake()->unique()->safeEmail(),
        ]);

        return Docente::create([
            'id_usuario' => $user->id_usuario,
            'profesional_area' => $overrides['profesional_area'] ?? true,
            'maestria' => $overrides['maestria'] ?? true,
            'diplomado_educacion_superior' => $overrides['diplomado_educacion_superior'] ?? true,
            'maestria_educacion_superior' => $overrides['maestria_educacion_superior'] ?? true,
            'contratado' => $overrides['contratado'] ?? false,
            'activo' => $overrides['activo'] ?? true,
        ])->load('usuario');
    }

    private function validDocentePayload(array $overrides = []): array
    {
        $materia = MateriaCup::create([
            'nombre' => 'Materia '.Str::upper(Str::random(6)),
            'activo' => true,
        ]);

        return array_merge([
            'ci' => fake()->unique()->numerify('########'),
            'nombre' => 'Docente',
            'apellido' => 'Prueba',
            'username' => 'docente_'.Str::lower(Str::random(8)),
            'correo' => 'docente_'.Str::lower(Str::random(8)).'@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'telefono' => '70000000',
            'sexo' => 'O',
            'estado_acceso' => 'HABILITADO',
            'usuario_activo' => true,
            'profesional_area' => true,
            'maestria' => true,
            'diplomado_educacion_superior' => true,
            'maestria_educacion_superior' => true,
            'habilitaciones' => [
                'PROFESIONAL_AREA' => [$materia->id_materia],
                'DIPLOMADO' => [],
                'MAESTRIA' => [],
            ],
            'contratado' => false,
            'activo' => true,
        ], $overrides);
    }

    private function createActiveAssignmentForDocente(Docente $docente): void
    {
        $gestion = GestionAcademica::create([
            'nombre' => 'CUP '.Str::upper(Str::random(6)),
            'fecha_inicio' => '2026-01-01',
            'fecha_fin' => '2026-12-31',
            'activo' => true,
        ]);
        $grupo = GrupoAcademico::create([
            'id_gestion' => $gestion->id_gestion,
            'nombre' => 'Grupo '.Str::upper(Str::random(4)),
            'capacidad_maxima' => 70,
            'activo' => true,
        ]);
        $materia = MateriaCup::create([
            'nombre' => 'Materia '.Str::upper(Str::random(6)),
            'activo' => true,
        ]);
        $aulaId = DB::table('aula')->insertGetId([
            'nombre' => 'Aula '.Str::upper(Str::random(6)),
            'capacidad' => 70,
        ], 'id_aula');
        $horarioId = DB::table('horario')->insertGetId([
            'dia' => 'LUNES',
            'hora_inicio' => '08:00',
            'hora_fin' => '10:00',
        ], 'id_horario');

        DB::table('asignacion_academica')->insert([
            'id_grupo' => $grupo->id_grupo,
            'id_materia' => $materia->id_materia,
            'id_docente' => $docente->id_docente,
            'id_aula' => $aulaId,
            'id_horario' => $horarioId,
            'activo' => true,
        ]);
    }
}
