<?php

namespace Tests\Feature;

use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_blog_uses_reference_layout_with_real_articles_and_pagination(): void
    {
        $blog = Page::factory()->create(['template' => 'blog', 'public' => true, 'slag' => 'avtoblog']);
        $blog->translations()->update(['title' => 'Автоблог', 'short_content' => '<p>Поради для водіїв</p>', 'content' => '<p>Матеріали нашої команди</p>']);
        $articles = Page::factory()->count(13)->sequence(fn ($sequence): array => [
            'created_at' => now()->subDays(13 - $sequence->index),
        ])->create(['type' => 'articles', 'public' => true, 'preview' => '/news/photo.webp']);
        $latest = $articles->last();
        Page::factory()->create(['type' => 'articles', 'public' => false, 'slag' => 'draft-news']);
        $url = route('page', $blog->slag);

        $this->get($url)->assertOk()->assertViewIs('site.blog')
            ->assertSee('class="blog-hero"', false)->assertSee('class="blog-feature-card"', false)
            ->assertSee('class="blog-grid"', false)->assertSee('class="blog-pagination"', false)
            ->assertSee('Поради для водіїв')->assertSee('Матеріали нашої команди')
            ->assertSee(route('article', ['blogSlag' => $blog->slag, 'articleSlag' => $latest->slag]))
            ->assertSee('src="/news/photo.webp"', false)->assertDontSee('draft-news')
            ->assertSee('?page=2#articles', false)
            ->assertViewHas('articles', fn ($records): bool => $records->total() === 13 && $records->count() === 12);
        $this->get($url.'?page=2')->assertOk()
            ->assertViewHas('articles', fn ($records): bool => $records->count() === 1)
            ->assertDontSee('class="blog-feature-card"', false)->assertSee('?page=1#articles', false);
    }

    public function test_articles_without_images_use_text_cards_and_titles_are_escaped(): void
    {
        $blog = Page::factory()->create(['template' => 'blog', 'public' => true]);
        $article = Page::factory()->create(['type' => 'articles', 'public' => true, 'preview' => null]);
        $article->translations()->update(['title' => '<script>Article</script>', 'short_content' => '<b>Safe summary</b>']);
        $this->get(route('page', $blog->slag))->assertOk()
            ->assertSee('blog-feature-card--text', false)->assertSee('blog-card--text', false)
            ->assertSee('&lt;script&gt;Article&lt;/script&gt;', false)
            ->assertDontSee('<script>Article</script>', false)->assertSee('Safe summary')
            ->assertDontSee('class="blog-card-image"', false)->assertDontSee('class="blog-feature-image"', false);
    }

    public function test_empty_blog_keeps_the_page_header_and_shows_empty_state(): void
    {
        $blog = Page::factory()->create(['template' => 'blog', 'public' => true]);
        $this->get(route('page', $blog->slag))->assertOk()->assertSee('class="blog-hero"', false)
            ->assertSee('class="blog-empty"', false)->assertDontSee('class="blog-pagination"', false)
            ->assertDontSee('class="blog-feature-card"', false);
    }
}
