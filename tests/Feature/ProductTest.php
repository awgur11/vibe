<?php

namespace Tests\Feature;

use App\Models\Catalog;
use App\Models\Fvalue;
use App\Models\Language;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_can_be_searched_by_title_article_and_slug(): void
    {
        $product = Product::factory()->create(['articule' => 'BRAKE-742', 'slag' => 'ceramic-brake']);
        $product->translations()->update(['title' => 'Premium brake pads']);
        $other = Product::factory()->create(['articule' => 'OIL-999', 'slag' => 'engine-oil']);
        $other->translations()->update(['title' => 'Engine oil']);
        $this->actingAs(User::factory()->create());

        foreach (['Premium', '742', 'ceramic', '  Premium  '] as $search) {
            $this->get(route('products.index', ['search' => $search]))->assertOk()
                ->assertViewHas('products', fn ($products): bool => $products->modelKeys() === [$product->id])
                ->assertDontSee('<th>Позиція</th>', false);
        }
        $this->get(route('products.index', ['search' => 'missing-product']))->assertOk()
            ->assertSee('Товарів не знайдено.');
        $this->get(route('products.index', ['search' => '   ']))->assertOk()
            ->assertViewHas('products', fn ($products): bool => $products->total() === 2);
        $this->getJson(route('products.index', ['search' => ['invalid']]))->assertUnprocessable();
    }

    public function test_product_search_is_preserved_in_pagination(): void
    {
        Product::factory()->count(21)->create()->each(function (Product $product): void {
            $product->translations()->update(['title' => 'Matching product']);
        });
        Product::factory()->create()->translations()->update(['title' => 'Unrelated']);
        $this->actingAs(User::factory()->create())
            ->get(route('products.index', ['search' => 'Matching']))->assertOk()
            ->assertViewHas('products', fn ($products): bool => $products->total() === 21
                && str_contains($products->nextPageUrl(), 'search=Matching'));
    }

    public function test_availability_defaults_to_in_stock_and_only_accepts_supported_statuses(): void
    {
        $catalog = Catalog::factory()->create();
        $product = Product::factory()->create()->refresh();
        $this->assertSame(0, $product->available);
        $this->actingAs(User::factory()->create());
        $this->get(route('products.edit', $product))->assertOk()->assertSee('name="available"', false);
        foreach ([1, 2, 0] as $status) {
            $this->put(route('products.update', $product), [...$this->payload($catalog), 'available' => (string) $status])->assertSessionHasNoErrors();
            $this->assertSame($status, $product->fresh()->available);
            $this->get(route('products.index'))->assertOk()->assertSee(Product::AVAILABILITY[$status]);
        }
        foreach ([-1, 3, '1.5', 'invalid', null] as $status) {
            $this->put(route('products.update', $product), [...$this->payload($catalog), 'available' => $status])->assertSessionHasErrors('available');
            $this->assertSame(0, $product->fresh()->available);
        }
        $product->update(['available' => 2]);
        $this->put(route('products.update', $product), $this->payload($catalog))->assertSessionHasNoErrors();
        $this->assertSame(2, $product->fresh()->available);
    }

    public function test_publication_and_position_are_saved_validated_and_used_for_sorting(): void
    {
        $catalog = Catalog::factory()->create();
        $first = Product::factory()->create(['position' => 4]);
        $second = Product::factory()->create(['position' => 2]);
        $this->actingAs(User::factory()->create());
        $this->get(route('products.index'))->assertOk()->assertViewHas('products', fn ($products): bool => $products->modelKeys() === [$second->id, $first->id]);
        $this->put(route('products.update', $first), [...$this->payload($catalog), 'public' => 1, 'position' => 1])->assertSessionHasNoErrors();
        $this->assertTrue($first->fresh()->public);
        $this->assertSame(1, $first->fresh()->position);
        $this->get(route('products.index'))->assertOk()->assertViewHas('products', fn ($products): bool => $products->modelKeys() === [$first->id, $second->id]);
        $this->get(route('products.edit', $first))->assertOk()->assertSee('name="position"', false)->assertSee('name="public"', false);
        foreach ([['position' => -1], ['position' => 4294967296], ['position' => '1.5'], ['public' => 'invalid']] as $change) {
            $this->put(route('products.update', $first), [...$this->payload($catalog), ...$change])->assertSessionHasErrors(array_keys($change));
        }
        $this->assertTrue($first->fresh()->public);
        $this->assertSame(1, $first->fresh()->position);
        $this->put(route('products.update', $first), $this->payload($catalog))->assertSessionHasNoErrors();
        $this->assertFalse($first->fresh()->public);
        $this->assertSame(1, $first->fresh()->position);
    }

    public function test_empty_or_omitted_position_preserves_current_position_and_zero_is_accepted(): void
    {
        $catalog = Catalog::factory()->create();
        $product = Product::factory()->create(['position' => 7]);
        $this->actingAs(User::factory()->create());
        foreach ([[], ['position' => ''], ['position' => null]] as $change) {
            $this->put(route('products.update', $product), [...$this->payload($catalog), ...$change])->assertSessionHasNoErrors();
            $this->assertSame(7, $product->fresh()->position);
        }
        $this->put(route('products.update', $product), [...$this->payload($catalog), 'position' => 0])->assertSessionHasNoErrors();
        $this->assertSame(0, $product->fresh()->position);
    }

    public function test_title_only_creation_makes_an_unpublished_product_at_the_end(): void
    {
        Product::factory()->create(['position' => 8]);
        $this->actingAs(User::factory()->create());
        $this->post(route('products.store'), ['title' => 'Новий', 'public' => 1, 'position' => 0])->assertSessionHasNoErrors();
        $product = Product::where('slag', 'novyy')->sole();
        $this->assertFalse($product->public);
        $this->assertSame(9, $product->position);
    }

    public function test_articule_is_optional_and_saves_as_a_string(): void
    {
        $catalog = Catalog::factory()->create();
        $product = Product::factory()->create();
        $this->actingAs(User::factory()->create());
        $this->put(route('products.update', $product), [...$this->payload($catalog), 'articule' => '001-ABC'])->assertSessionHasNoErrors();
        $this->assertSame('001-ABC', $product->fresh()->articule);
        $this->get(route('products.edit', $product))->assertOk()->assertSee('name="articule"', false)->assertSee('001-ABC');
        $this->put(route('products.update', $product), [...$this->payload($catalog), 'articule' => str_repeat('x', 256)])->assertSessionHasErrors('articule');
        $this->assertSame('001-ABC', $product->fresh()->articule);
        $this->put(route('products.update', $product), [...$this->payload($catalog), 'articule' => ''])->assertSessionHasNoErrors();
        $this->assertNull($product->fresh()->articule);
    }

    public function test_guests_cannot_manage_products_or_load_catalog_filters(): void
    {
        $product = Product::factory()->create();
        $catalog = Catalog::factory()->create();
        $this->get(route('products.index'))->assertRedirect(route('login'));
        $this->get(route('products.edit', $product))->assertRedirect(route('login'));
        $this->get(route('products.catalog-filters', $catalog))->assertRedirect(route('login'));
        $this->post(route('products.store'), ['title' => 'Товар'])->assertRedirect(route('login'));
        $this->put(route('products.update', $product), [])->assertRedirect(route('login'));
        $this->delete(route('products.destroy', $product))->assertRedirect(route('login'));
        $this->assertModelExists($product);
    }

    public function test_title_only_creation_generates_a_unique_slag_and_redirects_to_edit(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get(route('products.index'))->assertOk()->assertSee('product-create-dialog');
        $response = $this->post(route('products.store'), ['title' => 'Новий товар', 'price' => 999, 'discount' => 50]);
        $product = Product::sole();
        $response->assertRedirect(route('products.edit', $product))->assertSessionHasNoErrors();
        $this->assertNull($product->catalog_id);
        $this->assertSame('novyy-tovar', $product->slag);
        $this->assertSame('0.00', $product->price);
        $this->assertSame('0.00', $product->final_price);
        $this->assertSame('Новий товар', $product->translated('title', Language::ensureBase()));
        $this->post(route('products.store'), ['title' => 'Новий товар'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('products', ['slag' => 'novyy-tovar-2']);
        $this->post(route('products.store'), ['title' => '   '])->assertSessionHasErrors('title');
        $this->get(route('products.edit', $product))->assertOk()->assertSee('name="catalog_id"', false);
    }

    public function test_product_saves_translations_catalog_values_and_calculates_final_price(): void
    {
        $catalog = Catalog::factory()->create();
        $value = Fvalue::factory()->create();
        $catalog->filters()->attach($value->filter);
        $catalog->fvalues()->attach($value);
        $other = Language::factory()->create(['active' => false]);
        $product = Product::factory()->create();
        $this->actingAs(User::factory()->create());
        $payload = $this->payload($catalog);
        $payload['fvalues'] = [$value->id];
        $payload['languages'][$other->id] = ['title' => 'Other title', 'description' => 'Other description', 'tag_title' => 'SEO title', 'tag_description' => 'SEO description'];
        $payload['final_price'] = 1;
        $this->put(route('products.update', $product), $payload)->assertRedirect(route('products.edit', $product))->assertSessionHasNoErrors();
        $product->refresh();
        $this->assertSame($catalog->id, $product->catalog_id);
        $this->assertSame('199.99', $product->price);
        $this->assertSame('12.50', $product->discount);
        $this->assertSame('174.99', $product->final_price);
        $this->assertTrue($product->fvalues->contains($value));
        $this->assertTrue($value->fresh()->products->contains($product));
        $this->assertTrue($catalog->fresh()->products->contains($product));
        $this->assertSame('Other description', $product->translated('description', $other));
        $this->assertSame('SEO title', $product->translated('tag_title', $other));
        $this->get(route('products.edit', $product))->assertOk()->assertSee('name="fvalues[]"', false)
            ->assertSee('languages['.$other->id.'][description]', false);
        $this->put(route('products.update', $product), $this->payload($catalog))->assertSessionHasNoErrors();
        $this->assertSame('SEO description', $product->fresh()->translated('tag_description', $other));
        $this->assertCount(0, $product->fresh()->fvalues);
    }

    public function test_catalog_filter_loading_exposes_only_catalog_values_of_selected_filters(): void
    {
        $catalog = Catalog::factory()->create();
        $allowed = Fvalue::factory()->create();
        $unavailable = Fvalue::factory()->for($allowed->filter)->create();
        $unselectedFilterValue = Fvalue::factory()->create();
        $catalog->filters()->attach($allowed->filter);
        $catalog->fvalues()->attach([$allowed->id, $unselectedFilterValue->id]);
        $this->actingAs(User::factory()->create());
        $this->get(route('products.catalog-filters', $catalog))->assertOk()
            ->assertViewHas('filters', function ($filters) use ($allowed): bool {
                return $filters->modelKeys() === [$allowed->filter_id] && $filters->sole()->fvalues->modelKeys() === [$allowed->id];
            });
        $product = Product::factory()->create();
        foreach ([$unavailable->id, $unselectedFilterValue->id] as $id) {
            $this->put(route('products.update', $product), [...$this->payload($catalog), 'fvalues' => [$id]])->assertSessionHasErrors('fvalues');
        }
        $this->assertNull($product->fresh()->catalog_id);
    }

    public function test_catalog_changes_reject_stale_values_and_replace_links_on_valid_save(): void
    {
        $oldCatalog = Catalog::factory()->create();
        $newCatalog = Catalog::factory()->create();
        $oldValue = Fvalue::factory()->create();
        $newValue = Fvalue::factory()->create();
        foreach ([[$oldCatalog, $oldValue], [$newCatalog, $newValue]] as [$catalog, $value]) {
            $catalog->filters()->attach($value->filter);
            $catalog->fvalues()->attach($value);
        }
        $product = Product::factory()->for($oldCatalog)->create();
        $product->fvalues()->attach($oldValue);
        $this->actingAs(User::factory()->create());
        $this->put(route('products.update', $product), [...$this->payload($newCatalog), 'fvalues' => [$oldValue->id]])->assertSessionHasErrors('fvalues');
        $this->assertSame($oldCatalog->id, $product->fresh()->catalog_id);
        $this->assertSame([$oldValue->id], $product->fresh()->fvalues->modelKeys());
        $this->put(route('products.update', $product), [...$this->payload($newCatalog), 'fvalues' => [$newValue->id]])->assertSessionHasNoErrors();
        $this->assertSame($newCatalog->id, $product->fresh()->catalog_id);
        $this->assertSame([$newValue->id], $product->fresh()->fvalues->modelKeys());
    }

    public function test_invalid_prices_slags_and_translations_are_rejected(): void
    {
        $catalog = Catalog::factory()->create();
        $product = Product::factory()->create();
        $other = Product::factory()->create();
        $this->actingAs(User::factory()->create());
        $payload = $this->payload($catalog);
        foreach ([['price' => -1], ['price' => '1.001'], ['discount' => 101], ['discount' => -1], ['discount' => '0.001'], ['catalog_id' => 999999], ['slag' => $other->slag], ['slag' => '../bad']] as $change) {
            $this->put(route('products.update', $product), [...$payload, ...$change])->assertSessionHasErrors(array_keys($change));
        }
        $base = Language::ensureBase();
        $payload['languages'][$base->id]['title'] = '';
        $this->put(route('products.update', $product), $payload)->assertSessionHasErrors('languages.'.$base->id.'.title');
        $this->assertSame('0.00', $product->fresh()->price);
    }

    public function test_price_rounding_and_discount_boundaries(): void
    {
        foreach ([['0.05', '10.00', '0.05'], ['0.03', '50.00', '0.02'], ['123.45', '0', '123.45'], ['123.45', '100', '0.00'], ['9999999999.99', '0', '9999999999.99']] as [$price, $discount, $expected]) {
            $product = Product::factory()->create(['price' => $price, 'discount' => $discount]);
            $this->assertSame($expected, $product->fresh()->final_price);
        }
    }

    public function test_catalog_seo_fields_are_multilingual(): void
    {
        $catalog = Catalog::factory()->create();
        $base = Language::ensureBase();
        $other = Language::factory()->create();
        $this->actingAs(User::factory()->create());
        $payload = ['public' => 0, 'languages' => [
            $base->id => ['title' => 'Каталог', 'tag_title' => 'SEO', 'tag_description' => 'SEO опис'],
            $other->id => ['title' => 'Catalog', 'tag_title' => 'Other SEO', 'tag_description' => 'Other description'],
        ]];
        $this->put(route('catalogs.update', $catalog), $payload)->assertSessionHasNoErrors();
        $this->assertSame('SEO', $catalog->fresh()->translated('tag_title', $base));
        $this->assertSame('Other description', $catalog->fresh()->translated('tag_description', $other));
        app()->setLocale($base->locale);
        $this->assertSame('SEO', $catalog->fresh()->tag_title);
        $this->get(route('catalogs.edit', $catalog))->assertOk()->assertSee('languages['.$other->id.'][tag_title]', false);
    }

    public function test_deleting_a_product_cascades_pivots_and_translations_and_preserves_catalog(): void
    {
        $product = Product::factory()->for(Catalog::factory())->create();
        $value = Fvalue::factory()->create();
        $product->fvalues()->attach($value);
        $this->actingAs(User::factory()->create());
        $this->delete(route('products.destroy', $product))->assertRedirect(route('products.index'));
        $this->assertModelMissing($product);
        $this->assertDatabaseMissing('fvalue_product', ['product_id' => $product->id]);
        $this->assertDatabaseMissing('products_languages', ['product_id' => $product->id]);
        $this->assertModelExists($value);
        $this->assertModelExists($product->catalog);
    }

    /** @return array{public: int, catalog_id: int, price: string, discount: string, languages: array<int, array<string, string>>} */
    private function payload(Catalog $catalog): array
    {
        return ['public' => 0, 'catalog_id' => $catalog->id, 'price' => '199.99', 'discount' => '12.50', 'languages' => [Language::ensureBase()->id => [
            'title' => 'Товар', 'description' => '<p>Опис товару</p>', 'tag_title' => 'SEO товару', 'tag_description' => 'SEO опис товару',
        ]]];
    }
}
