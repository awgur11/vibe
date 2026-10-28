<?php

namespace Tests\Feature;

use App\Models\Language;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_manage_articles(): void
    {
        $article = Page::factory()->create(['type' => 'articles']);

        foreach (['articles.index', 'articles.create', 'articles.edit'] as $route) {
            $this->get(route($route, $route === 'articles.edit' ? $article : []))->assertRedirect(route('login'));
        }

        $this->post(route('articles.store'), [])->assertRedirect(route('login'));
        $this->put(route('articles.update', $article), [])->assertRedirect(route('login'));
        $this->delete(route('articles.destroy', $article))->assertRedirect(route('login'));
        $this->assertModelExists($article);
    }

    public function test_article_can_be_created_edited_and_deleted_with_translations(): void
    {
        $base = Language::ensureBase();
        $other = Language::factory()->create(['active' => false]);
        $this->actingAs(User::factory()->create());
        $payload = [
            'type' => 'pages', 'public' => 1, 'is_menu' => 1, 'position' => 5, 'icon' => 'news',
            'languages' => [
                $base->id => ['title' => 'Blog article', 'content' => 'Article content', 'short_content' => 'Summary', 'tag_title' => 'SEO title', 'tag_description' => 'SEO description'],
                $other->id => ['title' => 'Other title'],
            ],
        ];

        $this->get(route('articles.create'))->assertOk()->assertViewIs('admin.articles.form')
            ->assertSee(route('articles.store'))->assertSee('languages['.$other->id.'][content]', false);
        $this->post(route('articles.store'), $payload)->assertSessionHasNoErrors()->assertRedirect(route('articles.index'));

        $article = Page::sole();
        $this->assertDatabaseHas('pages', ['id' => $article->id, 'type' => 'articles', 'slag' => 'blog-article', 'public' => 1, 'is_menu' => 1, 'position' => 5, 'icon' => 'news']);
        $this->assertDatabaseHas('pages_languages', ['page_id' => $article->id, 'lacale' => $base->locale, ...$payload['languages'][$base->id]]);
        $this->get(route('articles.edit', $article))->assertOk()->assertViewIs('admin.articles.form')->assertSee(route('articles.update', $article));

        $payload['languages'][$base->id]['title'] = 'Updated article';
        $payload['public'] = 0;
        unset($payload['languages'][$other->id]);
        $this->put(route('articles.update', $article), $payload)->assertSessionHasNoErrors()->assertRedirect(route('articles.index'));
        $this->assertDatabaseHas('pages', ['id' => $article->id, 'type' => 'articles', 'slag' => 'updated-article', 'public' => 0]);
        $this->assertSame('Other title', $article->fresh()->translated('title', $other));

        $this->delete(route('articles.destroy', $article))->assertRedirect(route('articles.index'));
        $this->assertModelMissing($article);
        $this->assertDatabaseMissing('pages_languages', ['page_id' => $article->id]);
    }

    public function test_article_requires_base_title(): void
    {
        $base = Language::ensureBase();
        $article = Page::factory()->create(['type' => 'articles']);
        $this->actingAs(User::factory()->create());
        $payload = ['public' => 0, 'languages' => [$base->id => ['title' => '   ']]];

        $this->post(route('articles.store'), $payload)->assertSessionHasErrors('languages.'.$base->id.'.title');
        $this->put(route('articles.update', $article), $payload)->assertSessionHasErrors('languages.'.$base->id.'.title');
        $this->assertDatabaseCount('pages', 1);
    }

    public function test_pages_and_articles_are_managed_separately(): void
    {
        $base = Language::ensureBase();
        $page = Page::factory()->create(['type' => 'pages']);
        $article = Page::factory()->create(['type' => 'articles']);
        $this->actingAs(User::factory()->create());

        $this->get(route('articles.index'))->assertOk()->assertViewIs('admin.articles.index')
            ->assertViewHas('pages', fn ($pages): bool => $pages->modelKeys() === [$article->id]);
        $this->get(route('pages.index'))->assertOk()
            ->assertViewHas('pages', fn ($pages): bool => $pages->modelKeys() === [$page->id]);

        foreach ([['articles', $page], ['pages', $article]] as [$resource, $record]) {
            $this->get(route($resource.'.edit', $record))->assertNotFound();
            $this->put(route($resource.'.update', $record), ['public' => 0, 'languages' => [$base->id => ['title' => 'Wrong section']]])->assertNotFound();
            $this->delete(route($resource.'.destroy', $record))->assertNotFound();
            $this->assertModelExists($record);
        }
    }
}
