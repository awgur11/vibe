<?php

namespace Tests\Feature;

use App\Models\Filter;
use App\Models\Fvalue;
use App\Models\Language;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FvalueTest extends TestCase
{
    use RefreshDatabase;

    public function test_values_reorder_only_within_their_filter(): void
    {
        $first = Fvalue::factory()->create(['position' => 0]);
        $second = Fvalue::factory()->for($first->filter)->create(['position' => 1]);
        $other = Fvalue::factory()->create(['position' => 7]);
        $url = route('reorder.update', 'fvalues');
        $payload = ['filter_id' => $first->filter_id, 'ids' => [$second->id, $first->id]];
        $this->patchJson($url, $payload)->assertUnauthorized();
        $this->actingAs(User::factory()->create());
        $this->patchJson($url, $payload)->assertOk();
        $this->assertSame(0, $second->fresh()->position);
        $this->assertSame(1, $first->fresh()->position);
        $this->assertSame(7, $other->fresh()->position);
        $this->patchJson($url, ['filter_id' => $first->filter_id, 'ids' => [$first->id, $other->id]])->assertUnprocessable();
        $this->patchJson($url, ['ids' => [$first->id]])->assertUnprocessable();
        $this->patchJson($url, ['filter_id' => 999999, 'ids' => [$first->id]])->assertUnprocessable();
        $this->assertSame(0, $second->fresh()->position);
        $this->assertSame(1, $first->fresh()->position);
        $this->assertSame(7, $other->fresh()->position);
        $this->getJson(route('filters.values', $first->filter))->assertOk()
            ->assertJsonPath('html', fn (string $html): bool => str_contains($html, 'data-reorder-scope')
                && str_contains($html, route('reorder.update', 'fvalues')));
    }

    public function test_values_load_only_for_the_requested_filter(): void
    {
        $value = Fvalue::factory()->create();
        $other = Fvalue::factory()->create();
        $this->actingAs(User::factory()->create());
        $this->get(route('filters.index'))->assertOk()
            ->assertViewHas('filters', fn ($filters): bool => $filters->every(fn (Filter $filter): bool => ! $filter->relationLoaded('fvalues')));
        $this->getJson(route('filters.values', $value->filter))->assertOk()
            ->assertJsonPath('values.'.$value->id.'.slag', $value->slag)
            ->assertJsonMissingPath('values.'.$other->id);
        $this->getJson(route('filters.values', 999999))->assertNotFound();
    }

    public function test_values_load_in_batches_of_200(): void
    {
        $filter = Filter::factory()->create();
        $values = Fvalue::factory()->count(401)->for($filter)->create(['position' => 0]);
        $this->actingAs(User::factory()->create());
        $first = $this->getJson(route('filters.values', ['filter' => $filter, 'page' => 3]))->assertOk()
            ->assertJsonCount(200, 'values')
            ->assertJsonPath('values.'.$values->last()->id.'.position', 0);
        $nextUrl = $first->json('next_url');
        $this->assertStringContainsString('values_page=2', $nextUrl);
        $this->assertStringContainsString('page=3', $nextUrl);
        $second = $this->getJson($nextUrl)->assertOk()->assertJsonCount(200, 'values');
        $this->assertSame([], array_intersect(array_keys($first->json('values')), array_keys($second->json('values'))));
        $this->getJson($second->json('next_url'))->assertOk()->assertJsonCount(1, 'values')
            ->assertJsonPath('next_url', null)
            ->assertJsonPath('html', fn (string $html): bool => ! str_contains($html, 'data-values-load-more'));
        $this->getJson(route('filters.values', ['filter' => $filter, 'values_page' => 0]))->assertUnprocessable();
    }

    public function test_reordering_a_loaded_batch_of_200_values_is_allowed(): void
    {
        $filter = Filter::factory()->create();
        $values = Fvalue::factory()->count(201)->for($filter)->create(['position' => 0]);
        $ids = $values->sortByDesc('id')->take(200)->modelKeys();
        $this->actingAs(User::factory()->create())
            ->patchJson(route('reorder.update', 'fvalues'), ['filter_id' => $filter->id, 'ids' => array_reverse($ids)])
            ->assertOk();
        $this->assertSame(200, $values->first()->fresh()->position);
    }

    public function test_guests_cannot_manage_values(): void
    {
        $value = Fvalue::factory()->create();
        $parameters = ['filter' => $value->filter, 'fvalue' => $value];
        $this->getJson(route('filters.values', $value->filter))->assertUnauthorized();
        $this->post(route('filters.fvalues.store', $value->filter), [])->assertRedirect(route('login'));
        $this->put(route('filters.fvalues.update', $parameters), [])->assertRedirect(route('login'));
        $this->delete(route('filters.fvalues.destroy', $parameters))->assertRedirect(route('login'));
        $this->assertModelExists($value);
    }

    public function test_values_save_all_languages_and_generate_slags_within_the_filter(): void
    {
        $base = Language::ensureBase();
        $other = Language::factory()->create(['active' => false]);
        $filter = Filter::factory()->create();
        $another = Filter::factory()->create();
        $this->actingAs(User::factory()->create());
        $payload = ['languages' => [$base->id => ['title' => 'Червоний'], $other->id => ['title' => 'Red']]];
        $this->post(route('filters.fvalues.store', $filter), $payload)->assertSessionHasNoErrors()
            ->assertRedirect(route('filters.index', ['expanded' => $filter->id]));
        $value = $filter->fvalues()->sole();
        $this->assertSame('chervonyy', $value->slag);
        $this->assertSame(0, $value->position);
        $this->assertSame('Red', $value->translated('title', $other));
        app()->setLocale($base->locale);
        $this->assertSame('Червоний', $value->title);
        $this->post(route('filters.fvalues.store', $filter), $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('fvalues', ['filter_id' => $filter->id, 'slag' => 'chervonyy-2', 'position' => 1]);
        $this->post(route('filters.fvalues.store', $another), $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('fvalues', ['filter_id' => $another->id, 'slag' => 'chervonyy']);
        $this->put(route('filters.fvalues.update', ['filter' => $filter, 'fvalue' => $value]), [
            'slag' => 'red', 'position' => 5, 'languages' => [$base->id => ['title' => 'Оновлено']],
        ])->assertSessionHasNoErrors();
        $this->assertSame('red', $value->fresh()->slag);
        $this->assertSame(5, $value->fresh()->position);
        $this->assertSame('Red', $value->fresh()->translated('title', $other));
        $this->get(route('filters.index'))->assertOk()->assertSee('data-fvalues-toggle', false)
            ->assertSee('data-fvalue-create', false)
            ->assertSee('id="fvalue-dialog"', false)->assertDontSee('Оновлено');
        $this->getJson(route('filters.values', $filter))->assertOk()
            ->assertJsonPath('values.'.$value->id.'.slag', 'red')
            ->assertJsonPath('values.'.$value->id.'.titles.'.$base->locale, 'Оновлено');
    }

    public function test_validation_returns_to_the_value_dialog_with_named_errors_and_old_input(): void
    {
        $base = Language::ensureBase();
        $value = Fvalue::factory()->create();
        $this->actingAs(User::factory()->create());
        $url = route('filters.index', ['expanded' => $value->filter_id]);
        $this->from($url)->post(route('filters.fvalues.store', $value->filter), [
            '_owner_filter_id' => $value->filter_id,
            'slag' => $value->slag,
            'position' => -1,
            'languages' => [$base->id => ['title' => '']],
        ])->assertRedirect($url)->assertSessionHasErrorsIn('fvalue', ['slag', 'position', 'languages.'.$base->id.'.title'])
            ->assertSessionHasInput('_owner_filter_id', $value->filter_id);
        $this->get($url)->assertOk()->assertSee('fvalueDialog.showModal()', false);
        $this->assertDatabaseCount('fvalues', 1);
    }

    public function test_values_cannot_be_updated_or_deleted_through_another_filter(): void
    {
        $value = Fvalue::factory()->create();
        $another = Filter::factory()->create();
        $this->actingAs(User::factory()->create());
        $parameters = ['filter' => $another, 'fvalue' => $value];
        $this->put(route('filters.fvalues.update', $parameters), [
            'languages' => [Language::ensureBase()->id => ['title' => 'Changed']],
        ])->assertNotFound();
        $this->delete(route('filters.fvalues.destroy', $parameters))->assertNotFound();
        $this->assertModelExists($value);
    }

    public function test_deleting_values_and_filters_cleans_up_translations(): void
    {
        $first = Fvalue::factory()->create();
        $second = Fvalue::factory()->for($first->filter)->create();
        $this->actingAs(User::factory()->create());
        $this->delete(route('filters.fvalues.destroy', ['filter' => $first->filter, 'fvalue' => $first]))->assertSessionHasNoErrors();
        $this->assertModelMissing($first);
        $this->assertDatabaseMissing('fvalues_languages', ['fvalue_id' => $first->id]);
        $this->assertModelExists($second);
        $this->delete(route('filters.destroy', $second->filter))->assertSessionHasNoErrors();
        $this->assertModelMissing($second);
        $this->assertDatabaseMissing('fvalues_languages', ['fvalue_id' => $second->id]);
    }
}
