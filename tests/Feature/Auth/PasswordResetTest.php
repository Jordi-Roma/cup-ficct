<?php

namespace Tests\Feature\Auth;

use App\Modules\AccesoSeguridad\Mail\PasswordResetNotificationMail;
use App\Modules\AccesoSeguridad\Models\User;
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

    public function test_reset_password_link_can_be_requested_by_username_and_sent_to_institutional_email(): void
    {
        Mail::fake();
        config(['mail.from.address' => 'sistema@example.com']);
        config(['services.postulante_notification_email' => 'admincupficct@example.com']);

        $user = User::factory()->create();

        $this->post(route('password.email'), ['username_or_email' => $user->username])
            ->assertRedirect()
            ->assertSessionHas('status');

        Mail::assertSent(PasswordResetNotificationMail::class, function ($mail) use ($user) {
            return $mail->hasTo('admincupficct@example.com')
                && str_contains($mail->body, $user->username)
                && str_contains($mail->body, $user->correo)
                && str_contains($mail->body, '/reset-password/');
        });

        $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->correo]);
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        $response = $this->get(route('password.reset', [
            'token' => 'valid-looking-token',
            'identifier' => 'testuser',
        ]));

        $response->assertOk();
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Mail::fake();
        config(['services.postulante_notification_email' => 'admincupficct@example.com']);

        $user = User::factory()->create();

        $this->post(route('password.email'), ['username_or_email' => $user->username]);

        Mail::assertSent(PasswordResetNotificationMail::class, function ($mail) use ($user) {
            $token = $this->extractToken($mail->body);

            $this->post(route('password.update'), [
                'token' => $token,
                'username_or_email' => $user->username,
                'password' => 'Password1!',
                'password_confirmation' => 'Password1!',
            ])
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login'));

            $this->assertTrue(Hash::check('Password1!', $user->fresh()->password_hash));
            $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->correo]);

            return true;
        });
    }

    public function test_password_cannot_be_reset_with_invalid_token(): void
    {
        $user = User::factory()->create();

        DB::table('password_reset_tokens')->insert([
            'email' => $user->correo,
            'token' => Hash::make('valid-token'),
            'created_at' => now(),
        ]);

        $this->post(route('password.update'), [
            'token' => 'invalid-token',
            'username_or_email' => $user->username,
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
        ])->assertSessionHasErrors('token');
    }

    private function extractToken(string $body): string
    {
        preg_match('#/reset-password/([^?\\s]+)#', $body, $matches);

        return $matches[1] ?? '';
    }
}
