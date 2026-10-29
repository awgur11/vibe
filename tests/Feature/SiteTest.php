<?php

namespace Tests\Feature;

use App\Models\Catalog;
use App\Models\Page;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_lists_only_published_menu_pages_in_position_order(): void
    {
        $first = Page::factory()->create(['public' => true, 'is_menu' => true, 'position' => 1, 'slag' => 'first-page']);
        $second = Page::factory()->create(['public' => true, 'is_menu' => true, 'position' => 2, 'slag' => 'second-page']);
        $hidden = Page::factory()->create(['public' => false, 'is_menu' => true, 'slag' => 'hidden-page']);
        $unlisted = Page::factory()->create(['public' => true, 'is_menu' => false, 'slag' => 'unlisted-page']);
        $first->translations()->update(['menu' => 'Перша сторінка']);
        $second->translations()->update(['menu' => 'Друга сторінка']);

        $this->get(route('index'))->assertOk()->assertSee('Великі ідеї')
            ->assertSee('vendor/bootstrap/bootstrap.min.css', false)
            ->assertSee('vendor/bootstrap/bootstrap.bundle.min.js', false)
            ->assertSee('site-page-menu', false)
            ->assertSee('site-store-menu', false)
            ->assertSee('Автозапчасти')
            ->assertSee('aria-label="Кошик"', false)
            ->assertSeeInOrder(['Перша сторінка', 'Друга сторінка'])
            ->assertSee(route('page', $first->slag))->assertSee(route('page', $second->slag))
            ->assertDontSee(route('page', $hidden->slag))->assertDontSee(route('page', $unlisted->slag));
        $this->actingAs(User::factory()->create())->get(route('index'))->assertOk();
    }

    public function test_homepage_shows_twenty_four_published_popular_catalogs_with_images_in_order(): void
    {
        $catalogs = Catalog::factory()->count(25)->sequence(fn ($sequence): array => ['position' => $sequence->index])
            ->create(['public' => true, 'popular' => true, 'preview' => 'catalogs/photo.webp']);
        $hidden = Catalog::factory()->create(['public' => false, 'popular' => true, 'preview' => 'catalogs/hidden.webp']);
        $ordinary = Catalog::factory()->create(['public' => true, 'popular' => false, 'preview' => 'catalogs/ordinary.webp']);
        $withoutPhoto = Catalog::factory()->create(['public' => true, 'popular' => true, 'preview' => null]);
        $response = $this->get(route('index'))->assertOk()
            ->assertSee('Популярні каталоги')
            ->assertViewHas('catalogs', fn ($records): bool => $records->modelKeys() === $catalogs->take(24)->modelKeys())
            ->assertSeeInOrder($catalogs->take(24)->map(fn (Catalog $catalog): string => route('catalog', ['catalogSlag' => $catalog->slag]))->all(), false);
        foreach ([$hidden, $ordinary, $withoutPhoto, $catalogs->last()] as $catalog) {
            $response->assertDontSee(route('catalog', ['catalogSlag' => $catalog->slag]), false);
        }
    }

    public function test_popular_products_have_matching_catalog_filters_and_only_show_published_in_stock_products(): void
    {
        $catalog = Catalog::factory()->create(['slag' => 'test-catalog']);
        $product = Product::factory()->for($catalog)->create(['public' => true, 'popular' => true, 'available' => 0]);
        Product::factory()->for($catalog)->create(['public' => false, 'popular' => true, 'available' => 0]);
        Product::factory()->for($catalog)->create(['public' => true, 'popular' => true, 'available' => 1]);
        Product::factory()->for($catalog)->create(['public' => true, 'popular' => false, 'available' => 0]);
        $this->get(route('index'))->assertOk()
            ->assertViewHas('products', fn ($products): bool => $products->modelKeys() === [$product->id])
            ->assertSee('data-filter="test-catalog"', false)->assertSee('data-category="test-catalog"', false)
            ->assertSee('id="emptyProducts" hidden', false);
    }

    public function test_catalog_links_open_published_catalogs_and_their_published_children(): void
    {
        $catalog = Catalog::factory()->create(['public' => true]);
        $child = Catalog::factory()->create(['parent_id' => $catalog->id, 'public' => true]);
        $hidden = Catalog::factory()->create(['parent_id' => $catalog->id, 'public' => false]);
        $this->get(route('catalog', ['catalogSlag' => $catalog->slag]))->assertOk()
            ->assertViewIs('site.catalog')
            ->assertSee($catalog->title)
            ->assertSee(route('catalog', ['catalogSlag' => $child->slag]), false)
            ->assertDontSee(route('catalog', ['catalogSlag' => $hidden->slag]), false);
        $this->get(route('catalog', ['catalogSlag' => $hidden->slag]))->assertNotFound();
        $this->get(route('catalog', ['catalogSlag' => 'missing-catalog']))->assertNotFound();
    }

    public function test_published_page_renders_saved_content_and_metadata(): void
    {
        $page = Page::factory()->create(['public' => true, 'is_menu' => true, 'slag' => 'about']);
        $page->translations()->update([
            'title' => 'Про нас', 'content' => '<p>Історія нашої команди</p>',
            'tag_title' => 'Про команду — Vibe', 'tag_description' => 'Знайомство з командою',
        ]);

        $this->get(route('page', $page->slag))->assertOk()->assertSee('Про нас')
            ->assertSee('<p>Історія нашої команди</p>', false)->assertSee('Про команду — Vibe')
            ->assertSee('Знайомство з командою');
    }

    public function test_unpublished_and_missing_pages_return_not_found(): void
    {
        $page = Page::factory()->create(['public' => false, 'slag' => 'draft']);

        $this->get(route('page', $page->slag))->assertNotFound();
        $this->get(route('page', 'missing'))->assertNotFound();
    }

    public function test_article_uses_blog_slug_and_dedicated_template(): void
    {
        $blog = Page::factory()->create(['type' => 'pages', 'public' => true, 'is_menu' => true, 'template' => 'blog', 'slag' => 'avtoblog']);
        $article = Page::factory()->create(['type' => 'articles', 'public' => true, 'slag' => 'filtry']);
        $article->translations()->update([
            'title' => 'Фільтри для авто', 'content' => '<p>Як обрати фільтр</p>',
            'tag_title' => 'Поради про фільтри', 'tag_description' => 'Догляд за автомобілем',
        ]);
        $url = route('article', ['blogSlag' => $blog->slag, 'articleSlag' => $article->slag]);

        $this->assertSame(url('/avtoblog/filtry'), $url);
        $this->get(route('page', $blog->slag))->assertOk()->assertSee($url);
        $this->get($url)->assertOk()->assertViewIs('site.article')
            ->assertSee('Фільтри для авто')->assertSee('<p>Як обрати фільтр</p>', false)
            ->assertSee('Поради про фільтри')->assertSee('Догляд за автомобілем')
            ->assertSee(route('page', $blog->slag));
        $this->get(route('page', $article->slag))->assertNotFound();
    }

    public function test_article_requires_a_published_blog_and_a_published_article(): void
    {
        $blog = Page::factory()->create(['type' => 'pages', 'public' => true, 'template' => 'blog']);
        $article = Page::factory()->create(['type' => 'articles', 'public' => true]);
        $url = route('article', ['blogSlag' => $blog->slag, 'articleSlag' => $article->slag]);

        $article->update(['public' => false]);
        $this->get($url)->assertNotFound();
        $article->update(['public' => true]);
        $blog->update(['public' => false]);
        $this->get($url)->assertNotFound();
        $blog->update(['public' => true, 'template' => null]);
        $this->get($url)->assertNotFound();
        $blog->update(['template' => 'blog']);
        $article->update(['type' => 'pages']);
        $this->get($url)->assertNotFound();
        $this->get(route('article', ['blogSlag' => 'missing-blog', 'articleSlag' => $article->slag]))->assertNotFound();
        $this->get(route('article', ['blogSlag' => $blog->slag, 'articleSlag' => 'missing-article']))->assertNotFound();
    }

    public function test_breadcrumbs_render_home_page_and_article_paths_and_escape_titles(): void
    {
        $blog = Page::factory()->create(['type' => 'pages', 'public' => true, 'template' => 'blog', 'slag' => 'blog']);
        $blog->translations()->update(['title' => 'Автоблог']);
        $article = Page::factory()->create(['type' => 'articles', 'public' => true, 'slag' => 'filters']);
        $article->translations()->update(['title' => '<script>Filters</script>']);

        $home = $this->get(route('index'))->assertOk();
        $this->assertStringContainsString(
            '<li class="breadcrumb-item active" aria-current="page">Головна</li>',
            $home->getContent(),
        );

        $blogResponse = $this->get(route('page', $blog->slag))->assertOk();
        $blogCrumbs = $this->breadcrumbMarkup($blogResponse->getContent());
        $this->assertStringContainsString('href="'.route('index').'">Головна</a>', $blogCrumbs);
        $this->assertStringContainsString('aria-current="page">Автоблог</li>', $blogCrumbs);

        $response = $this->get(route('article', ['blogSlag' => $blog->slag, 'articleSlag' => $article->slag]))->assertOk();
        $articleCrumbs = $this->breadcrumbMarkup($response->getContent());
        $this->assertStringContainsString('href="'.route('page', $blog->slag).'">Автоблог</a>', $articleCrumbs);
        $this->assertStringContainsString('aria-current="page">&lt;script&gt;Filters&lt;/script&gt;</li>', $articleCrumbs);
        $this->assertStringNotContainsString('<script>Filters</script>', $articleCrumbs);
    }

    private function breadcrumbMarkup(string $html): string
    {
        preg_match('/<nav[^>]+aria-label="Хлібні крихти"[^>]*>.*?<\/nav>/s', $html, $matches);
        $this->assertNotEmpty($matches);

        return $matches[0];
    }

    public function test_dashboard_route_is_not_captured_by_page_route(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertOk();
    }
}
