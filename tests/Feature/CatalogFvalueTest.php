<?php

namespace Tests\Feature;

use App\Models\Catalog;
use App\Models\Fvalue;
use App\Models\Language;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogFvalueTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_values_are_saved_displayed_and_removed(): void
    {
        $catalog = Catalog::factory()->create();
        $value = Fvalue::factory()->create();
        $this->actingAs(User::factory()->create());
        $payload = $this->payload();
        $payload['filters'] = [$value->filter_id];
        $payload['fvalues'] = [$value->id];
        $this->put(route('catalogs.update', $catalog), $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('catalog_fvalue', ['catalog_id' => $catalog->id, 'fvalue_id' => $value->id]);
        $this->assertTrue($catalog->fresh()->fvalues->contains($value));
        $this->assertTrue($value->fresh()->catalogs->contains($catalog));
        $this->get(route('catalogs.edit', $catalog))->assertOk()->assertSee('name="fvalues[]"', false)
            ->assertViewHas('catalog', fn (Catalog $record): bool => $record->fvalues->contains($value));
        unset($payload['fvalues']);
        $this->put(route('catalogs.update', $catalog), $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('catalog_fvalue', ['catalog_id' => $catalog->id]);
        $this->assertModelExists($value);
    }

    public function test_filters_load_without_values_and_values_load_separately(): void
    {
        $value = Fvalue::factory()->create();
        $otherValue = Fvalue::factory()->create();
        $catalog = Catalog::factory()->create();
        $this->actingAs(User::factory()->create());

        $this->get(route('catalogs.edit', $catalog))->assertOk()
            ->assertViewMissing('filters')
            ->assertDontSee($value->slag);
        $this->getJson(route('catalogs.filters'))->assertOk()
            ->assertJsonCount(2)
            ->assertJsonFragment(['id' => $value->filter_id, 'values_url' => route('catalogs.filter-values', $value->filter)])
            ->assertJsonMissingPath('0.fvalues')
            ->assertDontSee($value->slag);
        $this->getJson(route('catalogs.filter-values', $value->filter))->assertOk()
            ->assertExactJson([['id' => $value->id, 'title' => $value->slag]])
            ->assertDontSee($otherValue->slag);
    }

    public function test_old_value_selections_are_available_before_values_load(): void
    {
        $catalog = Catalog::factory()->create();
        $value = Fvalue::factory()->create();
        $this->actingAs(User::factory()->create())
            ->withSession(['_old_input' => ['filters' => [$value->filter_id], 'fvalues' => [$value->id]]])
            ->get(route('catalogs.edit', $catalog))->assertOk()
            ->assertViewHas('selectedValueRecords', fn ($records): bool => $records->modelKeys() === [$value->id]);
    }

    public function test_invalid_duplicate_and_unselected_filter_values_are_rejected_without_changing_links(): void
    {
        $catalog = Catalog::factory()->create();
        $value = Fvalue::factory()->create();
        $other = Fvalue::factory()->create();
        $catalog->filters()->attach($value->filter);
        $catalog->fvalues()->attach($value);
        $this->actingAs(User::factory()->create());
        $payload = [...$this->payload(), 'filters' => [$value->filter_id]];
        $this->put(route('catalogs.update', $catalog), [...$payload, 'fvalues' => [999999]])->assertSessionHasErrors('fvalues.0');
        $this->put(route('catalogs.update', $catalog), [...$payload, 'fvalues' => [$value->id, $value->id]])->assertSessionHasErrors('fvalues.0');
        $this->put(route('catalogs.update', $catalog), [...$payload, 'fvalues' => [$other->id]])->assertSessionHasErrors('fvalues');
        $this->put(route('catalogs.update', $catalog), [...$payload, 'filters' => [], 'fvalues' => [$value->id]])->assertSessionHasErrors('fvalues');
        $this->assertSame([$value->id], $catalog->fresh()->fvalues->modelKeys());
    }

    public function test_pivot_rows_cascade_when_values_filters_or_catalogs_are_deleted(): void
    {
        $catalog = Catalog::factory()->create();
        $value = Fvalue::factory()->create();
        $catalog->fvalues()->attach($value);
        $value->delete();
        $this->assertDatabaseCount('catalog_fvalue', 0);
        $value = Fvalue::factory()->create();
        $catalog->fvalues()->attach($value);
        $value->filter->delete();
        $this->assertDatabaseCount('catalog_fvalue', 0);
        $value = Fvalue::factory()->create();
        $catalog->fvalues()->attach($value);
        $catalog->delete();
        $this->assertDatabaseCount('catalog_fvalue', 0);
        $this->assertModelExists($value);
    }

    /** @return array{public: int, languages: array<int, array{title: string}>} */
    private function payload(): array
    {
        return ['public' => 0, 'languages' => [Language::ensureBase()->id => ['title' => 'Каталог']]];
    }
}
