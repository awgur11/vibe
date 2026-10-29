<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Picture;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_visitor_receives_a_private_three_month_cookie(): void
    {
        $this->travelTo(now()->setDate(2026, 1, 31)->startOfDay());
        $response = $this->getJson(route('cart.index'))->assertOk()->assertJson(['items' => [], 'count' => 0, 'total' => '0.00'])->assertCookie(Cart::CLIENT_COOKIE);
        $cookie = $response->getCookie(Cart::CLIENT_COOKIE);
        $this->assertTrue(Str::isUuid($cookie->getValue()));
        $this->assertSame(now()->addMonthsNoOverflow(3)->getTimestamp(), $cookie->getExpiresTime());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('lax', $cookie->getSameSite());
        $this->assertSame('/', $cookie->getPath());
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_addition_stores_client_product_and_quantity_and_ignores_client_supplied_price(): void
    {
        $clientId = (string) Str::uuid();
        $product = Product::factory()->create(['public' => true, 'price' => 150, 'discount' => 20]);
        $this->withCredentials()->withCookie(Cart::CLIENT_COOKIE, $clientId);
        $payload = ['product_id' => $product->id, 'quantity' => 2, 'price' => '0.01', 'client_id' => (string) Str::uuid()];
        $this->postJson(route('cart.store'), $payload)->assertCreated()->assertJsonPath('count', 2)
            ->assertJsonPath('items.0.price', '120.00')->assertJsonPath('total', '240.00');
        $this->postJson(route('cart.store'), ['product_id' => $product->id, 'quantity' => 3])->assertCreated()
            ->assertJsonPath('count', 5)->assertJsonPath('items.0.quantity', 5)->assertJsonPath('total', '600.00');
        $entry = Cart::sole();
        $this->assertSame($clientId, $entry->client_id);
        $this->assertSame($product->id, $entry->product_id);
        $this->assertSame(5, $entry->quantity);
        $this->assertSame(now()->addMonthsNoOverflow(3)->toDateString(), $entry->expires_at->toDateString());
        $this->assertArrayNotHasKey('price', $entry->getAttributes());
    }

    public function test_cart_always_uses_the_current_product_price(): void
    {
        $entry = Cart::factory()->create(['quantity' => 3]);
        $entry->product->update(['price' => 199.99, 'discount' => 10]);
        $this->withCredentials()->withCookie(Cart::CLIENT_COOKIE, $entry->client_id)
            ->getJson(route('cart.index'))->assertOk()->assertJsonPath('items.0.price', '179.99')->assertJsonPath('total', '539.97');
        $entry->product->update(['price' => 99.99, 'discount' => 0]);
        $this->getJson(route('cart.index'))->assertOk()->assertJsonPath('items.0.price', '99.99')->assertJsonPath('total', '299.97');
    }

    public function test_product_modal_data_is_loaded_by_id_and_hidden_products_cannot_be_added(): void
    {
        $product = Product::factory()->create(['public' => true, 'price' => 50]);
        $hidden = Product::factory()->create(['public' => false]);
        $this->getJson(route('cart.product', $product))->assertOk()->assertJsonPath('product.id', $product->id)
            ->assertJsonPath('product.price', '50.00')->assertJsonPath('product.preview', null);
        $this->getJson(route('cart.product', $hidden))->assertNotFound();
        $this->postJson(route('cart.store'), ['product_id' => $hidden->id, 'quantity' => 1])
            ->assertUnprocessable()->assertJsonValidationErrors('product_id');
        $this->assertDatabaseCount('carts', 0);
    }

    public function test_visitors_cannot_read_change_or_delete_another_clients_cart(): void
    {
        $own = Cart::factory()->create(['quantity' => 2]);
        $foreign = Cart::factory()->create(['quantity' => 4]);
        $this->withCredentials()->withCookie(Cart::CLIENT_COOKIE, $own->client_id);
        $this->getJson(route('cart.index'))->assertOk()->assertJsonCount(1, 'items')->assertJsonPath('items.0.id', $own->id)->assertJsonPath('count', 2);
        $this->patchJson(route('cart.update', $foreign), ['quantity' => 1, 'client_id' => $foreign->client_id])->assertNotFound();
        $this->deleteJson(route('cart.destroy', $foreign))->assertNotFound();
        $this->assertSame(4, $foreign->fresh()->quantity);
        $this->assertModelExists($own);
    }

    public function test_cart_quantity_can_be_changed_and_the_item_can_be_removed(): void
    {
        $this->freezeTime();
        $entry = Cart::factory()->create();
        $this->withCredentials()->withCookie(Cart::CLIENT_COOKIE, $entry->client_id);
        $this->patchJson(route('cart.update', $entry), ['quantity' => 5])->assertOk()->assertJsonPath('count', 5);
        $this->patchJson(route('cart.update', $entry), ['quantity' => 5])->assertOk()->assertJsonPath('count', 5);
        $this->assertSame(5, $entry->fresh()->quantity);
        $this->deleteJson(route('cart.destroy', $entry))->assertOk()->assertJson(['items' => [], 'count' => 0, 'total' => '0.00']);
        $this->assertModelMissing($entry);
    }

    public function test_invalid_quantities_and_accumulated_overflow_do_not_change_the_cart(): void
    {
        $entry = Cart::factory()->create(['quantity' => Cart::MAX_QUANTITY]);
        $this->withCredentials()->withCookie(Cart::CLIENT_COOKIE, $entry->client_id);
        foreach ([0, -1, 1.5, Cart::MAX_QUANTITY + 1, 'invalid'] as $quantity) {
            $this->postJson(route('cart.store'), ['product_id' => $entry->product_id, 'quantity' => $quantity])->assertUnprocessable()->assertJsonValidationErrors('quantity');
            $this->patchJson(route('cart.update', $entry), ['quantity' => $quantity])->assertUnprocessable()->assertJsonValidationErrors('quantity');
        }
        $this->postJson(route('cart.store'), ['product_id' => $entry->product_id, 'quantity' => 1])->assertUnprocessable()->assertJsonValidationErrors('quantity');
        $this->assertSame(Cart::MAX_QUANTITY, $entry->fresh()->quantity);
        $this->assertDatabaseCount('carts', 1);
    }

    public function test_expired_items_are_physically_deleted_and_not_returned(): void
    {
        $expired = Cart::factory()->create(['expires_at' => now()->subSecond()]);
        $current = Cart::factory()->create(['client_id' => $expired->client_id]);
        $this->withCredentials()->withCookie(Cart::CLIENT_COOKIE, $current->client_id)
            ->getJson(route('cart.index'))->assertOk()->assertJsonCount(1, 'items')->assertJsonPath('items.0.id', $current->id);
        $this->assertModelMissing($expired);
        $this->assertModelExists($current);
    }

    public function test_scheduled_pruning_removes_items_after_three_months_even_without_visits(): void
    {
        $this->travelTo(now()->setDate(2026, 1, 31)->startOfDay());
        $entry = Cart::factory()->create();
        $this->travelTo(now()->addMonthsNoOverflow(3));
        $current = Cart::factory()->create();
        $this->artisan('model:prune', ['--model' => [Cart::class]])->assertExitCode(0);
        $this->assertModelMissing($entry);
        $this->assertModelExists($current);
    }

    public function test_unpublished_products_are_hidden_and_deleted_products_remove_cart_entries(): void
    {
        $entry = Cart::factory()->create();
        $entry->product->update(['public' => false]);
        $this->withCredentials()->withCookie(Cart::CLIENT_COOKIE, $entry->client_id)->getJson(route('cart.index'))
            ->assertOk()->assertJson(['items' => [], 'count' => 0]);
        $entry->product->delete();
        $this->assertModelMissing($entry);
    }

    public function test_invalid_client_cookie_is_replaced_instead_of_used_as_an_identifier(): void
    {
        $this->withCredentials()->withCookie(Cart::CLIENT_COOKIE, 'invalid')->getJson(route('cart.index'))
            ->assertOk()->assertCookie(Cart::CLIENT_COOKIE);
        $this->assertDatabaseCount('carts', 0);
    }

    public function test_unavailable_products_cannot_be_added_but_preorders_are_allowed(): void
    {
        $unavailable = Product::factory()->create(['public' => true, 'available' => 2]);
        $preorder = Product::factory()->create(['public' => true, 'available' => 1]);
        $this->getJson(route('cart.product', $unavailable))->assertOk()->assertJsonPath('product.can_add_to_cart', false);
        $this->postJson(route('cart.store'), ['product_id' => $unavailable->id, 'quantity' => 1])->assertUnprocessable()->assertJsonValidationErrors('product_id');
        $this->assertDatabaseCount('carts', 0);
        $this->getJson(route('cart.product', $preorder))->assertOk()->assertJsonPath('product.can_add_to_cart', true);
        $this->postJson(route('cart.store'), ['product_id' => $preorder->id, 'quantity' => 1])->assertCreated();
    }

    public function test_existing_unavailable_cart_items_cannot_have_their_quantity_increased(): void
    {
        $entry = Cart::factory()->create();
        $entry->product->update(['available' => 2]);
        $this->withCredentials()->withCookie(Cart::CLIENT_COOKIE, $entry->client_id);
        $this->getJson(route('cart.index'))->assertOk()->assertJsonPath('items.0.can_update', false);
        $this->patchJson(route('cart.update', $entry), ['quantity' => 2])->assertNotFound();
        $this->assertSame(1, $entry->fresh()->quantity);
        $this->deleteJson(route('cart.destroy', $entry))->assertOk();
    }

    public function test_product_cards_disable_unavailable_items_and_use_the_no_image_icon(): void
    {
        $unavailable = Product::factory()->create(['public' => true, 'available' => 2]);
        $card = $this->view('site.details.cards.products', ['products' => collect([$unavailable])]);
        $card->assertSee(' disabled', false)->assertSee('href="#i-no-image"', false);
        $product = Product::factory()->create(['public' => true, 'available' => 0]);
        Picture::factory()->for($product)->create();
        $this->view('site.details.cards.products', ['products' => collect([$product])])
            ->assertSee('<img', false)->assertDontSee('product-image-placeholder', false)->assertDontSee(' disabled', false);
    }
}
