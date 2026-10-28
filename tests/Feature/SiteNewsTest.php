<?php

namespace Tests\Feature;

use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteNewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_shows_the_latest_ten_published_articles_and_links_to_the_blog_template(): void
    {
        $blog = Page::factory()->create(['template' => 'blog', 'public' => true, 'slag' => 'avtoblog']);
        $articles = Page::factory()->count(12)->sequence(fn ($sequence): array => [
            'created_at' => now()->subDays(12 - $sequence->index),
        ])->create(['type' => 'articles', 'public' => true]);
        Page::factory()->create(['type' => 'articles', 'public' => false, 'slag' => 'hidden-news']);
        Page::factory()->create(['type' => 'articles', 'public' => true, 'slag' => '']);
        $expectedIds = $articles->reverse()->take(10)->values()->modelKeys();

        $response = $this->get(route('index'))->assertOk()
            ->assertViewHas('blogPage', fn (Page $page): bool => $page->is($blog))
            ->assertViewHas('latestArticles', fn ($records): bool => $records->modelKeys() === $expectedIds)
            ->assertSee('href="'.route('page', ['slag' => $blog->slag]).'"', false)
            ->assertSee('id="newsTrack"', false)
            ->assertDontSee('hidden-news');
        $response->assertSeeInOrder($articles->reverse()->take(10)->map(fn (Page $article): string => route('article', [
            'blogSlag' => $blog->slag, 'articleSlag' => $article->slag,
        ]))->all(), false);
        $this->get(route('page', $blog->slag))->assertOk()->assertViewIs('site.blog');
    }

    public function test_news_cards_support_articles_with_and_without_an_image_and_escape_text(): void
    {
        Page::factory()->create(['template' => 'blog', 'public' => true]);
        $textArticle = Page::factory()->create(['type' => 'articles', 'public' => true, 'preview' => null]);
        $textArticle->translations()->update(['title' => '<script>News</script>', 'short_content' => '<b>Article summary</b>']);
        Page::factory()->create(['type' => 'articles', 'public' => true, 'preview' => '/news/photo.webp']);

        $response = $this->get(route('index'))->assertOk()
            ->assertSee('news-card--text', false)
            ->assertSee('src="/news/photo.webp"', false)
            ->assertSee('&lt;script&gt;News&lt;/script&gt;', false)
            ->assertDontSee('<script>News</script>', false)
            ->assertSee('Article summary')->assertDontSee('<b>Article summary</b>', false);
        preg_match('/<article class="news-card news-card--text">(.*?)<\/article>/s', $response->getContent(), $matches);
        $this->assertNotEmpty($matches);
        $this->assertStringNotContainsString('<img', $matches[1]);
        $this->assertStringContainsString('<h3>', $matches[1]);
    }

    public function test_news_block_is_omitted_without_a_published_blog_page(): void
    {
        Page::factory()->create(['template' => 'blog', 'public' => false]);
        Page::factory()->create(['type' => 'articles', 'public' => true]);
        $this->get(route('index'))->assertOk()->assertViewHas('blogPage', null)
            ->assertViewHas('latestArticles', fn ($articles): bool => $articles->isEmpty())
            ->assertDontSee('id="newsTrack"', false);
    }

    public function test_empty_blog_omits_the_news_block(): void
    {
        Page::factory()->create(['template' => 'blog', 'public' => true]);
        $this->get(route('index'))->assertOk()->assertDontSee('id="newsTrack"', false);
    }
}
