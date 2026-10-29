<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_suggestions_match_titles_and_return_at_most_nine_published_products(): void
    {
        $products = Product::factory()->count(11)->create(['public' => true, 'available' => 0]);
        foreach ($products as $product) {
            $product->translations()->update(['title' => 'Test filter '.$product->id]);
        }
        $hidden = Product::factory()->create(['public' => false]);
        $hidden->translations()->update(['title' => 'Test filter hidden']);
        $response = $this->getJson(route('search.suggestions', ['q' => 'filter']))->assertOk()->assertJsonCount(9, 'products');
        $response->assertJsonPath('products.0.url', route('product', ['product' => $products->first()->slag]));
        $this->getJson(route('search.suggestions', ['q' => 'fi']))->assertOk()->assertJson(['products' => []]);
        $this->getJson(route('search.suggestions', ['q' => ['invalid']]))->assertUnprocessable();
    }

    public function test_search_results_paginate_and_preserve_query(): void
    {
        $products = Product::factory()->count(25)->create(['public' => true, 'available' => 0]);
        foreach ($products as $product) {
            $product->translations()->update(['title' => 'Unique query '.$product->id]);
        }
        $this->get(route('search', ['q' => 'Unique']))->assertOk()->assertViewIs('site.search')
            ->assertViewHas('products', fn ($products): bool => $products->count() === 24 && $products->total() === 25)
            ->assertSee('name="q"', false)->assertSee('q=Unique', false);
        $this->get(route('search', ['q' => 'Unique', 'page' => 2]))->assertOk()->assertViewHas('products', fn ($products): bool => $products->count() === 1);
        $this->get(route('search'))->assertOk()->assertViewHas('products', fn ($products): bool => $products->isEmpty());
    }

    public function test_search_treats_sql_wildcards_literally_and_matches_current_language_only(): void
    {
        $product = Product::factory()->create(['public' => true, 'available' => 0]);
        $product->translations()->update(['title' => 'Rate 50% filter']);
        $product->translations()->create(['lacale' => 'en', 'title' => 'Foreign title']);
        $this->getJson(route('search.suggestions', ['q' => '50%']))->assertOk()->assertJsonCount(1, 'products');
        $this->getJson(route('search.suggestions', ['q' => '___']))->assertOk()->assertJsonCount(0, 'products');
        $this->getJson(route('search.suggestions', ['q' => 'Foreign']))->assertOk()->assertJsonCount(0, 'products');
    }
}
