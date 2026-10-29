<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SiteAuthModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_modal_and_switchable_login_and_registration_forms(): void
    {
        $this->get(route('index'))->assertOk()->assertSee('data-auth-open', false)->assertSee('id="authRegDialog"', false)
            ->assertSee('data-auth-form="login"', false)->assertSee('data-auth-form="register" hidden', false);
    }

    public function test_json_login_authenticates_without_redirecting_to_admin(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password123')]);
        $this->postJson(route('login.store'), ['email' => $user->email, 'password' => 'password123'])
            ->assertOk()->assertExactJson(['authenticated' => true]);
        $this->assertAuthenticatedAs($user);
        $this->get(route('index'))->assertOk()->assertDontSee('data-auth-open', false)->assertDontSee('id="authRegDialog"', false);
    }

    public function test_invalid_login_returns_field_errors(): void
    {
        $this->postJson(route('login.store'), ['email' => 'unknown@example.test', 'password' => 'wrong'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertGuest();
    }

    public function test_json_registration_creates_and_authenticates_user_and_validates_confirmation(): void
    {
        $data = ['name' => 'Test User', 'email' => 'new@example.test', 'password' => 'password123', 'password_confirmation' => 'different'];
        $this->postJson(route('register.store'), $data)->assertUnprocessable()->assertJsonValidationErrors('password');
        $data['password_confirmation'] = 'password123';
        $this->postJson(route('register.store'), $data)->assertOk()->assertExactJson(['authenticated' => true, 'redirect' => route('verification.notice')]);
        $user = User::where('email', 'new@example.test')->sole();
        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check('password123', $user->password));
    }
}
