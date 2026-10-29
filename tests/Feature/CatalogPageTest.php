<?php

namespace Tests\Feature;

use App\Models\Catalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_menu_uses_saved_icon_class_and_fallback(): void
    {
        $root = Catalog::factory()->create(['public' => true, 'icon' => 'catalog-icon catalog-icon-oil']);
        $this->get(route('catalogs'))->assertOk()->assertSee('class="catalog-icon catalog-icon-oil"', false);
        $root->update(['icon' => null]);
        $this->get(route('catalogs'))->assertOk()->assertSee('class="catalog-icon catalog-icon-box"', false);
    }

    public function test_breadcrumbs_contain_full_catalog_ancestry_in_order(): void
    {
        $root = Catalog::factory()->create(['public' => true]);
        $parent = Catalog::factory()->create(['public' => true, 'parent_id' => $root->id]);
        $child = Catalog::factory()->create(['public' => true, 'parent_id' => $parent->id]);
        $leaf = Catalog::factory()->create(['public' => true, 'parent_id' => $child->id]);
        $response = $this->get(route('catalog', ['catalogSlag' => $leaf->slag]))->assertOk();
        $response->assertViewHas('crumbs', fn (array $crumbs): bool => array_column($crumbs, 'title') === [__('Головна'), $root->title, $parent->title, $child->title, $leaf->title]
            && array_column($crumbs, 'url') === [route('index'), route('catalog', ['catalogSlag' => $root->slag]), route('catalog', ['catalogSlag' => $parent->slag]), route('catalog', ['catalogSlag' => $child->slag]), null]);
        $response->assertSee('class="site-crumbs"', false);
        $this->assertSame(1, substr_count($response->getContent(), 'class="site-crumbs"'));
    }

    public function test_catalog_page_shows_published_children_with_real_links_and_image_fallbacks(): void
    {
        $catalog = Catalog::factory()->create(['public' => true]);
        $first = Catalog::factory()->create(['parent_id' => $catalog->id, 'public' => true, 'position' => 1, 'preview' => 'catalogs/img-123.webp']);
        $second = Catalog::factory()->create(['parent_id' => $catalog->id, 'public' => true, 'position' => 2, 'preview' => null]);
        Catalog::factory()->create(['parent_id' => $catalog->id, 'public' => false, 'slag' => 'hidden-catalog']);
        $response = $this->get(route('catalog', ['catalogSlag' => $catalog->slag]))->assertOk()
            ->assertSee('class="catalog-page"', false)->assertSee('class="catalog-heading"', false)
            ->assertViewHas('catalogs', fn ($records): bool => $records->modelKeys() === [$first->id, $second->id]);
        preg_match('/<main[^>]*>(.*?)<\/main>/s', $response->getContent(), $matches);
        $this->assertNotEmpty($matches);
        $this->assertStringContainsString(route('catalog', ['catalogSlag' => $first->slag]), $matches[1]);
        $this->assertStringContainsString(route('catalog', ['catalogSlag' => $second->slag]), $matches[1]);
        $this->assertStringContainsString('category-placeholder', $matches[1]);
        $this->assertStringNotContainsString('hidden-catalog', $matches[1]);
        $this->assertStringNotContainsString('data-category="parts"', $matches[1]);
        $this->assertStringNotContainsString('href="#products"', $matches[1]);
    }

    public function test_catalog_without_children_preserves_its_intro_and_content(): void
    {
        $catalog = Catalog::factory()->create(['public' => true]);
        $catalog->translations()->update(['title' => '<script>Catalog</script>', 'short_content' => '<p>Category introduction</p>', 'content' => '<p>Full category description</p>']);
        $this->get(route('catalog', ['catalogSlag' => $catalog->slag]))->assertOk()
            ->assertSee('&lt;script&gt;Catalog&lt;/script&gt;', false)->assertDontSee('<script>Catalog</script>', false)
            ->assertSee('<p>Category introduction</p>', false)->assertSee('<p>Full category description</p>', false)
            ->assertDontSee('id="catalogSectionsTitle"', false);
    }
}
