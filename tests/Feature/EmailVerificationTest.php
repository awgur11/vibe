<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\VerifyEmailQueued;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_queues_verification_and_blocks_unverified_admin_access(): void
    {
        Notification::fake();
        $this->postJson(route('register.store'), ['name' => 'New User', 'email' => 'verify@example.test', 'password' => 'password123', 'password_confirmation' => 'password123'])
            ->assertOk()->assertJsonPath('redirect', route('verification.notice'));
        $user = User::where('email', 'verify@example.test')->sole();
        $this->assertFalse($user->hasVerifiedEmail());
        Notification::assertSentTo($user, VerifyEmailQueued::class, fn ($notification): bool => $notification instanceof ShouldQueue && $notification->afterCommit === true);
        $this->get(route('dashboard'))->assertRedirect(route('verification.notice'));
        $this->get(route('verification.notice'))->assertOk()->assertSee('verify@example.test');
    }

    public function test_signed_link_verifies_email_once_and_returns_home(): void
    {
        Event::fake([Verified::class]);
        $user = User::factory()->unverified()->create();
        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1($user->email)]);
        $this->actingAs($user)->get($url)->assertRedirect(route('index'));
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->get($url)->assertRedirect(route('index'));
        Event::assertDispatchedTimes(Verified::class, 1);
    }

    public function test_expired_wrong_hash_and_other_users_links_are_rejected(): void
    {
        $user = User::factory()->unverified()->create();
        $other = User::factory()->unverified()->create();
        $this->actingAs($user);
        foreach ([
            URL::temporarySignedRoute('verification.verify', now()->subMinute(), ['id' => $user->id, 'hash' => sha1($user->email)]),
            URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => 'wrong']),
            URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $other->id, 'hash' => sha1($other->email)]),
        ] as $url) {
            $this->get($url)->assertForbidden();
        }
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_resend_is_authenticated_and_does_not_send_for_verified_users(): void
    {
        Notification::fake();
        $this->post(route('verification.send'))->assertRedirect(route('login'));
        $user = User::factory()->unverified()->create();
        $this->actingAs($user)->post(route('verification.send'))->assertSessionHas('verification_sent');
        Notification::assertSentToTimes($user, VerifyEmailQueued::class, 1);
        $user->markEmailAsVerified();
        $this->post(route('verification.send'))->assertRedirect(route('index'));
        Notification::assertSentToTimes($user, VerifyEmailQueued::class, 1);
    }
}
