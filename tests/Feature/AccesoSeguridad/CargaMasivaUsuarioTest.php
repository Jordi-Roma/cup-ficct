<?php

namespace Tests\Feature\AccesoSeguridad;

use App\Modules\AccesoSeguridad\Models\Permiso;
use App\Modules\AccesoSeguridad\Models\Rol;
use App\Modules\AccesoSeguridad\Models\User;
use App\Modules\GestionAcademica\Models\Carrera;
use App\Modules\GestionAcademica\Models\Docente;
use App\Modules\GestionAcademica\Models\GestionAcademica;
use App\Modules\GestionAcademica\Models\MateriaCup;
use App\Modules\RegistroPostulantes\Models\Postulacion;
use App\Modules\RegistroPostulantes\Models\Postulante;
use Database\Seeders\AccessControlSeeder;
use Database\Seeders\CupCatalogSeeder;
use Database\Seeders\MateriaCupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class CargaMasivaUsuarioTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/carga-masiva')->assertRedirect('/login');
    }

    public function test_user_without_usuarios_create_receives_forbidden(): void
    {
        $this->seed(AccessControlSeeder::class);

        $this->actingAs(User::factory()->create())
            ->get('/admin/carga-masiva')
            ->assertForbidden();
    }

    public function test_user_with_usuarios_create_can_view_page(): void
    {
        $this->actingAs($this->userWithPermissions(['usuarios:create']))
            ->get('/admin/carga-masiva')
            ->assertOk();
    }

    public function test_can_import_postulantes_enabled_without_payment(): void
    {
        Mail::fake();
        config(['services.postulante_notification_email' => 'admincupficct@example.com']);
        $this->seed(CupCatalogSeeder::class);
        $actor = $this->userWithPermissions(['usuarios:create']);
        [$carrera1, $carrera2] = Carrera::query()->where('activo', true)->orderBy('id_carrera')->limit(2)->get();

        $response = $this->actingAs($actor)->post('/admin/carga-masiva', [
            'tipo_usuario' => 'POSTULANTE',
            'archivo_csv' => $this->csv('postulantes.csv', [
                'ci,nombre,apellido,correo,telefono,sexo,fecha_nacimiento,direccion,colegio_procedencia,ciudad,carrera_opcion1,carrera_opcion2,turno_preferido,password',
                "90000001,Ana,Rojas,ana@example.com,70000001,F,2005-01-10,Av 1,Colegio A,Santa Cruz,{$carrera1->nombre},{$carrera2->nombre},MANANA,Cup2026#01",
            ]),
        ]);

        $response->assertRedirect()->assertSessionHas('resultado_carga_masiva');

        $user = User::where('ci', '90000001')->firstOrFail();
        $postulante = Postulante::where('id_usuario', $user->id_usuario)->firstOrFail();
        $postulacion = Postulacion::where('id_postulante', $postulante->id_postulante)->firstOrFail();
        $result = session('resultado_carga_masiva');

        $this->assertSame('P90000001', $user->username);
        $this->assertTrue($user->activo);
        $this->assertSame('HABILITADO', $user->estado_acceso);
        $this->assertTrue(Hash::check('Cup2026#01', $user->password_hash));
        $this->assertNotSame('Cup2026#01', $user->password_hash);
        $this->assertSame('Cup2026#01', $result['usuarios_creados'][0]['password']);
        $this->assertTrue($user->hasRole('POSTULANTE'));
        $this->assertTrue($postulante->documentacion_completa);
        $this->assertTrue($postulante->documentacion_validada);
        $this->assertTrue($postulante->creado_por_admin);
        $this->assertFalse($postulante->requiere_pago);
        $this->assertSame($actor->id_usuario, $postulante->validado_por);
        $this->assertSame('HABILITADO_CUP', $postulacion->estado_proceso);
        $this->assertSame('MANANA', $postulacion->turno_preferido);

        Mail::assertNothingSent();
    }

    public function test_imported_postulante_login_goes_to_dashboard_not_payment(): void
    {
        Mail::fake();
        config(['services.postulante_notification_email' => 'admincupficct@example.com']);
        $this->seed(CupCatalogSeeder::class);
        $actor = $this->userWithPermissions(['usuarios:create']);
        $carrera = Carrera::firstOrFail();

        $this->actingAs($actor)->post('/admin/carga-masiva', [
            'tipo_usuario' => 'POSTULANTE',
            'archivo_csv' => $this->csv('postulantes.csv', [
                'ci,nombre,apellido,correo,telefono,sexo,fecha_nacimiento,direccion,colegio_procedencia,ciudad,carrera_opcion1,carrera_opcion2,turno_preferido,password',
                "90000002,Luis,Paz,luis@example.com,70000002,M,2005-02-10,Av 2,Colegio B,Santa Cruz,{$carrera->nombre},,TARDE,Cup2026#02",
            ]),
        ]);

        auth()->logout();
        session()->flush();

        $this->post('/login', [
            'username' => 'P90000002',
            'password' => 'Cup2026#02',
        ])->assertRedirect('/dashboard');
    }

    public function test_password_is_required_for_imported_users(): void
    {
        Mail::fake();
        $this->seed(CupCatalogSeeder::class);
        $actor = $this->userWithPermissions(['usuarios:create']);
        $carrera = Carrera::firstOrFail();

        $this->actingAs($actor)->post('/admin/carga-masiva', [
            'tipo_usuario' => 'POSTULANTE',
            'archivo_csv' => $this->csv('postulantes.csv', [
                'ci,nombre,apellido,correo,telefono,sexo,fecha_nacimiento,direccion,colegio_procedencia,ciudad,carrera_opcion1,carrera_opcion2,turno_preferido,password',
                "90000005,Ana,Rojas,ana5@example.com,70000005,F,2005-01-10,Av 1,Colegio A,Santa Cruz,{$carrera->nombre},,MANANA,",
            ]),
        ])->assertRedirect();

        $result = session('resultado_carga_masiva');

        $this->assertSame(0, $result['creados']);
        $this->assertSame(1, $result['omitidos']);
        $this->assertFalse(User::where('ci', '90000005')->exists());
        Mail::assertNothingSent();
    }

    public function test_duplicate_ci_is_reported_and_does_not_stop_all_rows(): void
    {
        Mail::fake();
        $this->seed(CupCatalogSeeder::class);
        User::factory()->create(['ci' => '90000003', 'correo' => 'ocupado@example.com']);
        $actor = $this->userWithPermissions(['usuarios:create']);
        $carrera = Carrera::firstOrFail();

        $this->actingAs($actor)->post('/admin/carga-masiva', [
            'tipo_usuario' => 'POSTULANTE',
            'archivo_csv' => $this->csv('postulantes.csv', [
                'ci,nombre,apellido,correo,telefono,sexo,fecha_nacimiento,direccion,colegio_procedencia,ciudad,carrera_opcion1,carrera_opcion2,turno_preferido,password',
                "90000003,Ana,Rojas,ana2@example.com,70000003,F,2005-01-10,Av 1,Colegio A,Santa Cruz,{$carrera->nombre},,MANANA,Cup2026#03",
                "90000004,Luis,Paz,luis2@example.com,70000004,M,2005-02-10,Av 2,Colegio B,Santa Cruz,{$carrera->nombre},,NOCHE,Cup2026#04",
            ]),
        ])->assertRedirect();

        $result = session('resultado_carga_masiva');

        $this->assertSame(1, $result['creados']);
        $this->assertSame(1, $result['omitidos']);
        $this->assertTrue(User::where('ci', '90000004')->exists());
    }

    public function test_can_import_docentes_with_habilitaciones(): void
    {
        Mail::fake();
        $this->seed(MateriaCupSeeder::class);
        $actor = $this->userWithPermissions(['usuarios:create']);
        $materia = MateriaCup::firstOrFail();

        $this->actingAs($actor)->post('/admin/carga-masiva', [
            'tipo_usuario' => 'DOCENTE',
            'archivo_csv' => $this->csv('docentes.csv', [
                'ci,nombre,apellido,correo,telefono,sexo,profesional_area,diplomado,maestria,maestria_educacion_superior,contratado,materias_profesional_area,materias_diplomado,materias_maestria,password',
                "80000001,Braulio,Miranda,braulio@example.com,70000005,M,1,0,0,1,1,{$materia->nombre},,,Cup2026#05",
            ]),
        ])->assertRedirect();

        $user = User::where('ci', '80000001')->firstOrFail();
        $docente = Docente::where('id_usuario', $user->id_usuario)->firstOrFail();

        $this->assertSame('D80000001', $user->username);
        $this->assertTrue(Hash::check('Cup2026#05', $user->password_hash));
        $this->assertTrue($user->hasRole('DOCENTE'));
        $this->assertTrue($docente->contratado);
        $this->assertTrue($docente->maestria_educacion_superior);
        $this->assertDatabaseHas('docente_habilitacion_materia', [
            'id_docente' => $docente->id_docente,
            'id_materia' => $materia->id_materia,
            'tipo_habilitacion' => 'PROFESIONAL_AREA',
        ]);
    }

    public function test_contracted_docente_requires_maestria_educacion_superior(): void
    {
        Mail::fake();
        $this->seed(MateriaCupSeeder::class);
        $actor = $this->userWithPermissions(['usuarios:create']);
        $materia = MateriaCup::firstOrFail();

        $this->actingAs($actor)->post('/admin/carga-masiva', [
            'tipo_usuario' => 'DOCENTE',
            'archivo_csv' => $this->csv('docentes.csv', [
                'ci,nombre,apellido,correo,telefono,sexo,profesional_area,diplomado,maestria,maestria_educacion_superior,contratado,materias_profesional_area,materias_diplomado,materias_maestria,password',
                "80000002,Braulio,Miranda,braulio2@example.com,70000006,M,1,0,0,0,1,{$materia->nombre},,,Cup2026#06",
            ]),
        ])->assertRedirect();

        $result = session('resultado_carga_masiva');

        $this->assertSame(0, $result['creados']);
        $this->assertSame(1, $result['omitidos']);
        $this->assertFalse(User::where('ci', '80000002')->exists());
    }

    public function test_can_import_administradores_and_coordinadores(): void
    {
        Mail::fake();
        $actor = $this->userWithPermissions(['usuarios:create']);

        $this->actingAs($actor)->post('/admin/carga-masiva', [
            'tipo_usuario' => 'ADMINISTRADOR',
            'archivo_csv' => $this->csv('admins.csv', [
                'ci,nombre,apellido,correo,telefono,sexo,password',
                '60000001,Marco,Rivero,marco@example.com,70000007,M,Cup2026#07',
            ]),
        ])->assertRedirect();

        $this->actingAs($actor)->post('/admin/carga-masiva', [
            'tipo_usuario' => 'COORDINADOR_ACADEMICO',
            'archivo_csv' => $this->csv('coordinadores.csv', [
                'ci,nombre,apellido,correo,telefono,sexo,password',
                '70000001,Carla,Vargas,carla@example.com,70000008,F,Cup2026#08',
            ]),
        ])->assertRedirect();

        $this->assertTrue(User::where('username', 'A60000001')->firstOrFail()->hasRole('ADMINISTRADOR'));
        $this->assertTrue(User::where('username', 'C70000001')->firstOrFail()->roles()->exists());
    }

    private function userWithPermissions(array $permissions): User
    {
        $this->seed(AccessControlSeeder::class);

        $user = User::factory()->create();
        $role = Rol::create([
            'nombre' => 'ROL_CARGA_'.Str::upper(Str::random(8)),
            'descripcion' => 'Rol de prueba para carga masiva',
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

    private function csv(string $name, array $lines): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, implode("\n", $lines));
    }
}
