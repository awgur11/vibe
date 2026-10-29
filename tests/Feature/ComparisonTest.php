<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Catalog;
use App\Models\Comparison;
use App\Models\Filter;
use App\Models\Fvalue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComparisonTest extends TestCase
{
    use RefreshDatabase;

    public function test_comparison_is_saved_without_duplicates_and_grouped_by_category_for_current_client(): void
    {
        $entry = Comparison::factory()->create();
        $otherCategory = Comparison::factory()->create(['client_id' => $entry->client_id]);
        Comparison::factory()->create();
        $this->withCredentials()->withCookie(Cart::CLIENT_COOKIE, $entry->client_id)
            ->postJson(route('comparison.store', $entry->product_id), ['client_id' => 'forged'])->assertOk()
            ->assertJsonCount(2, 'product_ids')->assertJsonCount(2, 'categories')
            ->assertJsonPath('categories.0.url', route('comparison.show', $entry->product->catalog));
        $this->assertDatabaseCount('comparisons', 3);
        $this->deleteJson(route('comparison.destroy', $otherCategory->product_id))->assertOk()->assertJsonCount(1, 'categories');
        $this->assertModelMissing($otherCategory);
    }

    public function test_table_contains_only_clients_products_in_selected_category_and_all_characteristics(): void
    {
        $entry = Comparison::factory()->create();
        $sameCategory = Comparison::factory()->create(['client_id' => $entry->client_id]);
        $sameCategory->product->update(['catalog_id' => $entry->product->catalog_id]);
        Comparison::factory()->create(['client_id' => $entry->client_id]);
        $foreign = Comparison::factory()->create();
        $filter = Filter::factory()->create(['slag' => 'test-feature']);
        $value = Fvalue::factory()->for($filter)->create(['slag' => 'test-value']);
        $entry->product->fvalues()->attach($value);
        $this->withCredentials()->withCookie(Cart::CLIENT_COOKIE, $entry->client_id)->get(route('comparison.show', $entry->product->catalog))
            ->assertOk()->assertViewIs('site.comparison')
            ->assertViewHas('products', fn ($products): bool => $products->modelKeys() === [$entry->product_id, $sameCategory->product_id])
            ->assertSee($filter->title ?: $filter->slag)->assertSee($value->title ?: $value->slag);
        $this->deleteJson(route('comparison.destroy', $foreign->product_id))->assertOk();
        $this->assertModelExists($foreign);
    }

    public function test_hidden_products_and_catalogs_are_not_available_and_product_deletion_cascades(): void
    {
        $entry = Comparison::factory()->create();
        $entry->product->update(['public' => false]);
        $this->withCredentials()->withCookie(Cart::CLIENT_COOKIE, $entry->client_id)->getJson(route('comparison.state'))
            ->assertOk()->assertJson(['product_ids' => [], 'categories' => []]);
        $this->postJson(route('comparison.store', $entry->product_id))->assertNotFound();
        $entry->product->catalog->update(['public' => false]);
        $this->get(route('comparison.show', $entry->product->catalog))->assertNotFound();
        $entry->product->delete();
        $this->assertModelMissing($entry);
    }

    public function test_empty_category_table_and_new_client_state_are_available(): void
    {
        $catalog = Catalog::factory()->create(['public' => true]);
        $this->get(route('comparison.show', $catalog))->assertOk()->assertViewHas('products', fn ($products): bool => $products->isEmpty());
        $this->getJson(route('comparison.state'))->assertOk()->assertCookie(Cart::CLIENT_COOKIE)->assertJson(['product_ids' => [], 'categories' => []]);
    }
}
