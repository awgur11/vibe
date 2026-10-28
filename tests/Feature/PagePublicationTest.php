<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagePublicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_change_publication(): void
    {
        $page = Page::factory()->create(['public' => false]);
        $this->patchJson(route('pages.publication.update', $page), ['public' => true])->assertUnauthorized();
        $this->assertFalse($page->fresh()->public);
    }

    public function test_publication_can_be_changed_without_changing_other_fields(): void
    {
        $page = Page::factory()->create(['public' => false, 'position' => 7]);
        $translations = $page->translations->toArray();
        $this->actingAs(User::factory()->create());

        foreach ([true, false] as $public) {
            $this->patchJson(route('pages.publication.update', $page), ['public' => $public, 'position' => 99])
                ->assertOk()->assertExactJson(['public' => $public]);
            $this->assertSame($public, $page->fresh()->public);
            $this->assertSame(7, $page->fresh()->position);
            $this->assertSame($translations, $page->fresh()->translations->toArray());
        }
        $this->get(route('pages.index'))->assertOk()->assertSee('data-publication-toggle', false);
    }

    public function test_invalid_publication_and_articles_are_rejected(): void
    {
        $page = Page::factory()->create(['public' => false]);
        $article = Page::factory()->create(['type' => 'articles', 'public' => false]);
        $this->actingAs(User::factory()->create());

        foreach ([[], ['public' => 'invalid']] as $payload) {
            $this->patchJson(route('pages.publication.update', $page), $payload)->assertUnprocessable();
        }
        $this->patchJson(route('pages.publication.update', $article), ['public' => true])->assertNotFound();
        $this->assertFalse($page->fresh()->public);
        $this->assertFalse($article->fresh()->public);
    }
}
