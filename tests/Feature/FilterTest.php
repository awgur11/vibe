<?php

namespace Tests\Feature;

use App\Models\Catalog;
use App\Models\Filter;
use App\Models\Language;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_filters_can_be_searched_by_title_and_slug(): void
    {
        $filter = Filter::factory()->create(['slag' => 'paint-colour']);
        $filter->translations()->update(['title' => 'Exterior colour']);
        $other = Filter::factory()->create(['slag' => 'wheel-size']);
        $other->translations()->update(['title' => 'Wheel diameter']);
        $this->actingAs(User::factory()->create());

        foreach (['Exterior', 'paint', '  Exterior  '] as $search) {
            $this->get(route('filters.index', ['search' => $search]))->assertOk()
                ->assertViewHas('filters', fn ($filters): bool => $filters->modelKeys() === [$filter->id]);
        }
        $this->get(route('filters.index', ['search' => 'missing-filter']))->assertOk()
            ->assertSee('Фільтрів не знайдено.');
        $this->get(route('filters.index', ['search' => '   ']))->assertOk()
            ->assertViewHas('filters', fn ($filters): bool => $filters->total() === 2);
        $this->getJson(route('filters.index', ['search' => ['invalid']]))->assertUnprocessable();
    }

    public function test_filter_search_is_preserved_in_pagination(): void
    {
        Filter::factory()->count(21)->create()->each(function (Filter $filter): void {
            $filter->translations()->update(['title' => 'Matching filter']);
        });
        Filter::factory()->create()->translations()->update(['title' => 'Unrelated']);
        $this->actingAs(User::factory()->create())
            ->get(route('filters.index', ['search' => 'Matching']))->assertOk()
            ->assertViewHas('filters', fn ($filters): bool => $filters->total() === 21
                && str_contains($filters->nextPageUrl(), 'search=Matching'));
    }

    public function test_guests_cannot_manage_filters(): void
    {
        $filter = Filter::factory()->create();
        $this->get(route('filters.index'))->assertRedirect(route('login'));
        $this->post(route('filters.store'), [])->assertRedirect(route('login'));
        $this->put(route('filters.update', $filter), [])->assertRedirect(route('login'));
        $this->delete(route('filters.destroy', $filter))->assertRedirect(route('login'));
        $this->patchJson(route('reorder.update', 'filters'), ['ids' => [$filter->id]])->assertUnauthorized();
        $this->assertModelExists($filter);
    }

    public function test_filters_save_translations_and_generate_unique_slags(): void
    {
        $base = Language::ensureBase();
        $other = Language::factory()->create(['active' => false]);
        $this->actingAs(User::factory()->create());
        $payload = ['languages' => [$base->id => ['title' => 'Колір'], $other->id => ['title' => 'Color']]];
        $this->post(route('filters.store'), $payload)->assertRedirect(route('filters.index'))->assertSessionHasNoErrors();
        $filter = Filter::sole();
        $this->assertSame('kolir', $filter->slag);
        $this->assertSame(0, $filter->position);
        $this->assertSame('Color', $filter->translated('title', $other));
        $this->post(route('filters.store'), $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('filters', ['slag' => 'kolir-2', 'position' => 1]);
        $this->put(route('filters.update', $filter), ['slag' => 'color', 'position' => 8, 'languages' => [$base->id => ['title' => 'Новий колір']]])->assertSessionHasNoErrors();
        $this->assertSame('color', $filter->fresh()->slag);
        $this->assertSame(8, $filter->fresh()->position);
        $this->assertSame('Color', $filter->fresh()->translated('title', $other));
        $this->get(route('filters.index'))->assertOk()->assertSee('id="filter-dialog"', false)
            ->assertSee('data-filter-edit', false)->assertSee('data-reorder-list', false)
            ->assertSee('languages['.$other->id.'][title]', false);
    }

    public function test_validation_rejects_missing_base_title_duplicate_slag_and_invalid_position(): void
    {
        $base = Language::ensureBase();
        $filter = Filter::factory()->create();
        $this->actingAs(User::factory()->create());
        $this->post(route('filters.store'), ['slag' => $filter->slag, 'position' => -1, 'languages' => [$base->id => ['title' => '']]])
            ->assertSessionHasErrors(['slag', 'position', 'languages.'.$base->id.'.title']);
        $this->post(route('filters.store'), ['slag' => '../bad', 'languages' => [$base->id => ['title' => 'Title']]])->assertSessionHasErrors('slag');
        $this->assertDatabaseCount('filters', 1);
    }

    public function test_filters_reorder_and_catalog_links_are_saved_and_cleaned_on_deletion(): void
    {
        $base = Language::ensureBase();
        $first = Filter::factory()->create(['position' => 0]);
        $second = Filter::factory()->create(['position' => 1]);
        $catalog = Catalog::factory()->create();
        $this->actingAs(User::factory()->create());
        $this->patchJson(route('reorder.update', 'filters'), ['ids' => [$second->id, $first->id]])->assertOk();
        $this->assertSame(0, $second->fresh()->position);
        $this->assertSame(1, $first->fresh()->position);
        $payload = ['public' => 0, 'languages' => [$base->id => ['title' => 'Каталог']], 'filters' => [$first->id, $second->id]];
        $this->get(route('catalogs.edit', $catalog))->assertOk()->assertSee('name="filters[]"', false);
        $this->put(route('catalogs.update', $catalog), $payload)->assertSessionHasNoErrors();
        $this->assertCount(2, $catalog->fresh()->filters);
        $this->assertTrue($first->catalogs->contains($catalog));
        $this->put(route('catalogs.update', $catalog), [...$payload, 'filters' => [999999]])->assertSessionHasErrors('filters.0');
        $this->assertCount(2, $catalog->fresh()->filters);
        $this->delete(route('filters.destroy', $first))->assertRedirect(route('filters.index'));
        $this->assertDatabaseMissing('catalog_filter', ['filter_id' => $first->id]);
        $this->assertDatabaseMissing('filters_languages', ['filter_id' => $first->id]);
        unset($payload['filters']);
        $this->put(route('catalogs.update', $catalog), $payload)->assertSessionHasNoErrors();
        $this->assertCount(0, $catalog->fresh()->filters);
        $catalog->filters()->attach($second);
        $catalog->delete();
        $this->assertDatabaseMissing('catalog_filter', ['catalog_id' => $catalog->id]);
        $this->assertModelExists($second);
    }
}
