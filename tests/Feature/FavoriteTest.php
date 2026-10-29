<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Favorite;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_receives_client_cookie_and_can_add_product_without_duplicates(): void
    {
        $product = Product::factory()->create(['public' => true]);
        $clientId = (string) Str::uuid();
        $this->withCredentials()->withCookie(Cart::CLIENT_COOKIE, $clientId)->postJson(route('favorites.store', $product), ['client_id' => 'forged'])
            ->assertOk()->assertCookie(Cart::CLIENT_COOKIE)->assertJson(['product_ids' => [$product->id]]);
        $this->postJson(route('favorites.store', $product))->assertOk();
        $this->assertDatabaseCount('favorites', 1);
        $this->assertDatabaseHas('favorites', ['client_id' => $clientId, 'product_id' => $product->id]);
    }

    public function test_favorites_page_and_state_are_isolated_by_client_and_hide_unpublished_products(): void
    {
        $favorite = Favorite::factory()->create();
        $foreign = Favorite::factory()->create();
        $hidden = Favorite::factory()->create(['client_id' => $favorite->client_id]);
        $hidden->product->update(['public' => false]);
        $this->withCredentials()->withCookie(Cart::CLIENT_COOKIE, $favorite->client_id)->get(route('favorites.index'))->assertOk()->assertViewIs('site.favorites')
            ->assertViewHas('products', fn ($products): bool => $products->modelKeys() === [$favorite->product_id]);
        $this->getJson(route('favorites.state'))->assertJson(['product_ids' => [$favorite->product_id]]);
        $this->deleteJson(route('favorites.destroy', $foreign->product_id))->assertOk();
        $this->assertModelExists($foreign);
        $this->deleteJson(route('favorites.destroy', $favorite->product_id))->assertJson(['product_ids' => []]);
        $this->assertModelMissing($favorite);
    }

    public function test_unpublished_products_cannot_be_added_and_product_deletion_removes_favorite(): void
    {
        $product = Product::factory()->create(['public' => false]);
        $this->postJson(route('favorites.store', $product))->assertNotFound();
        $favorite = Favorite::factory()->create();
        $favorite->product->delete();
        $this->assertModelMissing($favorite);
    }

    public function test_new_client_can_open_empty_favorites_page(): void
    {
        $this->get(route('favorites.index'))->assertOk()->assertCookie(Cart::CLIENT_COOKIE)
            ->assertViewHas('products', fn ($products): bool => $products->isEmpty());
        $this->getJson(route('favorites.state'))->assertOk()->assertJson(['product_ids' => []]);
    }
}
