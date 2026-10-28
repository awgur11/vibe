<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EditorImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_upload_images(): void
    {
        $this->postJson(route('editor-images.store'))->assertUnauthorized();
    }

    public function test_image_is_stored_on_public_disk_and_url_is_returned(): void
    {
        Storage::fake('public');
        $image = UploadedFile::fake()->createWithContent('pixel.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='
        ));

        $response = $this->actingAs(User::factory()->create())
            ->postJson(route('editor-images.store'), ['image' => $image])->assertCreated();

        $path = 'editor-images/'.$image->hashName();
        Storage::disk('public')->assertExists($path);
        $response->assertExactJson(['url' => Storage::disk('public')->url($path)]);
    }

    public function test_missing_or_invalid_file_is_rejected(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create());
        $this->postJson(route('editor-images.store'))->assertUnprocessable()->assertJsonValidationErrors('image');
        $this->postJson(route('editor-images.store'), [
            'image' => UploadedFile::fake()->create('document.txt', 1, 'text/plain'),
        ])->assertUnprocessable()->assertJsonValidationErrors('image');
        $this->assertSame([], Storage::disk('public')->allFiles());
    }
}
