<?php

namespace Tests\Feature;

use App\Models\Picture;
use App\Models\Product;
use App\Models\User;
use App\Services\ImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class ProductPictureTest extends TestCase
{
    use RefreshDatabase;

    public function test_picture_order_is_saved_only_for_the_requested_product(): void
    {
        $first = Picture::factory()->create(['position' => 0]);
        $second = Picture::factory()->for($first->product)->create(['position' => 1]);
        $other = Picture::factory()->create(['position' => 8]);
        $url = route('reorder.update', 'pictures');
        $payload = ['product_id' => $first->product_id, 'ids' => [$second->id, $first->id]];
        $this->patchJson($url, $payload)->assertUnauthorized();
        $this->actingAs(User::factory()->create());
        $this->patchJson($url, $payload)->assertOk();
        $this->assertSame(0, $second->fresh()->position);
        $this->assertSame(1, $first->fresh()->position);
        $this->assertSame(8, $other->fresh()->position);
        $this->assertSame([$second->id, $first->id], $first->product->pictures()->pluck('id')->all());
        $this->patchJson($url, ['product_id' => $first->product_id, 'ids' => [$first->id, $other->id]])->assertUnprocessable();
        $this->patchJson($url, ['ids' => [$first->id]])->assertUnprocessable();
        $this->assertSame(0, $second->fresh()->position);
        $this->assertSame(1, $first->fresh()->position);
    }

    public function test_rotation_is_clockwise_and_replaces_original_and_all_thumbnails(): void
    {
        Storage::fake('public');
        $product = Product::factory()->create();
        $this->actingAs(User::factory()->create());
        $image = imagecreatetruecolor(80, 40);
        imagefilledrectangle($image, 0, 0, 39, 39, imagecolorallocate($image, 255, 0, 0));
        imagefilledrectangle($image, 40, 0, 79, 39, imagecolorallocate($image, 0, 0, 255));
        ob_start();
        imagepng($image);
        $contents = ob_get_clean();
        imagedestroy($image);
        $this->postJson(route('products.pictures.store', $product), ['image' => UploadedFile::fake()->createWithContent('pattern.png', $contents)])->assertCreated();
        $picture = $product->pictures()->sole();
        $oldPath = $picture->path;
        $response = $this->patchJson(route('products.pictures.rotate', ['product' => $product, 'picture' => $picture]))->assertOk();
        $picture->refresh();
        $this->assertNotSame($oldPath, $picture->path);
        $this->assertSame(0, $picture->position);
        Storage::disk('public')->assertMissing(ImageService::paths($oldPath));
        Storage::disk('public')->assertExists(ImageService::paths($picture->path));
        $this->assertCount(8, Storage::disk('public')->allFiles());
        foreach (ImageService::paths($picture->path) as $path) {
            $dimensions = getimagesizefromstring(Storage::disk('public')->get($path));
            $this->assertSame([40, 80], [$dimensions[0], $dimensions[1]]);
        }
        $rotated = imagecreatefromstring(Storage::disk('public')->get($picture->path));
        $top = imagecolorat($rotated, 20, 10);
        $bottom = imagecolorat($rotated, 20, 70);
        $this->assertGreaterThan($top & 255, ($top >> 16) & 255);
        $this->assertGreaterThan(($bottom >> 16) & 255, $bottom & 255);
        imagedestroy($rotated);
        $response->assertJsonPath('thumbnail_url', $picture->url('mini'));
        $this->get(route('products.edit', $product))->assertOk()->assertSee('data-gallery-rotate', false)->assertSee('data-gallery-move', false);
    }

    public function test_rotation_is_authenticated_and_scoped_to_its_product(): void
    {
        $picture = Picture::factory()->create();
        $another = Product::factory()->create();
        $this->patchJson(route('products.pictures.rotate', ['product' => $picture->product, 'picture' => $picture]))->assertUnauthorized();
        $this->actingAs(User::factory()->create());
        $this->patchJson(route('products.pictures.rotate', ['product' => $another, 'picture' => $picture]))->assertNotFound();
        $this->assertModelExists($picture);
    }

    public function test_failed_rotation_save_keeps_original_and_removes_new_files(): void
    {
        Storage::fake('public');
        $product = Product::factory()->create();
        $this->actingAs(User::factory()->create());
        $this->postJson(route('products.pictures.store', $product), ['image' => UploadedFile::fake()->image('photo.png')])->assertCreated();
        $picture = $product->pictures()->sole();
        $oldPath = $picture->path;
        Event::listen('eloquent.updating: '.Picture::class, function (): void {
            throw new RuntimeException('Unable to update picture.');
        });
        $this->withoutExceptionHandling();
        try {
            $this->patchJson(route('products.pictures.rotate', ['product' => $product, 'picture' => $picture]));
            $this->fail('Expected update failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Unable to update picture.', $exception->getMessage());
        } finally {
            Event::forget('eloquent.updating: '.Picture::class);
        }
        $this->assertSame($oldPath, $picture->fresh()->path);
        Storage::disk('public')->assertExists(ImageService::paths($oldPath));
        $this->assertCount(8, Storage::disk('public')->allFiles());
    }

    public function test_guests_cannot_upload_or_delete_product_pictures(): void
    {
        $picture = Picture::factory()->create();
        $this->postJson(route('products.pictures.store', $picture->product), [])->assertUnauthorized();
        $this->deleteJson(route('products.pictures.destroy', ['product' => $picture->product, 'picture' => $picture]))->assertUnauthorized();
        $this->assertModelExists($picture);
    }

    public function test_upload_creates_webp_and_all_thumbnails_and_returns_gallery_urls(): void
    {
        Storage::fake('public');
        $product = Product::factory()->create();
        $this->actingAs(User::factory()->create());
        $response = $this->postJson(route('products.pictures.store', $product), ['image' => UploadedFile::fake()->image('photo.png', 800, 400)])->assertCreated();
        $picture = $product->pictures()->sole();
        $this->assertStringStartsWith('products/', $picture->path);
        $this->assertStringEndsWith('.webp', $picture->path);
        $this->assertSame(0, $picture->position);
        $this->assertTrue($picture->product->is($product));
        Storage::disk('public')->assertExists(ImageService::paths($picture->path));
        $this->assertCount(8, Storage::disk('public')->allFiles());
        $response->assertJsonPath('id', $picture->id)->assertJsonPath('url', $picture->url())
            ->assertJsonPath('thumbnail_url', Storage::disk('public')->url(ImageService::thumbnailPath($picture->path, 'mini')));
        $this->get(route('products.edit', $product))->assertOk()->assertSee('data-gallery-drop', false)
            ->assertSee('data-gallery-files', false)->assertSee('data-gallery-url', false)->assertSee($picture->url('mini'));
        $this->postJson(route('products.pictures.store', $product), ['image' => UploadedFile::fake()->image('second.png')])->assertCreated()->assertJsonPath('position', 1);
        $this->assertSame([0, 1], $product->pictures()->pluck('position')->all());
    }

    public function test_url_download_uses_the_same_service_and_saves_miniatures(): void
    {
        Storage::fake('public');
        $url = 'https://93.184.216.34/photo.jpg';
        Http::fake([$url => Http::response(UploadedFile::fake()->image('photo.jpg', 800, 400)->getContent())]);
        $product = Product::factory()->create();
        $this->actingAs(User::factory()->create());
        $this->postJson(route('products.pictures.store', $product), ['preview_url' => $url])->assertCreated();
        $picture = $product->pictures()->sole();
        Storage::disk('public')->assertExists(ImageService::paths($picture->path));
        $this->assertCount(8, Storage::disk('public')->allFiles());
        $this->assertStringEndsWith('.webp', $picture->path);
        Http::assertSentCount(1);
    }

    public function test_invalid_sources_are_rejected_without_creating_records_or_files(): void
    {
        Storage::fake('public');
        $product = Product::factory()->create();
        $this->actingAs(User::factory()->create());
        $url = route('products.pictures.store', $product);
        $this->postJson($url, [])->assertUnprocessable()->assertJsonValidationErrors(['image', 'preview_url']);
        $this->postJson($url, ['image' => UploadedFile::fake()->create('bad.txt', 1, 'text/plain')])->assertUnprocessable()->assertJsonValidationErrors('image');
        $this->postJson($url, ['preview_url' => 'javascript:alert(1)'])->assertUnprocessable()->assertJsonValidationErrors('preview_url');
        $this->postJson($url, ['image' => UploadedFile::fake()->image('photo.png'), 'preview_url' => 'https://93.184.216.34/photo.png'])->assertUnprocessable();
        $this->postJson($url, ['preview_url' => 'http://127.0.0.1/photo.png'])->assertUnprocessable()->assertJsonValidationErrors('preview_url');
        $this->assertDatabaseCount('pictures', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_failed_database_save_cleans_up_stored_images(): void
    {
        Storage::fake('public');
        $product = Product::factory()->create();
        $this->actingAs(User::factory()->create());
        Event::listen('eloquent.creating: '.Picture::class, function (): void {
            throw new RuntimeException('Unable to save picture.');
        });
        $this->withoutExceptionHandling();
        try {
            $this->postJson(route('products.pictures.store', $product), ['image' => UploadedFile::fake()->image('photo.png')]);
            $this->fail('Expected database save failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Unable to save picture.', $exception->getMessage());
        } finally {
            Event::forget('eloquent.creating: '.Picture::class);
        }
        $this->assertDatabaseCount('pictures', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_deletion_is_scoped_to_product_and_cleans_up_files(): void
    {
        Storage::fake('public');
        $picture = Picture::factory()->create();
        $another = Product::factory()->create();
        foreach (ImageService::paths($picture->path) as $path) {
            Storage::disk('public')->put($path, 'image');
        }
        $this->actingAs(User::factory()->create());
        $this->deleteJson(route('products.pictures.destroy', ['product' => $another, 'picture' => $picture]))->assertNotFound();
        $this->assertModelExists($picture);
        $this->deleteJson(route('products.pictures.destroy', ['product' => $picture->product, 'picture' => $picture]))->assertOk();
        $this->assertModelMissing($picture);
        Storage::disk('public')->assertMissing(ImageService::paths($picture->path));
        $this->assertModelExists($picture->product);
    }

    public function test_deleting_product_removes_gallery_records_and_files(): void
    {
        Storage::fake('public');
        $product = Product::factory()->create();
        $pictures = Picture::factory()->count(2)->for($product)->create();
        foreach ($pictures as $picture) {
            foreach (ImageService::paths($picture->path) as $path) {
                Storage::disk('public')->put($path, 'image');
            }
        }
        $this->actingAs(User::factory()->create());
        $this->delete(route('products.destroy', $product))->assertRedirect(route('products.index'));
        $this->assertDatabaseMissing('pictures', ['product_id' => $product->id]);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }
}
