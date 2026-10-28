<?php

namespace Tests\Feature;

use App\Models\Language;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LanguageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_manage_languages(): void
    {
        $language = Language::factory()->create();
        foreach (['languages.index', 'languages.create', 'languages.edit'] as $route) {
            $this->get(route($route, $route === 'languages.edit' ? $language : []))->assertRedirect(route('login'));
        }
        $this->post(route('languages.store'), $this->payload())->assertRedirect(route('login'));
        $this->put(route('languages.update', $language), $this->payload())->assertRedirect(route('login'));
        $this->delete(route('languages.destroy', $language))->assertRedirect(route('login'));
        $this->assertDatabaseHas('languages', ['id' => $language->id]);
    }

    public function test_dashboard_has_language_sidebar_link_and_icon(): void
    {
        $this->actingAs(User::factory()->create())->get(route('dashboard'))
            ->assertOk()->assertSee(route('languages.index'))->assertSee('Мови')->assertSee('<svg', false);
    }

    public function test_base_language_exists_matches_config_and_is_listed_first(): void
    {
        $base = Language::where('locale', config('app.locale'))->firstOrFail();
        $this->assertTrue($base->active);
        $this->actingAs(User::factory()->create())->get(route('languages.index'))->assertOk()
            ->assertSee('Базова')->assertSee($base->locale)->assertSee('disabled', false);
    }

    public function test_language_can_be_created_edited_deactivated_and_deleted(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get(route('languages.create'))->assertOk();
        $this->post(route('languages.store'), array_replace($this->payload(), ['locale' => 'de', 'short' => 'DEU']))->assertRedirect(route('languages.index'));
        $language = Language::where('locale', 'de')->firstOrFail();
        $this->assertTrue($language->active);
        $this->get(route('languages.edit', $language))->assertOk()->assertSee('Українська');
        $this->put(route('languages.update', $language), ['locale' => 'uk_UA', 'short' => 'UKR', 'title' => 'Українська (Україна)', 'active' => 0])->assertRedirect(route('languages.index'));
        $this->assertDatabaseHas('languages', ['id' => $language->id, 'locale' => 'uk_UA', 'short' => 'UKR', 'active' => 0]);
        $this->delete(route('languages.destroy', $language))->assertRedirect(route('languages.index'));
        $this->assertDatabaseMissing('languages', ['id' => $language->id]);
    }

    public function test_base_language_cannot_be_deleted_even_by_direct_request(): void
    {
        $base = Language::where('locale', config('app.locale'))->firstOrFail();
        $this->actingAs(User::factory()->create())->from(route('languages.index'))
            ->delete(route('languages.destroy', $base))->assertRedirect(route('languages.index'))->assertSessionHasErrors('language');
        $this->assertDatabaseHas('languages', ['id' => $base->id]);
    }

    public function test_base_locale_is_readonly_but_names_can_be_edited(): void
    {
        $base = Language::where('locale', config('app.locale'))->firstOrFail();
        $this->actingAs(User::factory()->create());
        $this->get(route('languages.edit', $base))->assertOk()->assertSee('readonly', false);
        $this->put(route('languages.update', $base), array_replace($this->payload(), ['locale' => 'de']))->assertSessionHasErrors('locale');
        $this->assertSame(config('app.locale'), $base->fresh()->locale);
        $this->put(route('languages.update', $base), ['locale' => $base->locale, 'short' => 'ENG', 'title' => 'English', 'active' => 1])->assertRedirect(route('languages.index'));
        $this->assertSame('English', $base->fresh()->title);
    }

    public function test_model_also_protects_base_language(): void
    {
        $base = Language::where('locale', config('app.locale'))->firstOrFail();
        $this->expectException(ValidationException::class);
        $base->delete();
    }

    public function test_validation_rejects_duplicates_invalid_locale_and_missing_fields(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post(route('languages.store'), [])->assertSessionHasErrors(['locale', 'short', 'title', 'active']);
        $this->post(route('languages.store'), array_replace($this->payload(), ['locale' => config('app.locale')]))->assertSessionHasErrors('locale');
        $this->post(route('languages.store'), array_replace($this->payload(), ['locale' => '../bad', 'short' => str_repeat('a', 17), 'active' => 'invalid']))->assertSessionHasErrors(['locale', 'short', 'active']);
        $other = Language::factory()->create();
        $this->put(route('languages.update', $other), array_replace($this->payload(), ['locale' => config('app.locale')]))->assertSessionHasErrors('locale');
    }

    public function test_changing_config_changes_base_protection_and_ensures_language_exists(): void
    {
        $previousBase = Language::where('locale', config('app.locale'))->firstOrFail();
        config(['app.locale' => 'fr']);
        $this->actingAs(User::factory()->create())->get(route('languages.index'))->assertOk();
        $base = Language::where('locale', 'fr')->firstOrFail();
        $this->assertTrue($base->isBase());
        $this->assertFalse($previousBase->isBase());
        $this->delete(route('languages.destroy', $base))->assertSessionHasErrors('language');
        $this->delete(route('languages.destroy', $previousBase))->assertRedirect(route('languages.index'));
        $this->get(route('languages.index'))->assertOk();
        $this->assertSame(1, Language::where('locale', 'fr')->count());
    }

    public function test_language_titles_are_escaped_and_list_is_paginated(): void
    {
        Language::factory()->create(['title' => '<script>alert(1)</script>']);
        Language::factory()->count(22)->create();
        $this->actingAs(User::factory()->create())->get(route('languages.index'))
            ->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false)->assertSee('Далі');
        $this->get(route('languages.index', ['page' => 2]))->assertOk()->assertSee('Назад');
    }

    /** @return array<string, mixed> */
    private function payload(): array
    {
        return ['locale' => 'uk', 'short' => 'UKR', 'title' => 'Українська', 'active' => 1];
    }
}
