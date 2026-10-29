<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticlePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_article_has_the_latest_five_other_published_articles_in_the_sidebar(): void
    {
        $blog = Page::factory()->create(['template' => 'blog', 'public' => true]);
        $article = Page::factory()->create(['type' => 'articles', 'public' => true, 'preview' => '/news/main.webp']);
        $others = Page::factory()->count(7)->sequence(fn ($sequence): array => [
            'created_at' => now()->subDays(7 - $sequence->index),
        ])->create(['type' => 'articles', 'public' => true]);
        Page::factory()->create(['type' => 'articles', 'public' => false, 'slag' => 'hidden-recent-article']);
        Page::factory()->create(['type' => 'articles', 'public' => true, 'slag' => '']);
        $expectedIds = $others->reverse()->take(5)->values()->modelKeys();
        $this->get(route('article', ['blogSlag' => $blog->slag, 'articleSlag' => $article->slag]))->assertOk()
            ->assertViewHas('latestArticles', fn ($records): bool => $records->modelKeys() === $expectedIds)
            ->assertSee('class="article-main-image"', false)->assertSee('src="/news/main.webp"', false)
            ->assertSee('id="recentArticlesTitle"', false)->assertSee('id="articleContactsTitle"', false)
            ->assertDontSee('hidden-recent-article');
    }

    public function test_sidebar_contacts_use_settings_and_preserve_working_hour_line_breaks(): void
    {
        $blog = Page::factory()->create(['template' => 'blog', 'public' => true]);
        $article = Page::factory()->create(['type' => 'articles', 'public' => true]);
        foreach ([
            'phones' => ['+38 (050) 123 45 67'],
            'site_email' => 'shop@example.test',
            'address' => [config('app.locale') => 'Тестова вулиця, 15'],
            'working_hours' => [config('app.locale') => "Пн–Пт 09:00–18:00\nСб 10:00–14:00"],
        ] as $key => $value) {
            Setting::factory()->create(['key' => $key, 'value' => json_encode($value, JSON_UNESCAPED_UNICODE)]);
        }
        $response = $this->get(route('article', ['blogSlag' => $blog->slag, 'articleSlag' => $article->slag]))->assertOk();
        preg_match('/<aside class="article-sidebar"[^>]*>(.*?)<\/aside>/s', $response->getContent(), $matches);
        $this->assertNotEmpty($matches);
        $this->assertStringContainsString('href="tel:+380501234567"', $matches[1]);
        $this->assertStringContainsString('mailto:shop@example.test', $matches[1]);
        $this->assertStringContainsString('Тестова вулиця, 15', $matches[1]);
        $this->assertStringContainsString("Пн–Пт 09:00–18:00<br />\nСб 10:00–14:00", $matches[1]);
    }

    public function test_article_without_a_picture_keeps_its_content_and_escapes_titles(): void
    {
        $blog = Page::factory()->create(['template' => 'blog', 'public' => true]);
        $article = Page::factory()->create(['type' => 'articles', 'public' => true, 'preview' => null]);
        $article->translations()->update(['title' => '<script>Article</script>', 'short_content' => '<p>Lead paragraph</p>', 'content' => '<p>Main article text</p>']);
        $this->get(route('article', ['blogSlag' => $blog->slag, 'articleSlag' => $article->slag]))->assertOk()
            ->assertDontSee('class="article-main-image"', false)
            ->assertSee('&lt;script&gt;Article&lt;/script&gt;', false)->assertDontSee('<script>Article</script>', false)
            ->assertSee('<p>Lead paragraph</p>', false)->assertSee('<p>Main article text</p>', false)
            ->assertDontSee('id="recentArticlesTitle"', false)->assertSee('id="articleContactsTitle"', false);
    }
}
