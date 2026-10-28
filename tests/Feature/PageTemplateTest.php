<?php

namespace Tests\Feature;

use App\Models\Language;
use App\Models\Page;
use App\Models\User;
use Database\Seeders\BlogArticleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_template_can_be_saved_changed_and_cleared(): void
    {
        $base = Language::ensureBase();
        $this->actingAs(User::factory()->create());
        $payload = ['public' => 1, 'template' => 'blog', 'languages' => [$base->id => ['title' => 'Блог']]];

        $this->get(route('pages.create'))->assertOk()->assertSee('name="template"', false);
        $this->post(route('pages.store'), $payload)->assertSessionHasNoErrors()->assertRedirect(route('pages.index'));
        $page = Page::sole();
        $this->assertSame('blog', $page->template);
        $this->get(route('pages.edit', $page))->assertOk()->assertSee('value="blog"', false);

        $payload['template'] = 'page';
        $this->put(route('pages.update', $page), $payload)->assertSessionHasNoErrors();
        $this->assertSame('page', $page->fresh()->template);

        $payload['template'] = '';
        $this->put(route('pages.update', $page), $payload)->assertSessionHasNoErrors();
        $this->assertNull($page->fresh()->template);
    }

    public function test_blog_template_lists_only_published_articles_with_pagination(): void
    {
        $blog = Page::factory()->create(['public' => true, 'template' => 'blog']);
        $articles = Page::factory()->count(13)->create(['type' => 'articles', 'public' => true]);
        $hidden = Page::factory()->create(['type' => 'articles', 'public' => false]);
        $ordinaryPage = Page::factory()->create(['type' => 'pages', 'public' => true]);

        $this->get(route('page', $blog->slag))->assertOk()->assertViewIs('site.blog')
            ->assertSee(route('article', ['blogSlag' => $blog->slag, 'articleSlag' => $articles->last()->slag]))
            ->assertDontSee(route('article', ['blogSlag' => $blog->slag, 'articleSlag' => $articles->first()->slag]))
            ->assertDontSee(route('article', ['blogSlag' => $blog->slag, 'articleSlag' => $hidden->slag]))
            ->assertDontSee(route('article', ['blogSlag' => $blog->slag, 'articleSlag' => $ordinaryPage->slag]))
            ->assertSee(route('page', ['slag' => $blog->slag, 'page' => 2]));
        $this->get(route('page', ['slag' => $blog->slag, 'page' => 2]))->assertOk()
            ->assertSee(route('article', ['blogSlag' => $blog->slag, 'articleSlag' => $articles->first()->slag]));
    }

    public function test_unset_or_missing_template_uses_standard_page_view(): void
    {
        foreach ([null, 'missing-template'] as $template) {
            $page = Page::factory()->create(['public' => true, 'template' => $template]);
            $this->get(route('page', $page->slag))->assertOk()->assertViewIs('site.page');
        }
    }

    public function test_template_cannot_reference_views_outside_site(): void
    {
        $base = Language::ensureBase();
        $this->actingAs(User::factory()->create());

        foreach (['../admin.pages.index', '/admin/pages', ['blog'], str_repeat('a', 256)] as $template) {
            $this->post(route('pages.store'), [
                'public' => 1, 'template' => $template, 'languages' => [$base->id => ['title' => 'Блог']],
            ])->assertSessionHasErrors('template');
        }
    }

    public function test_blog_seeder_creates_two_published_articles_of_300_characters_without_duplicates(): void
    {
        $this->seed(BlogArticleSeeder::class);
        $this->seed(BlogArticleSeeder::class);

        $articles = Page::where('type', 'articles')->get();
        $this->assertCount(2, $articles);
        foreach ($articles as $article) {
            $this->assertTrue($article->public);
            $this->assertSame(300, mb_strlen($article->translations->firstWhere('lacale', 'uk')->content));
        }
    }
}
