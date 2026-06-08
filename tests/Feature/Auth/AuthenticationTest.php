<?php

namespace Tests\Feature\Auth;

use App\Modules\AccesoSeguridad\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered()
    {
        $response = $this->get(route('login'));

        $response->assertOk();
    }

    public function test_users_can_authenticate_using_the_login_screen()
    {
        $user = User::factory()->create();

        $response = $this->post(route('login.store'), [
            'username' => $user->username,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password()
    {
        $user = User::factory()->create();

        $response = $this->post(route('login.store'), [
            'username' => $user->username,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('username');
    }

    public function test_users_can_logout()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('home'));

        $this->assertGuest();
    }

    public function test_users_are_rate_limited_after_five_failed_attempts()
    {
        $user = User::factory()->create();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('login.store'), [
                'username' => $user->username,
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->post(route('login.store'), [
            'username' => $user->username,
            'password' => 'wrong-password',
        ]);

        $response->assertTooManyRequests();
    }

    public function test_login_screen_shows_remaining_attempts_after_failed_login(): void
    {
        $user = User::factory()->create();

        $this->from(route('login'))->post(route('login.store'), [
            'username' => $user->username,
            'password' => 'wrong-password',
        ]);

        $rateLimit = $this->get(route('login'))->viewData('page')['props']['loginRateLimit'];

        $this->assertSame(5, $rateLimit['maxAttempts']);
        $this->assertSame(1, $rateLimit['attempts']);
        $this->assertSame(4, $rateLimit['remaining']);
        $this->assertFalse($rateLimit['locked']);
    }

    public function test_login_screen_shows_locked_state_after_five_failed_logins(): void
    {
        $user = User::factory()->create();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->from(route('login'))->post(route('login.store'), [
                'username' => $user->username,
                'password' => 'wrong-password',
            ]);
        }

        $rateLimit = $this->get(route('login'))->viewData('page')['props']['loginRateLimit'];

        $this->assertSame(5, $rateLimit['maxAttempts']);
        $this->assertSame(5, $rateLimit['attempts']);
        $this->assertSame(0, $rateLimit['remaining']);
        $this->assertTrue($rateLimit['locked']);
        $this->assertGreaterThan(0, $rateLimit['availableIn']);
    }

    public function test_user_cannot_login_with_correct_password_during_temporary_lock(): void
    {
        $user = User::factory()->create();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('login.store'), [
                'username' => $user->username,
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->post(route('login.store'), [
            'username' => $user->username,
            'password' => 'password',
        ]);

        $response->assertTooManyRequests();
        $this->assertGuest();
    }

    public function test_user_can_login_again_after_three_minutes(): void
    {
        $user = User::factory()->create();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('login.store'), [
                'username' => $user->username,
                'password' => 'wrong-password',
            ]);
        }

        $this->travel(3)->minutes();

        $response = $this->post(route('login.store'), [
            'username' => $user->username,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_successful_login_clears_failed_attempts(): void
    {
        $user = User::factory()->create();

        for ($attempt = 0; $attempt < 4; $attempt++) {
            $this->post(route('login.store'), [
                'username' => $user->username,
                'password' => 'wrong-password',
            ]);
        }

        $this->post(route('login.store'), [
            'username' => $user->username,
            'password' => 'password',
        ]);
        $this->assertAuthenticated();
        $this->post(route('logout'))->assertRedirect(route('home'));
        $this->assertGuest();

        $this->post(route('login.store'), [
            'username' => $user->username,
            'password' => 'wrong-password',
        ]);

        $response = $this->post(route('login.store'), [
            'username' => $user->username,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }
}
