<?php

namespace Tests\Feature\Auth;

use App\Modules\AccesoSeguridad\Mail\PasswordResetNotificationMail;
use App\Modules\AccesoSeguridad\Models\User;
use App\Modules\AccesoSeguridad\Services\PasswordResetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $this->get(route('password.request'))->assertOk();
    }

    public function test_reset_password_code_can_be_requested_by_username_and_sent_to_institutional_email(): void
    {
        Mail::fake();
        config(['mail.from.address' => 'sistema@example.com']);
        config(['services.postulantes.notification_email' => 'admincupficct@example.com']);
        $this->fakePasswordResetCode('123456');

        $user = User::factory()->create();

        $this->post(route('password.email'), ['username_or_email' => $user->username])
            ->assertRedirect()
            ->assertSessionHas('status');

        Mail::assertSent(PasswordResetNotificationMail::class, function ($mail) use ($user) {
            return $mail->hasTo('admincupficct@example.com')
                && str_contains($mail->body, $user->username)
                && str_contains($mail->body, $user->correo)
                && str_contains($mail->body, '123456')
                && ! str_contains($mail->body, '/reset-password/');
        });

        $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->correo]);
    }

    public function test_verify_code_screen_can_be_rendered_after_request(): void
    {
        $response = $this->withSession([
            'password_reset.identifier' => 'testuser',
        ])->get(route('password.verify.form'));

        $response->assertOk();
    }

    public function test_reset_password_screen_requires_verified_code(): void
    {
        $this->get(route('password.reset.form'))
            ->assertRedirect(route('password.verify.form'));
    }

    public function test_valid_token_allows_access_to_change_password_screen(): void
    {
        Mail::fake();
        config(['services.postulantes.notification_email' => 'admincupficct@example.com']);
        $this->fakePasswordResetCode('123456');

        $user = User::factory()->create();

        $this->post(route('password.email'), ['username_or_email' => $user->username]);

        Mail::assertSent(PasswordResetNotificationMail::class);

        $this->post(route('password.verify'), [
            'token' => '123456',
            'username_or_email' => $user->username,
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('password.reset.form'));

        $this->withSession([
            'password_reset.identifier' => $user->username,
            'password_reset.verified' => true,
        ])->get(route('password.reset.form'))->assertOk();
    }

    public function test_password_can_be_reset_after_token_verification(): void
    {
        Mail::fake();
        config(['services.postulantes.notification_email' => 'admincupficct@example.com']);
        $this->fakePasswordResetCode('123456');

        $user = User::factory()->create();

        $this->post(route('password.email'), ['username_or_email' => $user->username]);

        $this->post(route('password.verify'), [
            'token' => '123456',
            'username_or_email' => $user->username,
        ]);

        $this->post(route('password.update'), [
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('Password1!', $user->fresh()->password_hash));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->correo]);
    }

    public function test_password_cannot_be_reset_with_invalid_token(): void
    {
        $user = User::factory()->create();

        DB::table('password_reset_tokens')->insert([
            'email' => $user->correo,
            'token' => Hash::make('123456'),
            'created_at' => now(),
        ]);

        $this->withSession([
            'password_reset.identifier' => $user->username,
        ])->post(route('password.verify'), [
            'token' => '654321',
            'username_or_email' => $user->username,
        ])->assertSessionHasErrors('token');
    }

    public function test_password_cannot_be_changed_without_verified_token(): void
    {
        $this->post(route('password.update'), [
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
        ])->assertRedirect(route('password.verify.form'));
    }

    public function test_password_rules_are_required_after_token_verification(): void
    {
        $user = User::factory()->create();

        DB::table('password_reset_tokens')->insert([
            'email' => $user->correo,
            'token' => Hash::make('123456'),
            'created_at' => now(),
        ]);

        $this->withSession([
            'password_reset.identifier' => $user->username,
            'password_reset.verified' => true,
        ])->post(route('password.update'), [
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('password');
    }

    private function fakePasswordResetCode(string $code): void
    {
        $this->app->bind(PasswordResetService::class, fn () => new class($code) extends PasswordResetService
        {
            public function __construct(private readonly string $code) {}

            protected function generateResetCode(): string
            {
                return $this->code;
            }
        });
    }
}
