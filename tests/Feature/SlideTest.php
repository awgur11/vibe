<?php

namespace Tests\Feature;

use App\Models\Slide;
use App\Models\Language;
use App\Models\User;
use App\Services\ImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SlideTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_manage_slides(): void
    {
        $slide = Slide::factory()->create();
        foreach (['slides.index', 'slides.create', 'slides.edit'] as $route) {
            $this->get(route($route, $route === 'slides.edit' ? $slide : []))->assertRedirect(route('login'));
        }
        $this->post(route('slides.store'), $this->payload())->assertRedirect(route('login'));
        $this->put(route('slides.update', $slide), $this->payload())->assertRedirect(route('login'));
        $this->delete(route('slides.destroy', $slide))->assertRedirect(route('login'));
        $this->assertModelExists($slide);
    }

    public function test_slide_can_be_created_edited_and_unpublished(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get(route('slides.create'))->assertOk()->assertSee('Зображення слайда')->assertSee('languages['.Language::ensureBase()->id.'][subtitle]', false);
        $this->post(route('slides.store'), $this->payload())->assertSessionHasNoErrors()->assertRedirect(route('slides.index'));

        $slide = Slide::sole();
        $payload = $this->payload();
        $this->assertDatabaseHas('slides', ['id' => $slide->id, 'link' => $payload['link'], 'position' => 0, 'public' => $payload['public']]);
        $this->assertDatabaseHas('slides_languages', ['slide_id' => $slide->id, 'lacale' => Language::ensureBase()->locale, ...$payload['languages'][Language::ensureBase()->id]]);
        $this->assertTrue($slide->public);
        $this->get(route('slides.edit', $slide))->assertOk()->assertSee('Автозапчастини');
        $payload['languages'][Language::ensureBase()->id] = ['title' => 'Нові пропозиції', 'subtitle' => ''];
        $this->put(route('slides.update', $slide), [...$payload, 'public' => 0, 'link' => ''])
            ->assertSessionHasNoErrors()->assertRedirect(route('slides.index'));
        $this->assertFalse($slide->fresh()->public);
        $this->assertSame('Нові пропозиції', $slide->fresh()->title);
        $this->assertNull($slide->fresh()->link);
        $this->assertNull($slide->fresh()->subtitle);
    }

    public function test_invalid_slide_fields_and_unsafe_links_are_rejected(): void
    {
        $this->actingAs(User::factory()->create());
        $base = Language::ensureBase();
        $this->post(route('slides.store'), [
            'languages' => [$base->id => ['title' => ' ', 'subtitle' => str_repeat('a', 1001)]], 'link' => 'javascript:alert(1)',
            'public' => 'invalid',
        ])->assertSessionHasErrors(['languages.'.$base->id.'.title', 'languages.'.$base->id.'.subtitle', 'link', 'public']);
        $this->assertDatabaseCount('slides', 0);
    }

    public function test_upload_url_replacement_and_removal_manage_all_image_files(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create());
        $this->post(route('slides.store'), [...$this->payload(), 'preview' => UploadedFile::fake()->image('slide.png')])
            ->assertSessionHasNoErrors()->assertRedirect(route('slides.index'));
        $slide = Slide::sole();
        $oldPreview = $slide->preview;
        $this->assertStringStartsWith('slides/', $oldPreview);
        $this->assertCount(8, Storage::disk('public')->allFiles());
        $this->assertSame(Storage::disk('public')->url(ImageService::thumbnailPath($oldPreview, 'mini')), $slide->previewUrl('mini'));

        $this->put(route('slides.update', $slide), $this->payload())->assertSessionHasNoErrors();
        $this->assertSame($oldPreview, $slide->fresh()->preview);
        $url = 'https://93.184.216.34/slide.png';
        Http::fake([$url => Http::response(UploadedFile::fake()->image('new.png')->getContent())]);
        $this->put(route('slides.update', $slide), [...$this->payload(), 'preview_url' => $url])->assertSessionHasNoErrors();
        $this->assertNotSame($oldPreview, $slide->fresh()->preview);
        Storage::disk('public')->assertMissing(ImageService::paths($oldPreview));
        $this->assertCount(8, Storage::disk('public')->allFiles());

        $this->put(route('slides.update', $slide), [...$this->payload(), 'remove_preview' => 1])->assertSessionHasNoErrors();
        $this->assertNull($slide->fresh()->preview);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_deleting_slide_removes_its_image_files(): void
    {
        Storage::fake('public');
        $slide = Slide::factory()->create(['preview' => 'slides/img-123456789.webp']);
        foreach (ImageService::paths($slide->preview) as $path) {
            Storage::disk('public')->put($path, 'image');
        }

        $this->actingAs(User::factory()->create())->delete(route('slides.destroy', $slide))->assertRedirect(route('slides.index'));

        $this->assertModelMissing($slide);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_list_is_ordered_paginated_and_escapes_content(): void
    {
        $unsafe = '<script>alert(1)</script>';
        Slide::factory()->create(['position' => 99])->translations()->update(['title' => $unsafe]);
        Slide::factory()->count(20)->create(['position' => 0]);
        $this->actingAs(User::factory()->create());

        $this->get(route('slides.index'))->assertOk()->assertSee(route('slides.index', ['page' => 2]));
        $this->get(route('slides.index', ['page' => 2]))->assertOk()->assertSee(e($unsafe), false)->assertDontSee($unsafe, false);
        $this->get(route('dashboard'))->assertOk()->assertSee(route('slides.index'));
    }

    public function test_all_languages_are_available_and_only_base_title_is_required(): void
    {
        $base = Language::ensureBase();
        $other = Language::factory()->create(['active' => false, 'title' => 'Додаткова мова']);
        $slide = Slide::factory()->create();
        $this->actingAs(User::factory()->create());

        foreach ([route('slides.create'), route('slides.edit', $slide)] as $url) {
            $this->get($url)->assertOk()->assertSee('Додаткова мова')
                ->assertSee('languages['.$base->id.'][title]', false)
                ->assertSee('languages['.$other->id.'][title]', false)
                ->assertSee('languages['.$other->id.'][subtitle]', false);
        }
        $payload = $this->payload();
        $payload['languages'][$other->id] = ['title' => 'Інший заголовок', 'subtitle' => 'Інший підзаголовок'];
        $this->put(route('slides.update', $slide), $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('slides_languages', ['slide_id' => $slide->id, 'lacale' => $other->locale, ...$payload['languages'][$other->id]]);
        $this->put(route('slides.update', $slide), $this->payload())->assertSessionHasNoErrors();
        $this->assertSame('Інший заголовок', $slide->fresh()->translated('title', $other));

        unset($payload['languages'][$base->id]);
        $this->put(route('slides.update', $slide), $payload)->assertSessionHasErrors('languages.'.$base->id.'.title');
        $payload = $this->payload();
        $payload['languages'][999999] = ['title' => 'Unknown'];
        $this->post(route('slides.store'), $payload)->assertSessionHasErrors('languages');
    }

    public function test_current_language_fields_and_cascade_delete(): void
    {
        $slide = Slide::factory()->create();
        $slide->translations()->create(['lacale' => 'en', 'title' => 'English title', 'subtitle' => 'English subtitle']);
        app()->setLocale('en');
        $translatedSlide = Slide::with('translation')->findOrFail($slide->id);
        $this->assertSame('English title', $translatedSlide->title);
        $this->assertSame('English subtitle', $translatedSlide->subtitle);

        $this->actingAs(User::factory()->create())->delete(route('slides.destroy', $slide))->assertRedirect(route('slides.index'));
        $this->assertDatabaseMissing('slides_languages', ['slide_id' => $slide->id]);
    }

    public function test_slide_reordering_and_publication_are_saved_asynchronously(): void
    {
        $first = Slide::factory()->create(['position' => 0, 'public' => false]);
        $second = Slide::factory()->create(['position' => 1]);
        $this->patchJson(route('slides.publication.update', $first), ['public' => true])->assertUnauthorized();
        $this->actingAs(User::factory()->create());

        $this->patchJson(route('reorder.update', 'slides'), ['ids' => [$second->id, $first->id]])->assertOk();
        $this->assertSame(0, $second->fresh()->position);
        $this->assertSame(1, $first->fresh()->position);
        $translations = $first->translations->toArray();
        foreach ([true, false] as $public) {
            $this->patchJson(route('slides.publication.update', $first), ['public' => $public, 'position' => 99])
                ->assertOk()->assertExactJson(['public' => $public]);
            $this->assertSame($public, $first->fresh()->public);
            $this->assertSame(1, $first->fresh()->position);
            $this->assertSame($translations, $first->fresh()->translations->toArray());
        }
        $this->patchJson(route('slides.publication.update', $first), ['public' => 'invalid'])->assertUnprocessable();
        $this->get(route('slides.index'))->assertOk()->assertSee('data-reorder-list', false)->assertSee('data-publication-toggle', false);
    }

    public function test_new_slide_is_appended_and_editing_preserves_its_position(): void
    {
        $existing = Slide::factory()->create(['position' => 9]);
        $this->actingAs(User::factory()->create());
        $this->post(route('slides.store'), $this->payload())->assertSessionHasNoErrors();
        $slide = Slide::whereKeyNot($existing->id)->sole();
        $this->assertSame(10, $slide->position);
        $this->put(route('slides.update', $slide), [...$this->payload(), 'position' => 0])->assertSessionHasNoErrors();
        $this->assertSame(10, $slide->fresh()->position);
        $this->get(route('slides.edit', $slide))->assertOk()->assertDontSee('name="position"', false);
    }

    /** @return array{languages: array<int, array{title: string, subtitle: string}>, link: string, public: int} */
    private function payload(): array
    {
        return ['languages' => [Language::ensureBase()->id => ['title' => 'Автозапчастини', 'subtitle' => 'Добірка для вашого авто']], 'link' => 'https://example.com/catalog', 'public' => 1];
    }
}
