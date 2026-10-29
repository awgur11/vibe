<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_pages_require_authentication(): void
    {
        $this->get(route('account.profile'))->assertRedirect(route('login'));
        $this->get(route('account.orders'))->assertRedirect(route('login'));
    }

    public function test_authenticated_menu_contains_links_and_conditional_verification(): void
    {
        $user = User::factory()->unverified()->create();
        $this->actingAs($user)->get(route('index'))->assertOk()->assertSee('data-account-toggle', false)
            ->assertSee('id="accountDropdown" hidden', false)->assertSee(route('account.profile'))->assertSee(route('account.orders'))->assertSee('class="account-verify"', false);
        $this->get(route('account.profile'))->assertOk()->assertSee($user->email);
        $user->markEmailAsVerified();
        $this->actingAs($user->fresh())->get(route('index'))->assertOk()->assertDontSee('class="account-verify"', false);
    }

    public function test_orders_page_only_lists_orders_owned_by_account(): void
    {
        $user = User::factory()->create();
        $own = Order::factory()->create(['user_id' => $user->id]);
        Order::factory()->create(['user_id' => User::factory()->create()->id]);
        Order::factory()->create(['email' => $user->email]);
        $this->actingAs($user)->get(route('account.orders'))->assertOk()
            ->assertViewHas('orders', fn ($orders): bool => $orders->modelKeys() === [$own->id]);
    }

    public function test_checkout_assigns_authenticated_owner_and_ignores_submitted_user_id(): void
    {
        $user = User::factory()->create();
        $cart = Cart::factory()->create();
        $this->actingAs($user)->withCookie(Cart::CLIENT_COOKIE, $cart->client_id)->post(route('order.store'), [
            'user_id' => 999, 'customer_type' => 'individual', 'name' => 'Customer', 'phone' => '+380501234567', 'payment_method' => 'cash', 'delivery_method' => 'pickup',
        ])->assertSessionHasNoErrors();
        $this->assertEquals($user->id, Order::sole()->user_id);
    }
}
