<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderDelivery;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_orders_or_change_status(): void
    {
        $order = Order::factory()->create();
        $this->get(route('orders.index'))->assertRedirect(route('login'));
        $this->get(route('orders.show', $order))->assertRedirect(route('login'));
        $this->patch(route('orders.update', $order), ['status' => 'completed'])->assertRedirect(route('login'));
        $this->assertSame('new', $order->fresh()->status);
    }

    public function test_orders_are_paginated_newest_first(): void
    {
        Order::factory()->count(26)->create();
        $latest = Order::latest('id')->first();
        $this->actingAs(User::factory()->create())->get(route('orders.index'))->assertOk()
            ->assertViewHas('orders', fn ($orders): bool => $orders->count() === 25 && $orders->total() === 26 && $orders->first()->is($latest))
            ->assertSee(route('orders.show', $latest));
        $this->get(route('orders.index', ['page' => 2]))->assertOk()
            ->assertViewHas('orders', fn ($orders): bool => $orders->count() === 1);
    }

    public function test_order_details_show_saved_items_company_and_delivery_even_after_product_deletion(): void
    {
        $order = Order::factory()->create(['customer_type' => 'company', 'company_name' => 'Company Test', 'legal_address' => 'Legal Address', 'tax_id' => '12345678', 'kpp' => '87654321']);
        $item = OrderItem::factory()->for($order)->create(['title' => 'Saved product', 'articule' => 'ABC-123']);
        $item->product->delete();
        OrderDelivery::factory()->for($order)->create(['delivery_method' => 'nova_poshta', 'pickup_address' => null, 'delivery_city' => 'Київ', 'delivery_warehouse' => 'Відділення №12']);
        $this->actingAs(User::factory()->create())->get(route('orders.show', $order))->assertOk()
            ->assertSee('Saved product')->assertSee('ABC-123')->assertSee('Company Test')->assertSee('Legal Address')
            ->assertSee('12345678')->assertSee('87654321')->assertSee('Київ')->assertSee('Відділення №12')->assertSee($order->email);
    }

    public function test_pickup_address_and_legacy_orders_without_delivery_are_supported(): void
    {
        $order = Order::factory()->create();
        $this->actingAs(User::factory()->create())->get(route('orders.show', $order))->assertOk();
        OrderDelivery::factory()->for($order)->create(['pickup_address' => 'Shop Address']);
        $this->get(route('orders.show', $order))->assertOk()->assertSee('Shop Address');
    }

    public function test_only_valid_status_can_be_updated_without_changing_order_prices(): void
    {
        $order = Order::factory()->create(['total' => '150.00']);
        $this->actingAs(User::factory()->create())->patch(route('orders.update', $order), ['status' => 'processing', 'total' => '1.00'])
            ->assertRedirect(route('orders.show', $order))->assertSessionHasNoErrors();
        $this->assertSame('processing', $order->fresh()->status);
        $this->assertSame('150.00', $order->fresh()->total);
        $this->patch(route('orders.update', $order), ['status' => 'invalid'])->assertSessionHasErrors('status');
        $this->assertSame('processing', $order->fresh()->status);
    }

    public function test_unknown_order_returns_not_found(): void
    {
        $this->actingAs(User::factory()->create())->get(route('orders.show', 999))->assertNotFound();
    }
}
