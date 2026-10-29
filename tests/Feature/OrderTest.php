<?php

namespace Tests\Feature;

use App\Mail\OrderPlaced;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_csrf_endpoint_returns_current_session_token_without_caching_or_rotating_it(): void
    {
        $token = str_repeat('a', 40);
        $response = $this->withSession(['_token' => $token])->getJson(route('order.csrf-token'));
        $response->assertOk()->assertExactJson(['token' => $token]);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->getJson(route('order.csrf-token'))->assertExactJson(['token' => $token]);
    }

    public function test_csrf_endpoint_initializes_a_new_session(): void
    {
        $response = $this->getJson(route('order.csrf-token'))->assertOk();
        $this->assertIsString($response->json('token'));
        $this->assertSame(40, strlen($response->json('token')));
    }

    public function test_empty_checkout_is_available_to_guests_and_does_not_show_an_order_form(): void
    {
        $this->get(route('order.checkout'))->assertOk()->assertViewIs('site.order')
            ->assertCookie(Cart::CLIENT_COOKIE)->assertSee('checkout-message', false)
            ->assertDontSee('data-checkout-form', false);
    }

    public function test_checkout_shows_only_the_current_clients_cart_and_the_customer_and_payment_fields(): void
    {
        $entry = Cart::factory()->create(['quantity' => 2]);
        $entry->product->update(['price' => 75.25]);
        Cart::factory()->create(['quantity' => 9]);
        $this->withCookie(Cart::CLIENT_COOKIE, $entry->client_id)->get(route('order.checkout'))->assertOk()
            ->assertViewHas('cartItems', fn (array $items): bool => count($items) === 1 && $items[0]['quantity'] === 2)
            ->assertViewHas('cartTotal', '150.50')->assertSee('data-checkout-form', false)
            ->assertSee('name="customer_type"', false)->assertSee('name="company_name"', false)
            ->assertSee('name="legal_address"', false)->assertSee('name="tax_id"', false)->assertSee('name="kpp"', false)
            ->assertSee('type="checkbox" name="payment_method" value="cash"', false)
            ->assertSee('type="checkbox" name="payment_method" value="bank_transfer"', false);
    }

    public function test_individual_order_uses_server_prices_and_quantities_and_clears_only_its_cart(): void
    {
        $entry = Cart::factory()->create(['quantity' => 3]);
        $entry->product->update(['price' => 199.99, 'discount' => 10]);
        $foreign = Cart::factory()->create();
        $this->withCookie(Cart::CLIENT_COOKIE, $entry->client_id)->post(route('order.store'), $this->customerData([
            'total' => '0.01', 'client_id' => $foreign->client_id,
            'items' => [['product_id' => $foreign->product_id, 'quantity' => 999, 'price' => '0.01']],
            'company_name' => 'Ignored', 'legal_address' => 'Ignored', 'tax_id' => 'Ignored', 'kpp' => 'Ignored',
        ]))->assertRedirect(route('order.checkout'))->assertSessionHasNoErrors();
        $order = Order::sole();
        $this->assertSame($entry->client_id, $order->client_id);
        $this->assertSame('individual', $order->customer_type);
        $this->assertSame('Олена', $order->name);
        $this->assertSame('cash', $order->payment_method);
        $this->assertSame('539.97', $order->total);
        $this->assertNull($order->company_name);
        $this->assertNull($order->legal_address);
        $this->assertNull($order->tax_id);
        $this->assertNull($order->kpp);
        $item = $order->items()->sole();
        $this->assertSame($entry->product_id, $item->product_id);
        $this->assertSame(3, $item->quantity);
        $this->assertSame('179.99', $item->price);
        $this->assertSame('539.97', $item->total);
        $this->assertModelMissing($entry);
        $this->assertModelExists($foreign);
        $this->get(route('order.checkout'))->assertOk()->assertSee('checkout-success', false)->assertSee('539.97');
    }

    public function test_company_order_saves_requisites_and_bank_transfer_payment(): void
    {
        $entry = Cart::factory()->create();
        $entry->product->update(['available' => 1]);
        $this->withCookie(Cart::CLIENT_COOKIE, $entry->client_id)->post(route('order.store'), $this->customerData([
            'customer_type' => 'company', 'company_name' => 'ТОВ Приклад', 'legal_address' => 'Київ, Тестова 12',
            'tax_id' => '001234567890', 'kpp' => '001234567', 'name' => 'Олена Петренко', 'payment_method' => 'bank_transfer',
        ]))->assertRedirect(route('order.checkout'))->assertSessionHasNoErrors();
        $order = Order::sole();
        $this->assertSame('ТОВ Приклад', $order->company_name);
        $this->assertSame('Київ, Тестова 12', $order->legal_address);
        $this->assertSame('001234567890', $order->tax_id);
        $this->assertSame('001234567', $order->kpp);
        $this->assertSame('Олена Петренко', $order->name);
        $this->assertSame('bank_transfer', $order->payment_method);
    }

    public function test_company_validation_requires_its_name_contact_person_phone_and_valid_payment(): void
    {
        $entry = Cart::factory()->create();
        $this->withCookie(Cart::CLIENT_COOKIE, $entry->client_id)->from(route('order.checkout'))
            ->post(route('order.store'), $this->customerData([
                'customer_type' => 'company', 'company_name' => '', 'name' => '', 'phone' => '', 'email' => 'invalid', 'payment_method' => 'card',
            ]))->assertRedirect(route('order.checkout'))->assertSessionHasErrors(['company_name', 'name', 'phone', 'email', 'payment_method'])
            ->assertSessionHasInput('customer_type', 'company');
        $this->assertDatabaseCount('orders', 0);
        $this->assertModelExists($entry);
        $this->get(route('order.checkout'))->assertOk()->assertSee('value="company" checked', false);
    }

    public function test_email_and_optional_company_requisites_can_be_omitted(): void
    {
        $entry = Cart::factory()->create();
        $data = $this->customerData(['customer_type' => 'company', 'company_name' => 'Company']);
        unset($data['email']);
        $this->withCookie(Cart::CLIENT_COOKIE, $entry->client_id)->post(route('order.store'), $data)
            ->assertRedirect(route('order.checkout'))->assertSessionHasNoErrors();
        $order = Order::sole();
        $this->assertNull($order->email);
        $this->assertNull($order->legal_address);
        $this->assertNull($order->tax_id);
        $this->assertNull($order->kpp);
    }

    public function test_empty_or_foreign_carts_cannot_be_ordered_using_a_forged_client_id(): void
    {
        $foreign = Cart::factory()->create();
        $this->post(route('order.store'), $this->customerData(['client_id' => $foreign->client_id]))
            ->assertSessionHasErrors('cart');
        $this->assertDatabaseCount('orders', 0);
        $this->assertModelExists($foreign);
    }

    public function test_unavailable_or_unpublished_items_block_the_entire_order_without_clearing_the_cart(): void
    {
        foreach ([['available' => 2], ['public' => false]] as $attributes) {
            $entry = Cart::factory()->create();
            $entry->product->update($attributes);
            $this->withCookie(Cart::CLIENT_COOKIE, $entry->client_id)->get(route('order.checkout'))->assertOk()
                ->assertViewHas('hasUnavailableItems', true);
            $this->post(route('order.store'), $this->customerData())->assertSessionHasErrors('cart');
            $this->assertDatabaseCount('orders', 0);
            $this->assertModelExists($entry);
            $entry->delete();
        }
    }

    public function test_store_rechecks_prices_and_preserves_order_history_after_product_deletion(): void
    {
        $entry = Cart::factory()->create(['quantity' => 2]);
        $entry->product->update(['price' => 100]);
        $this->withCookie(Cart::CLIENT_COOKIE, $entry->client_id)->get(route('order.checkout'))->assertOk()->assertViewHas('cartTotal', '200.00');
        $entry->product->update(['price' => 200, 'discount' => 25]);
        $this->post(route('order.store'), $this->customerData())->assertSessionHasNoErrors();
        $this->assertSame('300.00', Order::sole()->total);
        $item = OrderItem::sole();
        $title = $item->title;
        $entry->product->delete();
        $item->refresh();
        $this->assertNull($item->product_id);
        $this->assertSame($title, $item->title);
        $this->assertSame('150.00', $item->price);
        $this->assertSame('300.00', $item->total);
    }

    public function test_repeated_submission_cannot_create_a_second_order_from_the_cleared_cart(): void
    {
        $entry = Cart::factory()->create();
        $this->withCookie(Cart::CLIENT_COOKIE, $entry->client_id)->post(route('order.store'), $this->customerData())->assertSessionHasNoErrors();
        $this->post(route('order.store'), $this->customerData())->assertSessionHasErrors('cart');
        $this->assertDatabaseCount('orders', 1);
    }

    public function test_order_queues_separate_customer_and_admin_emails_with_saved_details(): void
    {
        Mail::fake();
        Setting::factory()->create(['key' => 'admin_email', 'value' => json_encode('admin@example.test')]);
        $entry = Cart::factory()->create(['quantity' => 2]);
        $entry->product->update(['price' => 75, 'discount' => 0]);
        $this->withCookie(Cart::CLIENT_COOKIE, $entry->client_id)->post(route('order.store'), $this->customerData())
            ->assertSessionHasNoErrors()->assertRedirect(route('order.checkout'));

        $order = Order::sole();
        Mail::assertNothingSent();
        Mail::assertQueued(OrderPlaced::class, fn (OrderPlaced $mail): bool => $mail->hasTo('customer@example.test') && ! $mail->forAdmin && $mail->order->is($order));
        Mail::assertQueued(OrderPlaced::class, fn (OrderPlaced $mail): bool => $mail->hasTo('admin@example.test') && $mail->forAdmin && $mail->order->is($order));
        Mail::assertQueuedCount(2);
        $mail = new OrderPlaced($order->load('items', 'delivery'));
        $mail->assertSeeInHtml($order->items->first()->title);
        $mail->assertSeeInHtml('150.00');
        $mail->assertSeeInHtml($order->phone);
        $mail->assertSeeInHtml(__(Order::DELIVERY_METHODS['pickup']));
        $mail->assertSeeInText('150.00');
    }

    public function test_order_without_customer_email_still_notifies_admin_and_invalid_orders_send_nothing(): void
    {
        Mail::fake();
        Setting::factory()->create(['key' => 'admin_email', 'value' => json_encode('admin@example.test')]);
        $entry = Cart::factory()->create();
        $this->withCookie(Cart::CLIENT_COOKIE, $entry->client_id)->post(route('order.store'), $this->customerData(['email' => null]))
            ->assertSessionHasNoErrors();
        Mail::assertQueued(OrderPlaced::class, fn (OrderPlaced $mail): bool => $mail->hasTo('admin@example.test') && $mail->forAdmin);
        Mail::assertQueuedCount(1);
        $this->post(route('order.store'), $this->customerData())->assertSessionHasErrors('cart');
        Mail::assertQueuedCount(1);
    }

    public function test_queue_failure_preserves_order_and_still_attempts_admin_notification(): void
    {
        Setting::factory()->create(['key' => 'admin_email', 'value' => json_encode('admin@example.test')]);
        $entry = Cart::factory()->create();
        Mail::shouldReceive('to')->once()->with('customer@example.test')->andThrow(new \RuntimeException('Mail unavailable'));
        Mail::shouldReceive('to')->once()->with('admin@example.test')->andReturnSelf();
        Mail::shouldReceive('locale')->once()->with(app()->getLocale())->andReturnSelf();
        Mail::shouldReceive('queue')->once()->with(\Mockery::on(fn (OrderPlaced $mail): bool => $mail->forAdmin));
        $this->withCookie(Cart::CLIENT_COOKIE, $entry->client_id)->post(route('order.store'), $this->customerData())
            ->assertRedirect(route('order.checkout'))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('orders', 1);
        $this->assertModelMissing($entry);
    }

    /** @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function customerData(array $overrides = []): array
    {
        return array_replace([
            'customer_type' => 'individual', 'name' => 'Олена', 'phone' => '+380501234567',
            'email' => 'customer@example.test', 'payment_method' => 'cash', 'delivery_method' => 'pickup',
        ], $overrides);
    }
}
