<?php

namespace Tests\Feature;

use App\Models\Language;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_manage_pages(): void
    {
        $page = Page::factory()->create();
        foreach (['pages.index', 'pages.create', 'pages.edit'] as $route) {
            $this->get(route($route, $route === 'pages.edit' ? $page : []))->assertRedirect(route('login'));
        }
        $this->post(route('pages.store'), $this->payload())->assertRedirect(route('login'));
        $this->put(route('pages.update', $page), $this->payload())->assertRedirect(route('login'));
        $this->delete(route('pages.destroy', $page))->assertRedirect(route('login'));
        $this->assertModelExists($page);
        $this->assertDatabaseCount('pages', 1);
    }

    public function test_forms_include_all_languages_and_only_base_title_is_required(): void
    {
        $inactive = Language::factory()->create(['active' => false, 'title' => 'Inactive language']);
        $base = Language::ensureBase();
        $page = Page::factory()->create();
        $this->actingAs(User::factory()->create());

        foreach (['pages.create', 'pages.edit'] as $route) {
            $response = $this->get(route($route, $route === 'pages.edit' ? $page : []));
            $response->assertOk()->assertSee('Inactive language')->assertSee('languages['.$inactive->id.'][content]', false);
            $document = new \DOMDocument;
            @$document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
            $xpath = new \DOMXPath($document);
            $this->assertSame(1, $xpath->query('//input[@name="languages['.$base->id.'][title]"][@required]')->length);
            $this->assertSame(0, $xpath->query('//input[@name="languages['.$inactive->id.'][title]"][@required]')->length);
        }
    }

    public function test_page_saves_all_fields_including_inactive_language(): void
    {
        $inactive = Language::factory()->create(['active' => false]);
        $payload = $this->payload();
        $payload = array_replace($payload, ['type' => 'article', 'slag' => 'about', 'icon' => 'info', 'position' => 5, 'is_menu' => 1, 'category_id' => 42]);
        $payload['languages'][$inactive->id] = ['menu' => 'About', 'short_content' => 'Summary', 'title' => 'Other title', 'content' => 'Other content', 'tag_title' => 'Other SEO title', 'tag_description' => 'Other description'];
        $this->actingAs(User::factory()->create());

        $this->post(route('pages.store'), $payload)->assertRedirect(route('pages.index'))->assertSessionHasNoErrors();

        $page = Page::sole();
        $this->assertTrue($page->public);
        $this->assertDatabaseHas('pages', ['id' => $page->id, 'type' => 'pages', 'slag' => 'about-us', 'icon' => 'info', 'position' => 5, 'is_menu' => 1, 'category_id' => 42]);
        foreach ($payload['languages'] as $languageId => $translation) {
            $language = Language::findOrFail($languageId);
            $this->assertDatabaseHas('pages_languages', ['page_id' => $page->id, 'lacale' => $language->locale, ...$translation]);
        }
    }

    public function test_only_base_title_is_required_and_other_languages_can_be_omitted(): void
    {
        Language::factory()->create(['active' => false]);
        $base = Language::ensureBase();
        $this->actingAs(User::factory()->create());

        $this->post(route('pages.store'), ['public' => 0, 'languages' => [$base->id => ['title' => 'Minimal']]])
            ->assertRedirect(route('pages.index'))->assertSessionHasNoErrors();

        $this->assertDatabaseCount('pages', 1);
        $this->assertFalse(Page::sole()->public);
        $this->assertSame('Minimal', Page::sole()->translated('title', $base));
    }

    public function test_missing_or_whitespace_base_title_is_rejected_on_create_and_update(): void
    {
        $base = Language::ensureBase();
        $page = Page::factory()->create();
        $original = $page->translations->toArray();
        $this->actingAs(User::factory()->create());

        foreach (['', '   '] as $title) {
            $payload = ['public' => 0, 'languages' => [$base->id => ['title' => $title]]];
            $this->post(route('pages.store'), $payload)->assertSessionHasErrors('languages.'.$base->id.'.title');
            $this->put(route('pages.update', $page), $payload)->assertSessionHasErrors('languages.'.$base->id.'.title');
        }

        $this->assertDatabaseCount('pages', 1);
        $this->assertSame($original, $page->fresh()->translations->toArray());
    }

    public function test_invalid_fields_and_unknown_languages_are_rejected(): void
    {
        $base = Language::ensureBase();
        $this->actingAs(User::factory()->create());
        $payload = $this->payload();
        $payload['public'] = 'invalid';
        $payload['languages'][$base->id]['title'] = str_repeat('x', 256);
        $payload['languages'][$base->id]['content'] = ['invalid'];
        $payload['languages'][$base->id]['tag_title'] = str_repeat('x', 256);
        $payload['languages'][$base->id]['tag_description'] = ['invalid'];

        $this->post(route('pages.store'), $payload)->assertSessionHasErrors([
            'public', 'languages.'.$base->id.'.title', 'languages.'.$base->id.'.content',
            'languages.'.$base->id.'.tag_title', 'languages.'.$base->id.'.tag_description',
        ]);
        $payload = $this->payload();
        $payload['languages'][999999] = ['title' => 'Unknown language'];
        $this->post(route('pages.store'), $payload)->assertSessionHasErrors('languages');
        $payload = $this->payload();
        $payload['languages'][$base->id]['unexpected'] = 'value';
        $this->post(route('pages.store'), $payload)->assertSessionHasErrors('languages.'.$base->id);

        $this->assertDatabaseCount('pages', 0);
    }

    public function test_page_can_be_updated_and_unpublished_without_losing_omitted_translations(): void
    {
        $inactive = Language::factory()->create(['active' => false]);
        $translations = $this->payload()['languages'];
        $translations[$inactive->id] = ['title' => 'Keep this translation'];
        $page = Page::factory()->create(['public' => true]);
        foreach ($translations as $languageId => $translation) {
            $page->translations()->updateOrCreate(['lacale' => Language::findOrFail($languageId)->locale], $translation);
        }
        $base = Language::ensureBase();
        $payload = $this->payload();
        $payload['public'] = 0;
        $payload['languages'][$base->id]['title'] = 'Updated title';
        $this->actingAs(User::factory()->create());

        $this->put(route('pages.update', $page), $payload)->assertRedirect(route('pages.index'));

        $this->assertFalse($page->fresh()->public);
        $this->assertSame('Updated title', $page->fresh()->translated('title', $base));
        $this->assertSame('Keep this translation', $page->fresh()->translated('title', $inactive));
    }

    public function test_page_can_be_deleted(): void
    {
        $page = Page::factory()->create();
        $this->actingAs(User::factory()->create());

        $this->delete(route('pages.destroy', $page))->assertRedirect(route('pages.index'));

        $this->assertModelMissing($page);
        $this->assertDatabaseMissing('pages_languages', ['page_id' => $page->id]);
    }

    public function test_list_and_form_escape_user_content_and_list_is_paginated(): void
    {
        $base = Language::ensureBase();
        $unsafe = '<script>alert(1)</script>';
        $page = Page::factory()->create();
        $page->translations()->where('lacale', $base->locale)->update(array_fill_keys(['title', 'content', 'tag_title', 'tag_description'], $unsafe));
        Page::factory()->count(20)->create();
        $this->actingAs(User::factory()->create());

        $this->get(route('pages.index'))->assertOk()->assertSee(route('pages.index', ['page' => 2]));
        $this->get(route('pages.index', ['page' => 2]))->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee($unsafe, false);
        $this->get(route('pages.edit', $page))->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee($unsafe, false);
        $this->get(route('dashboard'))->assertOk()->assertSee(route('pages.index'));
    }

    public function test_configured_base_language_controls_validation_even_when_inactive(): void
    {
        $base = Language::ensureBase();
        $newBase = Language::factory()->create(['locale' => 'fr', 'active' => false]);
        config(['app.locale' => 'fr']);
        $this->actingAs(User::factory()->create());

        $this->post(route('pages.store'), ['public' => 0, 'languages' => [$base->id => ['title' => 'Previous base']]])
            ->assertSessionHasErrors('languages.'.$newBase->id.'.title');

        $this->assertDatabaseCount('pages', 0);
    }

    public function test_parent_cannot_be_self_or_a_descendant(): void
    {
        $parent = Page::factory()->create();
        $child = Page::factory()->create(['parent_id' => $parent->id]);
        $this->actingAs(User::factory()->create());

        foreach ([$parent->id, $child->id, 999999] as $parentId) {
            $this->put(route('pages.update', $parent), [...$this->payload(), 'parent_id' => $parentId])->assertSessionHasErrors('parent_id');
        }

        $this->assertNull($parent->fresh()->parent_id);
    }

    public function test_deleting_parent_keeps_child_and_removes_only_parent_translations(): void
    {
        $parent = Page::factory()->create();
        $child = Page::factory()->create(['parent_id' => $parent->id]);
        $this->actingAs(User::factory()->create());

        $this->delete(route('pages.destroy', $parent))->assertRedirect(route('pages.index'));

        $this->assertModelExists($child);
        $this->assertNull($child->fresh()->parent_id);
        $this->assertDatabaseMissing('pages_languages', ['page_id' => $parent->id]);
        $this->assertDatabaseHas('pages_languages', ['page_id' => $child->id]);
    }

    /** @return array{public: int, languages: array<int, array{title: string, content: string, tag_title: string, tag_description: string}>} */
    private function payload(): array
    {
        return ['public' => 1, 'languages' => [Language::ensureBase()->id => [
            'title' => 'About us', 'content' => 'Page content', 'tag_title' => 'SEO title', 'tag_description' => 'SEO description',
        ]]];
    }
}
