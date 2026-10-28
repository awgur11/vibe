<?php

namespace Tests\Feature;

use App\Services\ImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Tests\TestCase;

class ImageServiceTest extends TestCase
{
    public function test_original_and_proportional_thumbnails_are_saved_as_webp_by_default(): void
    {
        Storage::fake('public');
        $this->travelTo(now()->setDate(2026, 10, 20));
        $image = UploadedFile::fake()->image('photo.png', 800, 400);

        $result = app(ImageService::class)->save($image, ['mini', 'four', 'mini', 'fhd']);

        $this->assertStringEndsWith('.webp', $result['original']);
        $this->assertMatchesRegularExpression('~^images/20102026/(?:[0-9]|[1-9][0-9])/img-[0-9]+\.webp$~', $result['original']);
        $this->assertCount(3, $result['thumbs']);
        $original = getimagesizefromstring(Storage::disk('public')->get($result['original']));
        $this->assertSame([800, 400, 'image/webp'], [$original[0], $original[1], $original['mime']]);

        foreach (['mini' => [320, 160], 'four' => [640, 320], 'fhd' => [800, 400]] as $width => $dimensions) {
            $expectedPath = str_replace('/img-', '/img-'.$width.'-', $result['original']);
            $this->assertSame($expectedPath, $result['thumbs'][$width]);
            $thumbnail = getimagesizefromstring(Storage::disk('public')->get($result['thumbs'][$width]));
            $this->assertSame($dimensions, [$thumbnail[0], $thumbnail[1]]);
            $this->assertSame('image/webp', $thumbnail['mime']);
        }
    }

    public function test_webp_can_be_disabled_and_original_bytes_are_preserved(): void
    {
        Storage::fake('public');
        $image = UploadedFile::fake()->image('photo.png', 300, 150);
        $contents = $image->getContent();

        $result = app(ImageService::class)->save($image, ['mini'], false, 'uploads');

        $this->assertStringStartsWith('uploads/', $result['original']);
        $this->assertStringEndsWith('.png', $result['original']);
        $this->assertSame($contents, Storage::disk('public')->get($result['original']));
        $thumbnail = getimagesizefromstring(Storage::disk('public')->get($result['thumbs']['mini']));
        $this->assertSame([300, 150, 'image/png'], [$thumbnail[0], $thumbnail[1], $thumbnail['mime']]);
    }

    public function test_all_named_thumbnails_are_created_when_names_are_omitted(): void
    {
        Storage::fake('public');

        $result = app(ImageService::class)->save(UploadedFile::fake()->image('photo.jpg'));

        $this->assertSame(array_keys(ImageService::THUMB_WIDTHS), array_keys($result['thumbs']));
        $this->assertCount(8, Storage::disk('public')->allFiles());
    }

    public function test_unknown_name_is_rejected_before_files_are_written(): void
    {
        Storage::fake('public');

        try {
            app(ImageService::class)->save(UploadedFile::fake()->image('photo.png'), ['unknown']);
            $this->fail('Expected an unknown thumbnail name to be rejected.');
        } catch (InvalidArgumentException) {
            $this->assertSame([], Storage::disk('public')->allFiles());
        }
    }

    public function test_explicit_empty_thumbs_saves_only_original(): void
    {
        Storage::fake('public');

        $result = app(ImageService::class)->save(UploadedFile::fake()->image('photo.png'), []);

        $this->assertSame([], $result['thumbs']);
        $this->assertSame([$result['original']], Storage::disk('public')->allFiles());
    }
    public function test_store_preserves_current_path_without_a_source_and_returns_null_for_removal(): void
    {
        $service = app(ImageService::class);
        $this->assertSame('pages/img-123.webp', $service->store(null, currentPath: 'pages/img-123.webp'));
        $this->assertNull($service->store(null, currentPath: 'pages/img-123.webp', remove: true));
        $this->assertNull($service->store(null));
    }

    public function test_delete_removes_original_and_thumbnails_but_ignores_external_paths(): void
    {
        Storage::fake('public');
        $service = app(ImageService::class);
        $image = $service->store(UploadedFile::fake()->image('photo.png'), directory: 'pages');
        $unrelated = 'pages/img-987654321.webp';
        Storage::disk('public')->put($unrelated, 'another image');

        $service->delete($image);

        $this->assertSame([$unrelated], Storage::disk('public')->allFiles());
        foreach ([null, 'https://example.com/photo.jpg', '/pages/img-987654321.webp', '../pages/img-987654321.webp'] as $path) {
            $service->delete($path);
        }
        Storage::disk('public')->assertExists($unrelated);
    }

}
