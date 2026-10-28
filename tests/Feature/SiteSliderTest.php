<?php

namespace Tests\Feature;

use App\Models\Slide;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteSliderTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_shows_published_slides_in_order(): void
    {
        $second = Slide::factory()->create(['public' => true, 'position' => 2, 'preview' => 'slides/img-123456789.webp', 'link' => 'https://example.com/catalog']);
        $second->translations()->update(['title' => 'Другий слайд', 'subtitle' => 'Корисні пропозиції']);
        $first = Slide::factory()->create(['public' => true, 'position' => 1]);
        $first->translations()->update(['title' => 'Перший слайд']);
        Slide::factory()->create(['public' => false])->translations()->update(['title' => 'Прихований слайд']);

        $response = $this->get(route('index'))->assertOk()->assertSee('id="siteSlider"', false)
            ->assertSeeInOrder(['<h2 class="display-5 fw-bold">Перший слайд</h2>', '<h2 class="display-5 fw-bold">Другий слайд</h2>'], false)
            ->assertSee('Корисні пропозиції')->assertSee('https://example.com/catalog')
            ->assertSee($second->previewUrl('fhd'))->assertDontSee('Прихований слайд');
        $response->assertViewHas('slides', fn ($slides): bool => $slides->modelKeys() === [$first->id, $second->id]);
    }

    public function test_slider_is_hidden_when_there_are_no_published_slides(): void
    {
        Slide::factory()->create(['public' => false]);

        $this->get(route('index'))->assertOk()->assertDontSee('id="siteSlider"', false);
    }

    public function test_slider_uses_current_language_and_escapes_text(): void
    {
        $slide = Slide::factory()->create(['public' => true]);
        $slide->translations()->update(['title' => 'Український заголовок']);
        $slide->translations()->create(['lacale' => 'en', 'title' => 'English slide', 'subtitle' => '<script>alert(1)</script>']);
        app()->setLocale('en');

        $this->get(route('index'))->assertOk()->assertSee('English slide')
            ->assertDontSee('Український заголовок')->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('class="carousel-control-prev"', false);
    }

    public function test_slider_omits_slides_without_a_title_in_current_language(): void
    {
        $slide = Slide::factory()->create(['public' => true]);
        $slide->translations()->update(['title' => null]);

        $this->get(route('index'))->assertOk()->assertDontSee('id="siteSlider"', false);
    }
}
