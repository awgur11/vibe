<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_open_forms_but_not_dashboard(): void
    {
        foreach (['/login', '/register', '/forgot-password', '/reset-password/example?email=test@example.com'] as $url) {
            $this->get($url)->assertOk()->assertSee('name="_token"', false);
        }
        $this->get('/adminzone')->assertRedirect(route('login'));
    }

    public function test_registration_creates_user_and_logs_in(): void
    {
        $this->post('/register', [
            'name' => 'Анна', 'email' => 'anna@example.com',
            'password' => 'secure-password', 'password_confirmation' => 'secure-password',
        ])->assertRedirect(route('dashboard'));
        $user = User::where('email', 'anna@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check('secure-password', $user->password));
        $this->get('/adminzone')->assertOk()->assertSee('Анна');
        $this->get('/login')->assertRedirect();
    }

    public function test_registration_rejects_duplicate_email_and_invalid_password(): void
    {
        $user = User::factory()->create();
        $this->post('/register', [
            'name' => 'Test', 'email' => $user->email,
            'password' => 'short', 'password_confirmation' => 'different',
        ])->assertSessionHasErrors(['email', 'password']);
        $this->assertGuest();
        $this->assertDatabaseCount('users', 1);
    }

    public function test_login_and_logout(): void
    {
        $user = User::factory()->create();
        $this->post('/login', ['email' => $user->email, 'password' => 'password', 'remember' => '1'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
        $this->get('/adminzone')->assertRedirect(route('login'));
    }

    public function test_invalid_login_is_rejected_and_rate_limited(): void
    {
        $user = User::factory()->create();
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'incorrect'])->assertSessionHasErrors('email');
        }
        $this->assertGuest();
        $this->post('/login', ['email' => $user->email, 'password' => 'incorrect'])->assertStatus(429);
    }

    public function test_reset_email_contains_single_use_link_and_new_password_works(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->post('/forgot-password', ['email' => $user->email])->assertSessionHas('status');
        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token, $user): bool {
            $token = $notification->token;
            $this->assertStringContainsString('/reset-password/', $notification->toMail($user)->actionUrl);

            return true;
        });
        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))->assertOk();
        $payload = ['token' => $token, 'email' => $user->email, 'password' => 'new-password', 'password_confirmation' => 'new-password'];
        $this->post('/reset-password', $payload)->assertRedirect(route('login'))->assertSessionHas('status');
        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
        $this->post('/reset-password', $payload)->assertSessionHasErrors('email');
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => $user->email, 'password' => 'new-password'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_unknown_email_does_not_reveal_account_existence(): void
    {
        Notification::fake();
        $this->post('/forgot-password', ['email' => 'unknown@example.com'])->assertSessionHas('status');
        Notification::assertNothingSent();
    }

    public function test_invalid_and_expired_tokens_do_not_change_password(): void
    {
        $user = User::factory()->create();
        $payload = ['email' => $user->email, 'password' => 'new-password', 'password_confirmation' => 'new-password'];
        $this->post('/reset-password', $payload + ['token' => 'invalid'])->assertSessionHasErrors('email');
        $token = Password::createToken($user);
        $this->travel(61)->minutes();
        $this->post('/reset-password', $payload + ['token' => $token])->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }
}
