<?php

namespace Tests\Feature;

use App\Models\Catalog;
use App\Models\Language;
use App\Models\User;
use App\Services\ImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_manage_catalogs(): void
    {
        $catalog = Catalog::factory()->create();
        $this->get(route('catalogs.index'))->assertRedirect(route('login'));
        $this->get(route('catalogs.edit', $catalog))->assertRedirect(route('login'));
        $this->get(route('catalogs.children', $catalog))->assertRedirect(route('login'));
        $this->post(route('catalogs.store'), ['title' => 'Каталог'])->assertRedirect(route('login'));
        $this->put(route('catalogs.update', $catalog), [])->assertRedirect(route('login'));
        $this->delete(route('catalogs.destroy', $catalog))->assertRedirect(route('login'));
        $this->patchJson(route('catalogs.publication.update', $catalog), ['public' => true])->assertUnauthorized();
        $this->assertModelExists($catalog);
    }

    public function test_title_only_creation_makes_a_draft_and_redirects_to_edit_with_unique_slag(): void
    {
        $parent = Catalog::factory()->create();
        Catalog::factory()->create(['parent_id' => $parent->id, 'position' => 7]);
        $this->actingAs(User::factory()->create());
        $response = $this->post(route('catalogs.store'), ['title' => 'Гальмівні колодки', 'parent_id' => $parent->id, 'public' => true]);
        $catalog = Catalog::where('slag', 'halmivni-kolodky')->sole();
        $response->assertRedirect(route('catalogs.edit', $catalog))->assertSessionHasNoErrors();
        $this->assertFalse($catalog->public);
        $this->assertSame(8, $catalog->position);
        $this->assertSame($parent->id, $catalog->parent_id);
        $this->assertSame('Гальмівні колодки', $catalog->translated('title', Language::ensureBase()));
        $this->post(route('catalogs.store'), ['title' => 'Гальмівні колодки'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('catalogs', ['slag' => 'halmivni-kolodky-2']);
        $this->post(route('catalogs.store'), ['title' => '   '])->assertSessionHasErrors('title');
    }

    public function test_list_contains_title_dialog_and_filters_children(): void
    {
        $root = Catalog::factory()->create();
        $child = Catalog::factory()->create(['parent_id' => $root->id]);
        $grandchild = Catalog::factory()->create(['parent_id' => $child->id]);
        $this->actingAs(User::factory()->create());
        $this->get(route('catalogs.index'))->assertOk()->assertSee('catalog-create-dialog')
            ->assertSee('name="title"', false)->assertSee('data-publication-toggle', false)
            ->assertSee('aria-expanded="false" aria-controls="catalog-children-'.$root->id.'"', false)
            ->assertSee('id="catalog-children-'.$root->id.'" class="catalog-children" hidden', false)
            ->assertDontSee(route('catalogs.edit', $child), false)
            ->assertDontSee(route('catalogs.edit', $grandchild), false)
            ->assertDontSee('aria-controls="catalog-children-'.$grandchild->id.'"', false)
            ->assertViewHas('catalogs', fn ($catalogs): bool => $catalogs->modelKeys() === [$root->id]);
        $this->get(route('catalogs.index', ['parent_id' => $root->id]))->assertOk()
            ->assertViewHas('catalogs', fn ($catalogs): bool => $catalogs->modelKeys() === [$child->id]);
        $this->get(route('catalogs.children', $root))->assertOk()
            ->assertSee(route('catalogs.edit', $child), false)
            ->assertDontSee(route('catalogs.edit', $grandchild), false)
            ->assertViewHas('catalogs', fn ($catalogs): bool => $catalogs->modelKeys() === [$child->id]);
        $this->get(route('catalogs.children', $child))->assertOk()
            ->assertSee(route('catalogs.edit', $grandchild), false)
            ->assertViewHas('catalogs', fn ($catalogs): bool => $catalogs->modelKeys() === [$grandchild->id]);
        $this->get(route('catalogs.children', $grandchild))->assertOk()
            ->assertViewHas('catalogs', fn ($catalogs): bool => $catalogs->isEmpty());
        $this->assertFalse(Route::has('catalogs.create'));
    }

    public function test_catalog_popularity_defaults_to_false_and_can_be_updated(): void
    {
        $catalog = Catalog::factory()->create();
        $this->assertFalse($catalog->fresh()->popular);
        $this->actingAs(User::factory()->create());
        $this->get(route('catalogs.edit', $catalog))->assertOk()->assertSee('name="popular"', false);
        foreach ([true, false] as $popular) {
            $this->put(route('catalogs.update', $catalog), [...$this->payload(), 'popular' => $popular])
                ->assertSessionHasNoErrors()->assertRedirect(route('catalogs.edit', $catalog));
            $this->assertSame($popular, $catalog->fresh()->popular);
        }
        $this->put(route('catalogs.update', $catalog), [...$this->payload(), 'popular' => 'invalid'])
            ->assertSessionHasErrors('popular');
    }

    public function test_catalog_saves_all_languages_and_preserves_omitted_translations(): void
    {
        $base = Language::ensureBase();
        $other = Language::factory()->create(['active' => false]);
        $catalog = Catalog::factory()->create();
        $this->actingAs(User::factory()->create());
        $payload = $this->payload();
        $payload['languages'][$other->id] = ['title' => 'Other title', 'short_content' => 'Other summary', 'content' => 'Other content'];
        $payload['slag'] = 'spare-parts';
        $this->get(route('catalogs.edit', $catalog))->assertOk()->assertSee('languages['.$other->id.'][content]', false);
        $this->put(route('catalogs.update', $catalog), $payload)->assertRedirect(route('catalogs.edit', $catalog))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('catalogs', ['id' => $catalog->id, 'slag' => 'spare-parts', 'public' => 1]);
        $this->assertSame('Запчастини', $catalog->fresh()->translated('title', $base));
        $this->assertSame('Other title', $catalog->fresh()->translated('title', $other));
        $this->put(route('catalogs.update', $catalog), $this->payload())->assertSessionHasNoErrors();
        $this->assertSame('Other title', $catalog->fresh()->translated('title', $other));
        $payload['languages'][$base->id]['title'] = '';
        $this->put(route('catalogs.update', $catalog), $payload)->assertSessionHasErrors('languages.'.$base->id.'.title');
    }

    public function test_parent_cycles_and_duplicate_slag_are_rejected(): void
    {
        $root = Catalog::factory()->create();
        $child = Catalog::factory()->create(['parent_id' => $root->id]);
        $this->actingAs(User::factory()->create());
        foreach ([$root->id, $child->id, 999999] as $parentId) {
            $this->put(route('catalogs.update', $root), [...$this->payload(), 'parent_id' => $parentId])->assertSessionHasErrors('parent_id');
        }
        $this->put(route('catalogs.update', $root), [...$this->payload(), 'slag' => $child->slag])->assertSessionHasErrors('slag');
        $this->assertNull($root->fresh()->parent_id);
    }

    public function test_reorder_changes_only_siblings_and_rejects_other_branches(): void
    {
        $parent = Catalog::factory()->create(['position' => 9]);
        $first = Catalog::factory()->create(['parent_id' => $parent->id, 'position' => 0]);
        $second = Catalog::factory()->create(['parent_id' => $parent->id, 'position' => 1]);
        $this->actingAs(User::factory()->create());
        $url = route('reorder.update', 'catalogs');
        $this->patchJson($url, ['parent_id' => $parent->id, 'ids' => [$second->id, $first->id]])->assertOk();
        $this->assertSame(0, $second->fresh()->position);
        $this->assertSame(1, $first->fresh()->position);
        $this->assertSame(9, $parent->fresh()->position);
        $this->patchJson($url, ['parent_id' => $parent->id, 'ids' => [$first->id, $parent->id]])->assertUnprocessable();
        $this->patchJson($url, ['ids' => [$first->id, $second->id]])->assertUnprocessable();
    }

    public function test_publication_updates_without_changing_content(): void
    {
        $catalog = Catalog::factory()->create(['public' => false]);
        $translations = $catalog->translations->toArray();
        $this->actingAs(User::factory()->create());
        foreach ([true, false] as $public) {
            $this->patchJson(route('catalogs.publication.update', $catalog), ['public' => $public])->assertOk()->assertExactJson(['public' => $public]);
            $this->assertSame($public, $catalog->fresh()->public);
            $this->assertSame($translations, $catalog->fresh()->translations->toArray());
        }
    }

    public function test_moving_to_another_parent_appends_without_resetting_on_normal_edit(): void
    {
        $parent = Catalog::factory()->create();
        Catalog::factory()->create(['parent_id' => $parent->id, 'position' => 6]);
        $catalog = Catalog::factory()->create(['position' => 3]);
        $this->actingAs(User::factory()->create());
        $payload = [...$this->payload(), 'parent_id' => (string) $parent->id];
        $this->put(route('catalogs.update', $catalog), $payload)->assertSessionHasNoErrors();
        $this->assertSame(7, $catalog->fresh()->position);
        $this->put(route('catalogs.update', $catalog), $payload)->assertSessionHasNoErrors();
        $this->assertSame(7, $catalog->fresh()->position);
    }

    public function test_image_lifecycle_and_deleting_parent_preserves_children(): void
    {
        Storage::fake('public');
        $catalog = Catalog::factory()->create();
        $child = Catalog::factory()->create(['parent_id' => $catalog->id]);
        $this->actingAs(User::factory()->create());
        $this->put(route('catalogs.update', $catalog), [...$this->payload(), 'preview' => UploadedFile::fake()->image('catalog.png')])->assertSessionHasNoErrors();
        $path = $catalog->fresh()->preview;
        $this->assertStringStartsWith('catalogs/', $path);
        Storage::disk('public')->assertExists(ImageService::paths($path));
        $this->delete(route('catalogs.destroy', $catalog))->assertRedirect(route('catalogs.index'));
        $this->assertModelMissing($catalog);
        $this->assertNull($child->fresh()->parent_id);
        $this->assertDatabaseMissing('catalogs_languages', ['catalog_id' => $catalog->id]);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    /** @return array{public: int, languages: array<int, array{title: string, short_content: string, content: string}>} */
    private function payload(): array
    {
        return ['public' => 1, 'languages' => [Language::ensureBase()->id => ['title' => 'Запчастини', 'short_content' => 'Короткий опис', 'content' => '<p>Опис каталогу</p>']]];
    }
}
