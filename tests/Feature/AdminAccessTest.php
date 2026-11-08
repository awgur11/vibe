<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Models\ProductComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_admin_routes_require_admin_permission(): void
    {
        foreach (Route::getRoutes() as $route) {
            if (str_starts_with($route->uri(), 'adminzone')) {
                $this->assertContains(EnsureUserIsAdmin::class, $route->gatherMiddleware(), $route->uri());
            }
        }
    }

    public function test_regular_users_cannot_view_admin_pages_or_moderate_comments(): void
    {
        $comment = ProductComment::factory()->create();
        $this->actingAs(User::factory()->create(['is_admin' => false]));

        foreach (['dashboard', 'product-comments.index', 'orders.index', 'products.index', 'settings.index'] as $route) {
            $this->get(route($route))->assertForbidden();
        }

        $this->patchJson(route('product-comments.update', $comment), ['status' => 1])->assertForbidden();
        $this->delete(route('product-comments.destroy', $comment))->assertForbidden();
        $this->assertSame(0, $comment->fresh()->status);
        $this->assertModelExists($comment);
        $this->get(route('account.profile'))->assertOk();
    }

    public function test_verified_admin_can_open_dashboard_and_moderate_comments(): void
    {
        $comment = ProductComment::factory()->create();
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->get(route('dashboard'))->assertOk();
        $this->get(route('product-comments.index'))->assertOk();
        $this->patchJson(route('product-comments.update', $comment), ['status' => 1])->assertOk();
        $this->assertSame(1, $comment->fresh()->status);
        $this->delete(route('product-comments.destroy', $comment))->assertRedirect();
        $this->assertModelMissing($comment);
    }

    public function test_admin_still_requires_email_verification(): void
    {
        $this->actingAs(User::factory()->unverified()->create(['is_admin' => true]))
            ->get(route('dashboard'))->assertRedirect(route('verification.notice'));
    }

    public function test_registration_cannot_grant_admin_permission(): void
    {
        $this->post('/register', [
            'name' => 'Customer',
            'email' => 'customer@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'is_admin' => true,
        ])->assertRedirect(route('verification.notice'));

        $this->assertFalse(User::where('email', 'customer@example.test')->sole()->is_admin);
    }
}
