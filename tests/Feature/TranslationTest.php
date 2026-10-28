<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TranslationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class TranslationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_translate(): void
    {
        $this->postJson(route('translations.store'), [])->assertUnauthorized();
    }

    public function test_translation_is_returned_as_json(): void
    {
        $this->mock(TranslationService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('translate')->once()->with('Привет', 'ru', 'en')->andReturn('Hello');
        });

        $this->actingAs(User::factory()->create())
            ->postJson(route('translations.store'), [
                'text' => 'Привет', 'source_locale' => 'ru', 'target_locale' => 'en',
            ])->assertOk()->assertExactJson(['translation' => 'Hello']);
    }

    public function test_invalid_input_is_rejected_before_translation(): void
    {
        $this->mock(TranslationService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('translate');
        });

        $this->actingAs(User::factory()->create());
        $this->postJson(route('translations.store'), [])->assertUnprocessable()
            ->assertJsonValidationErrors(['text', 'source_locale', 'target_locale']);
        $this->postJson(route('translations.store'), [
            'text' => ['invalid'], 'source_locale' => '../ru', 'target_locale' => 'invalid',
        ])->assertUnprocessable()->assertJsonValidationErrors(['text', 'source_locale', 'target_locale']);
    }
}
