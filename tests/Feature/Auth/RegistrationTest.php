<?php

namespace Tests\Feature\Auth;

use App\Modules\Autenticacion\Models\User;
use App\Modules\GestionAcademica\Models\Carrera;
use App\Modules\GestionAcademica\Models\GestionAcademica;
use App\Modules\RegistroPostulantes\Models\Postulacion;
use App\Modules\RegistroPostulantes\Models\Postulante;
use Database\Seeders\AccessControlSeeder;
use Database\Seeders\CupCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::registration());
    }

    public function test_registration_screen_can_be_rendered()
    {
        $this->seed(CupCatalogSeeder::class);
        $this->seed(AccessControlSeeder::class);

        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_postulantes_can_register()
    {
        $this->seed(CupCatalogSeeder::class);
        $this->seed(AccessControlSeeder::class);

        $gestion = GestionAcademica::where('nombre', 'CUP 2026')->firstOrFail();
        $carreraPrincipal = Carrera::where('nombre', 'Ingeniería de Sistemas')->firstOrFail();
        $carreraSecundaria = Carrera::where('nombre', 'Ingeniería Informática')->firstOrFail();

        $response = $this->post(route('register.store'), [
            'ci' => '12345678',
            'nombre' => 'Jordi',
            'apellido' => 'Rivera',
            'username' => 'jrivera',
            'correo' => 'jordi@example.com',
            'telefono' => '70000000',
            'sexo' => 'M',
            'fecha_nacimiento' => '2005-05-20',
            'direccion' => 'Av. Principal',
            'colegio_procedencia' => 'Colegio Central',
            'ciudad' => 'Santa Cruz',
            'documentacion_completa' => true,
            'id_gestion' => $gestion->id_gestion,
            'id_carrera_opcion1' => $carreraPrincipal->id_carrera,
            'id_carrera_opcion2' => $carreraSecundaria->id_carrera,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('username', 'jrivera')->first();
        $this->assertNotNull($user);
        $this->assertSame('12345678', $user->ci);
        $this->assertSame('jordi@example.com', $user->correo);
        $this->assertTrue($user->hasRole('POSTULANTE'));

        $postulante = Postulante::where('id_usuario', $user->id_usuario)->first();
        $this->assertNotNull($postulante);
        $this->assertSame('Santa Cruz', $postulante->ciudad);
        $this->assertTrue($postulante->documentacion_completa);

        $postulacion = Postulacion::where('id_postulante', $postulante->id_postulante)->first();
        $this->assertNotNull($postulacion);
        $this->assertSame($gestion->id_gestion, $postulacion->id_gestion);
        $this->assertSame($carreraPrincipal->id_carrera, $postulacion->id_carrera_opcion1);
        $this->assertSame($carreraSecundaria->id_carrera, $postulacion->id_carrera_opcion2);
        $this->assertSame('PENDIENTE', $postulacion->estado_admision);
    }

    public function test_duplicate_ci_username_and_correo_are_rejected()
    {
        $this->seed(CupCatalogSeeder::class);

        $gestion = GestionAcademica::where('nombre', 'CUP 2026')->firstOrFail();
        $carrera = Carrera::where('nombre', 'Ingeniería de Sistemas')->firstOrFail();

        User::factory()->create([
            'ci' => '12345678',
            'username' => 'jrivera',
            'correo' => 'jordi@example.com',
        ]);

        $response = $this->from(route('register'))->post(route('register.store'), [
            'ci' => '12345678',
            'nombre' => 'Jordi',
            'apellido' => 'Rivera',
            'username' => 'jrivera',
            'correo' => 'jordi@example.com',
            'sexo' => 'M',
            'fecha_nacimiento' => '2005-05-20',
            'documentacion_completa' => true,
            'id_gestion' => $gestion->id_gestion,
            'id_carrera_opcion1' => $carrera->id_carrera,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors(['ci', 'username', 'correo']);
    }

    public function test_second_career_must_be_different_from_first_career()
    {
        $this->seed(CupCatalogSeeder::class);

        $gestion = GestionAcademica::where('nombre', 'CUP 2026')->firstOrFail();
        $carrera = Carrera::where('nombre', 'Ingeniería de Sistemas')->firstOrFail();

        $response = $this->from(route('register'))->post(route('register.store'), [
            'ci' => '87654321',
            'nombre' => 'Ana',
            'apellido' => 'Suarez',
            'username' => 'asuarez',
            'correo' => 'ana@example.com',
            'sexo' => 'F',
            'fecha_nacimiento' => '2004-03-12',
            'documentacion_completa' => true,
            'id_gestion' => $gestion->id_gestion,
            'id_carrera_opcion1' => $carrera->id_carrera,
            'id_carrera_opcion2' => $carrera->id_carrera,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors(['id_carrera_opcion2']);
    }

    public function test_documentacion_completa_is_required()
    {
        $this->seed(CupCatalogSeeder::class);

        $gestion = GestionAcademica::where('nombre', 'CUP 2026')->firstOrFail();
        $carrera = Carrera::firstOrFail();

        $response = $this->from(route('register'))->post(route('register.store'), [
            'ci' => '11223344',
            'nombre' => 'Luis',
            'apellido' => 'Mendoza',
            'username' => 'lmendoza',
            'correo' => 'luis@example.com',
            'sexo' => 'M',
            'fecha_nacimiento' => '2004-11-10',
            'id_gestion' => $gestion->id_gestion,
            'id_carrera_opcion1' => $carrera->id_carrera,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors(['documentacion_completa']);
    }
}
