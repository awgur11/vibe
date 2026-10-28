<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Language;
use App\Models\User;
use Database\Seeders\BrandSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_manage_brands(): void
    {
        $brand = Brand::factory()->create();
        $this->get(route('brands.index'))->assertRedirect(route('login'));
        $this->post(route('brands.store'), [])->assertRedirect(route('login'));
        $this->put(route('brands.update', $brand), [])->assertRedirect(route('login'));
        $this->delete(route('brands.destroy', $brand))->assertRedirect(route('login'));
        $this->patchJson(route('reorder.update', 'brands'), ['ids' => [$brand->id]])->assertUnauthorized();
        $this->assertModelExists($brand);
    }

    public function test_brands_can_be_created_edited_and_deleted_with_translated_tooltips(): void
    {
        $base = Language::ensureBase();
        $other = Language::factory()->create(['locale' => 'en']);
        $this->actingAs(User::factory()->create());
        $this->post(route('brands.store'), [
            'name' => "MANN\nFILTER", 'public' => true,
            'languages' => [$base->id => ['title' => 'Фільтри MANN'], $other->id => ['title' => 'MANN filters']],
        ])->assertRedirect(route('brands.index'))->assertSessionHasNoErrors();
        $brand = Brand::sole();
        $this->assertTrue($brand->public);
        $this->assertSame(0, $brand->position);
        $this->assertSame("MANN\nFILTER", $brand->name);
        $this->assertSame('MANN filters', $brand->translated('title', $other));
        $this->get(route('brands.index'))->assertOk()->assertSee('id="brand-dialog"', false)
            ->assertSee('data-brand-edit', false)->assertSee('showModal()', false);
        $this->put(route('brands.update', $brand), [
            'name' => 'BOSCH', 'public' => false, 'position' => 4,
            'languages' => [$base->id => ['title' => 'Запчастини BOSCH']],
        ])->assertRedirect(route('brands.index'))->assertSessionHasNoErrors();
        $brand->refresh();
        $this->assertFalse($brand->public);
        $this->assertSame('BOSCH', $brand->name);
        $this->assertSame(4, $brand->position);
        $this->assertSame('Запчастини BOSCH', $brand->translated('title', $base));
        $this->assertSame('MANN filters', $brand->translated('title', $other));
        $this->delete(route('brands.destroy', $brand))->assertRedirect(route('brands.index'));
        $this->assertModelMissing($brand);
        $this->assertDatabaseMissing('brands_languages', ['brand_id' => $brand->id]);
    }

    public function test_invalid_brands_preserve_modal_input(): void
    {
        $base = Language::ensureBase();
        $this->actingAs(User::factory()->create())->from(route('brands.index'))->post(route('brands.store'), [
            'name' => '', 'public' => 'invalid', 'position' => -1,
            'languages' => [$base->id => ['title' => '']],
        ])->assertRedirect(route('brands.index'))
            ->assertSessionHasErrors(['name', 'public', 'position', 'languages.'.$base->id.'.title'])
            ->assertSessionHasInput('position', -1);
        $this->assertDatabaseCount('brands', 0);
    }

    public function test_homepage_shows_only_published_brands_in_order_with_safe_translated_titles(): void
    {
        $first = Brand::factory()->create(['name' => "MANN\nFILTER", 'position' => 1, 'public' => true]);
        $second = Brand::factory()->create(['name' => 'BOSCH', 'position' => 2, 'public' => true]);
        Brand::factory()->create(['name' => 'Hidden brand', 'public' => false]);
        $first->translations()->update(['title' => 'MANN "filters" <script>']);
        $this->get(route('index'))->assertOk()
            ->assertViewHas('brands', fn ($brands): bool => $brands->modelKeys() === [$first->id, $second->id])
            ->assertSee('title="MANN &quot;filters&quot; &lt;script&gt;"', false)
            ->assertSee('MANN<br />', false)->assertDontSee('Hidden brand');
    }

    public function test_brands_can_be_reordered(): void
    {
        $first = Brand::factory()->create(['position' => 0]);
        $second = Brand::factory()->create(['position' => 1]);
        $this->actingAs(User::factory()->create())->patchJson(route('reorder.update', 'brands'), [
            'ids' => [$second->id, $first->id],
        ])->assertOk();
        $this->assertSame(0, $second->fresh()->position);
        $this->assertSame(1, $first->fresh()->position);
    }

    public function test_seeder_imports_eight_original_brands_without_duplicates(): void
    {
        Language::ensureBase();
        $this->seed(BrandSeeder::class);
        $this->seed(BrandSeeder::class);
        $this->assertDatabaseCount('brands', 8);
        $this->assertSame(['BOSCH', "MANN\nFILTER", 'KYB', 'TRW', 'CONTINENTAL', 'Valeo', 'NGK', 'MAHLE'], Brand::orderBy('position')->pluck('name')->all());
        $this->assertSame(8, Brand::where('public', true)->count());
        $this->assertDatabaseHas('brands_languages', ['lacale' => 'uk', 'title' => 'MANN FILTER']);
    }
}
