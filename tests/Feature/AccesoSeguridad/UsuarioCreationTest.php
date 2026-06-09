<?php

namespace Tests\Feature\AccesoSeguridad;

use App\Modules\AccesoSeguridad\Models\Permiso;
use App\Modules\AccesoSeguridad\Models\Rol;
use App\Modules\AccesoSeguridad\Models\User;
use App\Modules\GestionAcademica\Models\Docente;
use App\Modules\GestionAcademica\Models\MateriaCup;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class UsuarioCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_create_user_with_password_without_uppercase(): void
    {
        $this->assertWeakPasswordIsRejected('password1!');
    }

    public function test_admin_cannot_create_user_with_password_without_lowercase(): void
    {
        $this->assertWeakPasswordIsRejected('PASSWORD1!');
    }

    public function test_admin_cannot_create_user_with_password_without_number(): void
    {
        $this->assertWeakPasswordIsRejected('Password!');
    }

    public function test_admin_cannot_create_user_with_password_without_symbol(): void
    {
        $this->assertWeakPasswordIsRejected('Password1');
    }

    public function test_admin_can_create_docente_with_strong_password(): void
    {
        $actor = $this->userWithPermissions(['usuarios:create']);
        $materia = MateriaCup::create(['nombre' => 'Matematica '.Str::upper(Str::random(5)), 'activo' => true]);
        $payload = $this->docentePayload('Password1!', $materia->id_materia);

        $this->actingAs($actor)
            ->post('/admin/usuarios', $payload)
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $user = User::where('username', $payload['username'])->firstOrFail();

        $this->assertTrue(Hash::check('Password1!', $user->password_hash));
        $this->assertTrue($user->hasRole('DOCENTE'));
        $this->assertDatabaseHas('docente', ['id_usuario' => $user->id_usuario]);
        $this->assertSame(1, Docente::where('id_usuario', $user->id_usuario)->firstOrFail()->habilitaciones()->where('activo', true)->count());
    }

    private function assertWeakPasswordIsRejected(string $password): void
    {
        $actor = $this->userWithPermissions(['usuarios:create']);
        $materia = MateriaCup::create(['nombre' => 'Materia '.Str::upper(Str::random(5)), 'activo' => true]);
        $payload = $this->docentePayload($password, $materia->id_materia);

        $this->actingAs($actor)
            ->from('/admin/usuarios')
            ->post('/admin/usuarios', $payload)
            ->assertRedirect('/admin/usuarios')
            ->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('usuario', ['username' => $payload['username']]);
    }

    private function docentePayload(string $password, int $materiaId): array
    {
        $suffix = Str::lower(Str::random(8));

        return [
            'tipo_usuario' => 'DOCENTE',
            'ci' => fake()->unique()->numerify('########'),
            'nombre' => 'Docente',
            'apellido' => 'Prueba',
            'username' => 'docente_'.$suffix,
            'correo' => 'docente_'.$suffix.'@example.com',
            'password' => $password,
            'password_confirmation' => $password,
            'telefono' => '70000000',
            'sexo' => 'O',
            'estado_acceso' => 'HABILITADO',
            'activo' => true,
            'maestria_educacion_superior' => true,
            'contratado' => true,
            'habilitaciones' => [
                'PROFESIONAL_AREA' => [$materiaId],
                'DIPLOMADO' => [],
                'MAESTRIA' => [],
            ],
        ];
    }

    private function userWithPermissions(array $permissions): User
    {
        $this->seed(AccessControlSeeder::class);

        $user = User::factory()->create();
        $role = Rol::create([
            'nombre' => 'ROL_USUARIOS_'.Str::upper(Str::random(8)),
            'descripcion' => 'Rol de prueba usuarios',
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
}
