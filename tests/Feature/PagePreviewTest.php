<?php

namespace Tests\Feature;

use App\Models\Language;
use App\Models\Page;
use App\Models\User;
use App\Services\ImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class PagePreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_upload_saves_original_and_thumbnails_and_displays_preview(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create());
        $this->post(route('pages.store'), [
            ...$this->payload(), 'preview' => UploadedFile::fake()->image('photo.png', 1000, 500),
        ])->assertRedirect(route('pages.index'))->assertSessionHasNoErrors();

        $page = Page::sole();
        $this->assertStringEndsWith('.webp', $page->preview);
        $this->assertCount(8, Storage::disk('public')->allFiles());
        $this->assertSame(Storage::disk('public')->url($page->preview), $page->origin);
        $this->assertSame(Storage::disk('public')->url(ImageService::thumbnailPath($page->preview, 'fhd')), $page->fhd);
        foreach ($page->previewPaths() as $path) {
            Storage::disk('public')->assertExists($path);
        }
        $this->get(route('pages.edit', $page))->assertOk()
            ->assertSee('multipart/form-data', false)->assertSee('data-preview-input', false)
            ->assertSee($page->previewUrl('four'));

        $this->put(route('pages.update', $page), $this->payload())->assertSessionHasNoErrors();
        $this->assertSame($page->preview, $page->fresh()->preview);
    }

    public function test_replacing_and_removing_preview_deletes_old_files(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create());
        $page = Page::factory()->create(['preview' => 'pages/img-123456789.webp']);
        $oldPaths = $page->previewPaths();
        foreach ($oldPaths as $path) {
            Storage::disk('public')->put($path, 'old image');
        }

        $this->put(route('pages.update', $page), [
            ...$this->payload(), 'preview' => UploadedFile::fake()->image('replacement.jpg'),
        ])->assertRedirect(route('pages.index'))->assertSessionHasNoErrors();
        foreach ($oldPaths as $path) {
            Storage::disk('public')->assertMissing($path);
        }
        $page->refresh();
        $this->assertNotSame($oldPaths[0], $page->preview);

        $this->put(route('pages.update', $page), [...$this->payload(), 'remove_preview' => 1])
            ->assertSessionHasNoErrors();
        $this->assertNull($page->fresh()->preview);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_deleting_page_deletes_its_preview_files(): void
    {
        Storage::fake('public');
        $page = Page::factory()->create(['preview' => 'pages/img-123456789.webp']);
        foreach ($page->previewPaths() as $path) {
            Storage::disk('public')->put($path, 'image');
        }
        $this->actingAs(User::factory()->create())->delete(route('pages.destroy', $page))->assertRedirect();
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_non_image_preview_is_rejected(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create())->post(route('pages.store'), [
            ...$this->payload(), 'preview' => UploadedFile::fake()->create('document.txt', 1, 'text/plain'),
        ])->assertSessionHasErrors('preview');
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_image_url_is_downloaded_with_thumbnails_for_pages_and_articles(): void
    {
        $url = 'https://93.184.216.34/photo.jpg';
        $image = UploadedFile::fake()->image('photo.jpg', 800, 400);
        Http::fake([$url => fn () => Http::response($image->getContent(), 200, ['Content-Type' => 'image/jpeg'])]);
        $this->actingAs(User::factory()->create());

        foreach (['pages', 'articles'] as $resource) {
            Storage::fake('public');
            $this->post(route($resource.'.store'), [...$this->payload(), 'preview_url' => $url])
                ->assertRedirect(route($resource.'.index'))->assertSessionHasNoErrors();

            $page = Page::where('type', $resource)->sole();
            $this->assertStringStartsWith('pages/', $page->preview);
            $this->assertStringEndsWith('.webp', $page->preview);
            $this->assertCount(8, Storage::disk('public')->allFiles());
            foreach ($page->previewPaths() as $path) {
                Storage::disk('public')->assertExists($path);
            }
            $this->get(route($resource.'.edit', $page))->assertOk()->assertSee($page->previewUrl('four'));
        }
        Http::assertSentCount(2);
    }

    public function test_image_url_replaces_local_image_and_cleans_up_old_files(): void
    {
        $url = 'https://93.184.216.34/new.png';
        Http::fake([$url => fn () => Http::response(UploadedFile::fake()->image('new.png')->getContent())]);
        $this->actingAs(User::factory()->create());

        foreach (['pages', 'articles'] as $resource) {
            Storage::fake('public');
            $page = Page::factory()->create(['type' => $resource, 'preview' => 'pages/img-123456789.webp']);
            $oldPaths = $page->previewPaths();
            foreach ($oldPaths as $path) {
                Storage::disk('public')->put($path, 'old image');
            }
            $this->put(route($resource.'.update', $page), [...$this->payload(), 'preview_url' => $url])
                ->assertSessionHasNoErrors();

            $this->assertNotSame($oldPaths[0], $page->fresh()->preview);
            $this->assertCount(8, Storage::disk('public')->allFiles());
            Storage::disk('public')->assertMissing($oldPaths);
        }
    }

    public function test_failed_or_invalid_remote_image_does_not_replace_existing_preview(): void
    {
        $url = 'https://93.184.216.34/image';
        $this->actingAs(User::factory()->create());

        foreach (['pages', 'articles'] as $resource) {
            foreach ([Http::response('Not found', 404), Http::response('<html>Not an image</html>'), Http::response(str_repeat('x', 5 * 1024 * 1024 + 1))] as $index => $response) {
                Storage::fake('public');
                $failureUrl = $url.'/'.$resource.'/'.$index;
                Http::fake([$failureUrl => $response]);
                $page = Page::factory()->create(['type' => $resource, 'preview' => 'pages/img-123456789.webp']);
                Storage::disk('public')->put($page->preview, 'existing image');

                $this->put(route($resource.'.update', $page), [...$this->payload(), 'preview_url' => $failureUrl])
                    ->assertSessionHasErrors('preview_url');

                $this->assertSame('pages/img-123456789.webp', $page->fresh()->preview);
                $this->assertSame(['pages/img-123456789.webp'], Storage::disk('public')->allFiles());
            }
        }
    }

    public function test_redirect_to_private_address_is_rejected(): void
    {
        Storage::fake('public');
        $url = 'https://93.184.216.34/image';
        Http::fake([$url => Http::response('', 302, ['Location' => 'http://127.0.0.1/private.png'])]);

        $this->actingAs(User::factory()->create())->post(route('articles.store'), [...$this->payload(), 'preview_url' => $url])
            ->assertSessionHasErrors('preview_url');

        Http::assertSentCount(1);
        $this->assertDatabaseCount('pages', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_image_url_rejects_non_http_schemes(): void
    {
        $this->actingAs(User::factory()->create())->post(route('pages.store'), [
            ...$this->payload(), 'preview_url' => 'javascript:alert(1)',
        ])->assertSessionHasErrors('preview_url');
    }

    public function test_removing_preview_deletes_named_thumbnails_without_saved_metadata(): void
    {
        Storage::fake('public');
        $page = Page::factory()->create(['preview' => 'pages/img-123456789.webp']);
        Storage::disk('public')->put($page->preview, 'original');
        foreach (['fhd', 'one', 'two', 'three', 'four', 'five', 'mini'] as $name) {
            Storage::disk('public')->put('pages/img-'.$name.'-123456789.webp', 'thumbnail');
        }
        Storage::disk('public')->put('pages/img-987654321.webp', 'another image');

        $this->actingAs(User::factory()->create())->put(route('pages.update', $page), [
            ...$this->payload(), 'remove_preview' => 1,
        ])->assertSessionHasNoErrors();

        $this->assertNull($page->fresh()->preview);
        $this->assertSame(['pages/img-987654321.webp'], Storage::disk('public')->allFiles());
    }

    public function test_deleting_page_cleans_named_thumbnails_without_saved_metadata(): void
    {
        Storage::fake('public');
        $page = Page::factory()->create(['preview' => 'pages/img-123456789.png']);
        Storage::disk('public')->put($page->preview, 'original');
        Storage::disk('public')->put('pages/img-fhd-123456789.png', 'thumbnail');

        $this->actingAs(User::factory()->create())->delete(route('pages.destroy', $page))->assertRedirect();

        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_article_upload_and_removal_use_the_same_image_lifecycle(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create());
        $this->post(route('articles.store'), [
            ...$this->payload(), 'preview' => UploadedFile::fake()->image('article.png'),
        ])->assertSessionHasNoErrors()->assertRedirect(route('articles.index'));
        $article = Page::where('type', 'articles')->sole();
        $this->assertCount(8, Storage::disk('public')->allFiles());

        $this->put(route('articles.update', $article), $this->payload())->assertSessionHasNoErrors();
        $this->assertSame($article->preview, $article->fresh()->preview);

        $this->put(route('articles.update', $article), [...$this->payload(), 'remove_preview' => 1])->assertSessionHasNoErrors();
        $this->assertNull($article->fresh()->preview);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_failed_translation_save_rolls_back_record_and_preserves_old_image(): void
    {
        $this->withoutExceptionHandling();
        $this->actingAs(User::factory()->create());

        foreach (['pages', 'articles'] as $resource) {
            foreach ([false, true] as $remove) {
                Storage::fake('public');
                $page = Page::factory()->create(['type' => $resource, 'preview' => 'pages/img-123456789.webp']);
                $oldPaths = $page->previewPaths();
                foreach ($oldPaths as $path) {
                    Storage::disk('public')->put($path, 'existing image');
                }
                $translations = $page->translations->toArray();
                $payload = $this->payload();
                if ($remove) {
                    $payload['remove_preview'] = 1;
                } else {
                    $payload['preview'] = UploadedFile::fake()->image('replacement.png');
                }
                $event = 'eloquent.saving: '.\App\Models\Page_languages::class;
                Event::listen($event, function (): never {
                    throw new RuntimeException('Translation save failed');
                });

                try {
                    $this->put(route($resource.'.update', $page), $payload);
                    $this->fail('Expected translation persistence to fail.');
                } catch (RuntimeException $exception) {
                    $this->assertSame('Translation save failed', $exception->getMessage());
                } finally {
                    Event::forget($event);
                }

                $this->assertSame('pages/img-123456789.webp', $page->fresh()->preview);
                $this->assertSame($translations, $page->fresh()->translations->toArray());
                $this->assertEqualsCanonicalizing($oldPaths, Storage::disk('public')->allFiles());
            }
        }
    }

    /** @return array{public: int, languages: array<int, array{title: string}>} */
    private function payload(): array
    {
        return ['public' => 0, 'languages' => [Language::ensureBase()->id => ['title' => 'Page with image']]];
    }
}
