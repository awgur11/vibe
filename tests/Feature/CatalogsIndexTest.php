<?php

namespace Tests\Feature;

use App\Models\Catalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogsIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalogs_are_grouped_by_root_with_only_second_level_cards(): void
    {
        $root = Catalog::factory()->create(['public' => true, 'position' => 0]);
        $child = Catalog::factory()->create(['public' => true, 'parent_id' => $root->id]);
        $grandchild = Catalog::factory()->create(['public' => true, 'parent_id' => $child->id]);
        $hidden = Catalog::factory()->create(['public' => false, 'parent_id' => $root->id]);
        $leaf = Catalog::factory()->create(['public' => true, 'position' => 1]);
        $hiddenRoot = Catalog::factory()->create(['public' => false]);
        $response = $this->get(route('catalogs'))->assertOk()->assertViewIs('site.catalogs')
            ->assertViewHas('catalogs', fn ($catalogs): bool => $catalogs->modelKeys() === [$root->id, $leaf->id]
                && $catalogs->first()->children->modelKeys() === [$child->id])
            ->assertSee('href="'.route('catalogs').'" class="catalog-toggle', false);
        preg_match('/<main.*?<\/main>/s', $response->getContent(), $matches);
        $this->assertStringContainsString(route('catalog', ['catalogSlag' => $child->slag]), $matches[0]);
        $this->assertStringContainsString(route('catalog', ['catalogSlag' => $leaf->slag]), $matches[0]);
        foreach ([$grandchild, $hidden, $hiddenRoot] as $catalog) {
            $this->assertStringNotContainsString(route('catalog', ['catalogSlag' => $catalog->slag]), $matches[0]);
        }
    }

    public function test_empty_catalogs_page_is_available(): void
    {
        $this->get(route('catalogs'))->assertOk()->assertViewHas('catalogs', fn ($catalogs): bool => $catalogs->isEmpty());
    }
}
