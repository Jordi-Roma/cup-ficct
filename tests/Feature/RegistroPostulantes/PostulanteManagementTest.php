<?php

namespace Tests\Feature\RegistroPostulantes;

use App\Modules\Autenticacion\Models\Permiso;
use App\Modules\Autenticacion\Models\Rol;
use App\Modules\Autenticacion\Models\User;
use App\Modules\GestionAcademica\Models\Carrera;
use App\Modules\GestionAcademica\Models\GestionAcademica;
use App\Modules\RegistroPostulantes\Models\Postulacion;
use App\Modules\RegistroPostulantes\Models\Postulante;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PostulanteManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/postulantes')->assertRedirect('/login');
    }

    public function test_user_without_postulantes_read_receives_forbidden(): void
    {
        $this->seed(AccessControlSeeder::class);

        $user = User::factory()->create();

        $this->actingAs($user)->get('/postulantes')->assertForbidden();
    }

    public function test_user_with_postulantes_read_can_list_postulantes(): void
    {
        $this->createPostulante();
        $user = $this->userWithPermissions(['postulantes:read']);

        $this->actingAs($user)->get('/postulantes')->assertOk();
    }

    public function test_search_filter_finds_ci_name_and_email(): void
    {
        $this->createPostulante([
            'ci' => '12345678',
            'nombre' => 'Ana',
            'apellido' => 'Rojas',
            'correo' => 'ana.rojas@example.com',
        ]);
        $this->createPostulante([
            'ci' => '87654321',
            'nombre' => 'Luis',
            'apellido' => 'Paz',
            'correo' => 'luis.paz@example.com',
        ]);
        $user = $this->userWithPermissions(['postulantes:read']);

        $response = $this->actingAs($user)->get('/postulantes?search=Ana');

        $response->assertOk();
        $postulantes = $response->viewData('page')['props']['postulantes'];

        $this->assertCount(1, $postulantes);
        $this->assertSame('Ana', $postulantes[0]['nombre']);
    }

    public function test_user_with_postulantes_update_can_update_allowed_data(): void
    {
        [$postulante, $carrera1, $carrera2] = $this->createPostulante();
        $user = $this->userWithPermissions(['postulantes:update']);

        $this->actingAs($user)
            ->put("/postulantes/{$postulante->id_postulante}", [
                'correo' => 'nuevo@example.com',
                'telefono' => '70000000',
                'fecha_nacimiento' => '2004-02-03',
                'direccion' => 'Nueva dirección',
                'colegio_procedencia' => 'Nuevo Colegio',
                'ciudad' => 'Cochabamba',
                'documentacion_completa' => true,
                'id_carrera_opcion1' => $carrera2->id_carrera,
                'id_carrera_opcion2' => $carrera1->id_carrera,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('usuario', [
            'id_usuario' => $postulante->id_usuario,
            'correo' => 'nuevo@example.com',
            'telefono' => '70000000',
        ]);
        $this->assertDatabaseHas('postulante', [
            'id_postulante' => $postulante->id_postulante,
            'ciudad' => 'Cochabamba',
            'documentacion_completa' => true,
        ]);
        $this->assertDatabaseHas('postulacion', [
            'id_postulante' => $postulante->id_postulante,
            'id_carrera_opcion1' => $carrera2->id_carrera,
            'id_carrera_opcion2' => $carrera1->id_carrera,
        ]);
    }

    public function test_email_must_be_unique(): void
    {
        $this->createPostulante(['correo' => 'ocupado@example.com']);
        [$postulante, $carrera1, $carrera2] = $this->createPostulante();
        $user = $this->userWithPermissions(['postulantes:update']);

        $this->actingAs($user)
            ->put("/postulantes/{$postulante->id_postulante}", [
                'correo' => 'ocupado@example.com',
                'telefono' => '70000000',
                'fecha_nacimiento' => '2004-02-03',
                'direccion' => 'Dirección',
                'colegio_procedencia' => 'Colegio',
                'ciudad' => 'Santa Cruz',
                'documentacion_completa' => true,
                'id_carrera_opcion1' => $carrera1->id_carrera,
                'id_carrera_opcion2' => $carrera2->id_carrera,
            ])
            ->assertSessionHasErrors('correo');
    }

    public function test_second_career_must_be_different_from_first(): void
    {
        [$postulante, $carrera1] = $this->createPostulante();
        $user = $this->userWithPermissions(['postulantes:update']);

        $this->actingAs($user)
            ->put("/postulantes/{$postulante->id_postulante}", [
                'correo' => 'valido@example.com',
                'telefono' => '70000000',
                'fecha_nacimiento' => '2004-02-03',
                'direccion' => 'Dirección',
                'colegio_procedencia' => 'Colegio',
                'ciudad' => 'Santa Cruz',
                'documentacion_completa' => true,
                'id_carrera_opcion1' => $carrera1->id_carrera,
                'id_carrera_opcion2' => $carrera1->id_carrera,
            ])
            ->assertSessionHasErrors('id_carrera_opcion2');
    }

    public function test_user_with_postulantes_update_can_mark_documentation(): void
    {
        [$postulante, $carrera1, $carrera2] = $this->createPostulante([
            'documentacion_completa' => false,
        ]);
        $user = $this->userWithPermissions(['postulantes:update']);

        $this->actingAs($user)
            ->put("/postulantes/{$postulante->id_postulante}", [
                'correo' => $postulante->usuario->correo,
                'telefono' => $postulante->usuario->telefono,
                'fecha_nacimiento' => '2004-02-03',
                'direccion' => $postulante->direccion,
                'colegio_procedencia' => $postulante->colegio_procedencia,
                'ciudad' => $postulante->ciudad,
                'documentacion_completa' => true,
                'id_carrera_opcion1' => $carrera1->id_carrera,
                'id_carrera_opcion2' => $carrera2->id_carrera,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('postulante', [
            'id_postulante' => $postulante->id_postulante,
            'documentacion_completa' => true,
        ]);
    }

    public function test_user_with_postulantes_update_can_soft_disable_postulante(): void
    {
        [$postulante] = $this->createPostulante();
        $user = $this->userWithPermissions(['postulantes:update']);

        $this->actingAs($user)
            ->patch("/postulantes/{$postulante->id_postulante}/toggle")
            ->assertRedirect();

        $this->assertDatabaseHas('usuario', [
            'id_usuario' => $postulante->id_usuario,
            'activo' => false,
        ]);
        $this->assertDatabaseHas('postulante', [
            'id_postulante' => $postulante->id_postulante,
        ]);
        $this->assertDatabaseHas('postulacion', [
            'id_postulante' => $postulante->id_postulante,
        ]);
    }

    private function userWithPermissions(array $permissions): User
    {
        $this->seed(AccessControlSeeder::class);

        $user = User::factory()->create();
        $role = Rol::create([
            'nombre' => 'ROL_POSTULANTES_'.Str::upper(Str::random(8)),
            'descripcion' => 'Rol de prueba para postulantes',
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

    private function createPostulante(array $overrides = []): array
    {
        $gestion = GestionAcademica::firstOrCreate(
            ['nombre' => 'CUP TEST'],
            [
                'fecha_inicio' => '2026-01-01',
                'fecha_fin' => '2026-12-31',
                'activo' => true,
            ],
        );
        $carrera1 = Carrera::firstOrCreate(
            ['nombre' => 'Carrera '.Str::upper(Str::random(8))],
            ['activo' => true],
        );
        $carrera2 = Carrera::firstOrCreate(
            ['nombre' => 'Carrera '.Str::upper(Str::random(8))],
            ['activo' => true],
        );
        $user = User::factory()->create([
            'ci' => $overrides['ci'] ?? fake()->unique()->numerify('########'),
            'nombre' => $overrides['nombre'] ?? 'Postulante',
            'apellido' => $overrides['apellido'] ?? 'Prueba',
            'correo' => $overrides['correo'] ?? fake()->unique()->safeEmail(),
        ]);
        $postulante = Postulante::create([
            'id_usuario' => $user->id_usuario,
            'fecha_nacimiento' => '2004-02-03',
            'direccion' => 'Dirección',
            'colegio_procedencia' => 'Colegio',
            'ciudad' => 'Santa Cruz',
            'documentacion_completa' => $overrides['documentacion_completa'] ?? true,
        ]);

        Postulacion::create([
            'id_postulante' => $postulante->id_postulante,
            'id_gestion' => $gestion->id_gestion,
            'id_carrera_opcion1' => $carrera1->id_carrera,
            'id_carrera_opcion2' => $carrera2->id_carrera,
            'estado_admision' => 'PENDIENTE',
            'fecha_postulacion' => now(),
        ]);

        return [$postulante->load('usuario', 'postulaciones'), $carrera1, $carrera2];
    }
}
