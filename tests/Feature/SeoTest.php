<?php

namespace Tests\Feature;

use App\Models\Catalog;
use App\Models\Page;
use App\Models\Product;
use App\Models\Setting;
use App\Services\SeoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_seo_uses_localized_templates_and_escapes_values(): void
    {
        $locale = app()->getLocale();
        Setting::factory()->create(['key' => 'product_title_seo_tag', 'value' => json_encode([$locale => 'Купить s% — KINGAVTO'])]);
        Setting::factory()->create(['key' => 'product_description_seo_tag', 'value' => json_encode([$locale => 'Доставка s% по Украине'])]);
        $product = Product::factory()->create(['public' => true, 'available' => 0]);
        $product->translations()->update(['title' => 'Filter "A" & B']);
        $response = $this->get(route('product', ['product' => $product->slag]))->assertOk();
        $response->assertSee('<title>Купить Filter &quot;A&quot; &amp; B — KINGAVTO</title>', false)
            ->assertSee('<meta name="description" content="Доставка Filter &quot;A&quot; &amp; B по Украине">', false);
        $this->assertSame(1, substr_count($response->getContent(), '<title>'));
        $this->assertSame(1, substr_count($response->getContent(), 'name="description"'));
    }

    public function test_page_tags_override_site_defaults_and_robots_settings_are_respected(): void
    {
        Setting::factory()->create(['key' => 'site_title', 'value' => json_encode('Site title')]);
        Setting::factory()->create(['key' => 'site_description', 'value' => json_encode('Site description')]);
        Setting::factory()->create(['key' => 'robots_index', 'value' => 'false']);
        $page = Page::factory()->create(['public' => true]);
        $page->translations()->update(['tag_title' => 'Custom title', 'tag_description' => 'Custom description']);
        $this->get(route('page', $page->slag))->assertOk()->assertSee('<title>Custom title</title>', false)
            ->assertSee('content="Custom description"', false)->assertSee('content="noindex, follow"', false);
        $this->get(route('index'))->assertOk()->assertSee('<title>Site title</title>', false)->assertSee('content="Site description"', false);
    }

    public function test_product_explicit_tags_override_templates_independently(): void
    {
        Setting::factory()->create(['key' => 'product_title_seo_tag', 'value' => json_encode('Template s%')]);
        Setting::factory()->create(['key' => 'product_description_seo_tag', 'value' => json_encode('Description s%')]);
        $product = Product::factory()->create(['public' => true, 'available' => 0]);
        $product->translations()->update(['tag_title' => 'Explicit title', 'tag_description' => 'Explicit description']);
        $url = route('product', ['product' => $product->slag]);
        $this->get($url)->assertOk()->assertSee('<title>Explicit title</title>', false)->assertSee('content="Explicit description"', false);
        $product->translations()->update(['tag_title' => '   ']);
        $this->get($url)->assertOk()->assertSee('<title>Template '.e($product->title).'</title>', false)->assertSee('content="Explicit description"', false);
    }

    public function test_homepage_uses_its_own_settings_and_catalog_uses_explicit_tags(): void
    {
        $locale = app()->getLocale();
        Setting::factory()->create(['key' => 'index_title_seo_tag', 'value' => json_encode([$locale => 'Homepage title'])]);
        Setting::factory()->create(['key' => 'index_description_seo_tag', 'value' => json_encode([$locale => 'Homepage description'])]);
        $this->get(route('index'))->assertOk()->assertSee('<title>Homepage title</title>', false)->assertSee('content="Homepage description"', false);
        $catalog = Catalog::factory()->create(['public' => true]);
        $catalog->translations()->update(['tag_title' => 'Catalog title', 'tag_description' => 'Catalog description']);
        $this->get(route('catalog', ['catalogSlag' => $catalog->slag]))->assertOk()->assertSee('<title>Catalog title</title>', false)
            ->assertSee('content="Catalog description"', false)->assertDontSee('<title>Homepage title</title>', false);
    }

    public function test_templates_use_current_language_and_fall_back_to_product_name(): void
    {
        Setting::factory()->create(['key' => 'product_title_seo_tag', 'value' => json_encode(['uk' => 'Купити s%', 'ru' => 'Купить s%'])]);
        $product = Product::factory()->create(['slag' => 'fallback-name']);
        app()->setLocale('ru');
        $this->assertSame('Купить fallback-name', app(SeoService::class)->forProduct($product)['title']);
        app()->setLocale('uk');
        $this->assertStringContainsString('Купити ', app(SeoService::class)->forProduct($product->fresh())['title']);
    }
}
