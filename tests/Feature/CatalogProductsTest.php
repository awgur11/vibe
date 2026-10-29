<?php

namespace Tests\Feature;

use App\Models\Catalog;
use App\Models\Filter;
use App\Models\Fvalue;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogProductsTest extends TestCase
{
    use RefreshDatabase;

    public function test_leaf_catalog_lists_only_its_published_products_twenty_four_per_page(): void
    {
        $catalog = Catalog::factory()->create(['public' => true]);
        $products = Product::factory()->count(25)->sequence(fn ($sequence): array => ['position' => $sequence->index])
            ->create(['catalog_id' => $catalog->id, 'public' => true]);
        Product::factory()->create(['catalog_id' => $catalog->id, 'public' => false]);
        Product::factory()->for(Catalog::factory())->create(['public' => true]);
        $url = route('catalog', ['catalogSlag' => $catalog->slag]);
        $this->get($url)->assertOk()->assertViewHas('products', fn ($records): bool => $records->total() === 25
            && $records->perPage() === 24 && $records->modelKeys() === $products->take(24)->modelKeys())
            ->assertSee('catalog-filter-panel', false)->assertSee('catalog-products-grid', false)
            ->assertSee('data-favorite="p'.$products->first()->id.'"', false)
            ->assertSee('data-compare="p'.$products->first()->id.'"', false)
            ->assertSee('?page=2#products', false);
        $this->get($url.'?page=2')->assertOk()
            ->assertViewHas('products', fn ($records): bool => $records->modelKeys() === [$products->last()->id]);
    }

    public function test_filters_use_or_within_each_filter_and_and_between_filters(): void
    {
        $catalog = Catalog::factory()->create(['public' => true]);
        $brand = Filter::factory()->create();
        $size = Filter::factory()->create();
        $catalog->filters()->attach([$brand->id, $size->id]);
        $bosch = Fvalue::factory()->for($brand)->create();
        $mann = Fvalue::factory()->for($brand)->create();
        $small = Fvalue::factory()->for($size)->create();
        $large = Fvalue::factory()->for($size)->create();
        $catalog->fvalues()->attach([$bosch->id, $mann->id, $small->id, $large->id]);
        $first = Product::factory()->for($catalog)->create(['public' => true, 'position' => 1]);
        $first->fvalues()->attach([$bosch->id, $small->id]);
        $second = Product::factory()->for($catalog)->create(['public' => true, 'position' => 2]);
        $second->fvalues()->attach([$mann->id, $small->id]);
        $third = Product::factory()->for($catalog)->create(['public' => true, 'position' => 3]);
        $third->fvalues()->attach([$bosch->id, $large->id]);
        $url = route('catalog', ['catalogSlag' => $catalog->slag]);
        $this->get($url.'?'.http_build_query(['fvalues' => [$bosch->id, $mann->id]]))->assertOk()
            ->assertViewHas('products', fn ($records): bool => $records->modelKeys() === [$first->id, $second->id, $third->id]);
        $this->get($url.'?'.http_build_query(['fvalues' => [$bosch->id, $mann->id, $small->id]]))->assertOk()
            ->assertViewHas('products', fn ($records): bool => $records->modelKeys() === [$first->id, $second->id])
            ->assertViewHas('selectedValueIds', [$bosch->id, $mann->id, $small->id]);
    }

    public function test_sidebar_only_uses_filters_and_values_assigned_to_this_catalog(): void
    {
        $catalog = Catalog::factory()->create(['public' => true]);
        $filter = Filter::factory()->create();
        $foreignFilter = Filter::factory()->create();
        $catalog->filters()->attach($filter);
        $allowed = Fvalue::factory()->for($filter)->create();
        $unassignedValue = Fvalue::factory()->for($filter)->create();
        $foreignValue = Fvalue::factory()->for($foreignFilter)->create();
        $catalog->fvalues()->attach([$allowed->id, $foreignValue->id]);
        $url = route('catalog', ['catalogSlag' => $catalog->slag]);
        $this->get($url)->assertOk()->assertViewHas('filters', fn ($filters): bool => $filters->modelKeys() === [$filter->id]
            && $filters->first()->fvalues->modelKeys() === [$allowed->id]);
        foreach ([$unassignedValue->id, $foreignValue->id, 999999] as $invalidId) {
            $this->getJson($url.'?'.http_build_query(['fvalues' => [$invalidId]]))->assertUnprocessable()->assertJsonValidationErrors('fvalues.0');
        }
        $this->getJson($url.'?fvalues=invalid')->assertUnprocessable();
    }

    public function test_filter_selection_survives_pagination(): void
    {
        $catalog = Catalog::factory()->create(['public' => true]);
        $filter = Filter::factory()->create();
        $value = Fvalue::factory()->for($filter)->create();
        $catalog->filters()->attach($filter);
        $catalog->fvalues()->attach($value);
        Product::factory()->count(25)->for($catalog)->create(['public' => true])->each(function (Product $product) use ($value): void {
            $product->fvalues()->attach($value);
        });
        $url = route('catalog', ['catalogSlag' => $catalog->slag]);
        $this->get($url.'?'.http_build_query(['fvalues' => [$value->id]]))->assertOk()
            ->assertViewHas('products', fn ($records): bool => str_contains($records->nextPageUrl(), 'fvalues%5B0%5D='.$value->id))
            ->assertSee('value="'.$value->id.'" checked', false);
    }

    public function test_parent_catalog_shows_subcatalogs_instead_of_products(): void
    {
        $catalog = Catalog::factory()->create(['public' => true]);
        Catalog::factory()->create(['parent_id' => $catalog->id, 'public' => true]);
        Product::factory()->for($catalog)->create(['public' => true]);
        $this->get(route('catalog', ['catalogSlag' => $catalog->slag]))->assertOk()
            ->assertSee('catalog-categories-grid', false)->assertDontSee('catalog-filter-panel', false)
            ->assertViewMissing('products');
    }

    public function test_mainpage_and_catalog_share_cards_and_escape_product_attributes(): void
    {
        $catalog = Catalog::factory()->create(['public' => true]);
        $product = Product::factory()->for($catalog)->create(['public' => true, 'popular' => true, 'price' => 1250]);
        $product->translations()->update(['title' => '<script>Part</script>']);
        foreach ([route('index'), route('catalog', ['catalogSlag' => $catalog->slag])] as $url) {
            $this->get($url)->assertOk()->assertSee('data-id="p'.$product->id.'"', false)
                ->assertSee('data-price="1250.00"', false)
                ->assertSee('&lt;script&gt;Part&lt;/script&gt;', false)->assertDontSee('<script>Part</script>', false)
                ->assertSee('product-image-placeholder', false);
        }
    }

    public function test_selected_filter_buttons_remove_one_value_and_reset_all_appears_only_for_multiple_values(): void
    {
        $catalog = Catalog::factory()->create(['public' => true]);
        $filter = Filter::factory()->create();
        $first = Fvalue::factory()->for($filter)->create();
        $second = Fvalue::factory()->for($filter)->create();
        $catalog->filters()->attach($filter);
        $catalog->fvalues()->attach([$first->id, $second->id]);
        $firstProduct = Product::factory()->for($catalog)->create(['public' => true]);
        $firstProduct->fvalues()->attach($first);
        $secondProduct = Product::factory()->for($catalog)->create(['public' => true]);
        $secondProduct->fvalues()->attach($second);
        $url = route('catalog', ['catalogSlag' => $catalog->slag]);
        $response = $this->get($url.'?'.http_build_query(['page' => 2, 'fvalues' => [$first->id, $second->id]]))
            ->assertOk()->assertSee('data-catalog-filters', false)
            ->assertSee('class="catalog-reset-filters"', false)
            ->assertDontSee('catalog-filter-actions', false)
            ->assertViewHas('activeFilters', fn (array $filters): bool => count($filters) === 2);
        $activeFilters = $response->viewData('activeFilters');
        $removeFirstUrl = collect($activeFilters)->firstWhere('id', $first->id)['url'];
        $this->assertStringNotContainsString('page=', $removeFirstUrl);
        $this->get($removeFirstUrl)->assertOk()
            ->assertViewHas('products', fn ($products): bool => $products->modelKeys() === [$secondProduct->id])
            ->assertViewHas('selectedValueIds', [$second->id])
            ->assertDontSee('class="catalog-reset-filters"', false)
            ->assertViewHas('activeFilters', fn (array $filters): bool => count($filters) === 1 && $filters[0]['url'] === $url);
        $this->get($url)->assertOk()->assertViewHas('activeFilters', [])
            ->assertViewHas('products', fn ($products): bool => $products->total() === 2)
            ->assertDontSee('class="catalog-active-filters"', false);
    }

    public function test_all_sort_orders_keep_availability_as_the_primary_order_and_use_discounted_prices(): void
    {
        $catalog = Catalog::factory()->create(['public' => true]);
        $regular = Product::factory()->for($catalog)->create(['public' => true, 'available' => 0, 'price' => 100, 'position' => 1]);
        $popular = Product::factory()->for($catalog)->create(['public' => true, 'available' => 0, 'price' => 500, 'popular' => true, 'position' => 3]);
        $discounted = Product::factory()->for($catalog)->create(['public' => true, 'available' => 0, 'price' => 300, 'discount' => 50, 'position' => 2]);
        $preorder = Product::factory()->for($catalog)->create(['public' => true, 'available' => 1, 'price' => 20, 'popular' => true]);
        $unavailable = Product::factory()->for($catalog)->create(['public' => true, 'available' => 2, 'price' => 1, 'popular' => true]);
        $url = route('catalog', ['catalogSlag' => $catalog->slag]);
        foreach ([
            '' => [$popular->id, $regular->id, $discounted->id, $preorder->id, $unavailable->id],
            'popular' => [$popular->id, $regular->id, $discounted->id, $preorder->id, $unavailable->id],
            'price_asc' => [$regular->id, $discounted->id, $popular->id, $preorder->id, $unavailable->id],
            'price_desc' => [$popular->id, $discounted->id, $regular->id, $preorder->id, $unavailable->id],
        ] as $sort => $expectedIds) {
            $this->get($url.($sort === '' ? '' : '?sort='.$sort))->assertOk()
                ->assertViewHas('products', fn ($records): bool => $records->modelKeys() === $expectedIds)
                ->assertSee('form="catalog-filter-form" data-catalog-sort', false);
        }
        $this->getJson($url.'?sort=invalid')->assertUnprocessable()->assertJsonValidationErrors('sort');
    }

    public function test_sort_is_preserved_when_paginating_removing_filters_and_resetting_all(): void
    {
        $catalog = Catalog::factory()->create(['public' => true]);
        $filter = Filter::factory()->create();
        $first = Fvalue::factory()->for($filter)->create();
        $second = Fvalue::factory()->for($filter)->create();
        $catalog->filters()->attach($filter);
        $catalog->fvalues()->attach([$first->id, $second->id]);
        Product::factory()->count(25)->for($catalog)->create(['public' => true])->each(function (Product $product) use ($first, $second): void {
            $product->fvalues()->attach([$first->id, $second->id]);
        });
        $url = route('catalog', ['catalogSlag' => $catalog->slag]);
        $response = $this->get($url.'?'.http_build_query(['sort' => 'price_desc', 'fvalues' => [$first->id, $second->id]]))->assertOk()
            ->assertViewHas('sort', 'price_desc')
            ->assertViewHas('products', fn ($records): bool => str_contains($records->nextPageUrl(), 'sort=price_desc'))
            ->assertSee('class="catalog-reset-filters" href="'.$url.'?sort=price_desc#products"', false);
        foreach ($response->viewData('activeFilters') as $activeFilter) {
            $this->assertStringContainsString('sort=price_desc', $activeFilter['url']);
            $this->get($activeFilter['url'])->assertOk()->assertViewHas('sort', 'price_desc');
        }
    }
}
