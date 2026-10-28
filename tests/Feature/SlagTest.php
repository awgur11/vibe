<?php

namespace Tests\Feature;

use App\Models\Language;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlagTest extends TestCase
{
    use RefreshDatabase;

    public function test_helper_transliterates_ukrainian_letters_and_punctuation(): void
    {
        $this->assertSame('yizhak-yenot-ganok-ukrayina-yosyp-shchastya-pyat', x_slag('Їжак Єнот Ґанок Україна Йосип Щастя П’ять'));
        $this->assertSame('holovna-storinka', x_slag('  Головна — сторінка! '));
    }

    public function test_page_type_and_slag_are_generated_on_create_and_update(): void
    {
        $base = Language::ensureBase();
        $this->actingAs(User::factory()->create());
        $this->post(route('pages.store'), [
            'type' => 'article', 'slag' => 'custom', 'public' => 0,
            'languages' => [$base->id => ['title' => 'Головна сторінка']],
        ])->assertSessionHasNoErrors();

        $page = Page::sole();
        $this->assertSame('pages', $page->type);
        $this->assertSame('holovna-storinka', $page->slag);
        $this->put(route('pages.update', $page), [
            'public' => 0, 'languages' => [$base->id => ['title' => 'Про Україну']],
        ])->assertSessionHasNoErrors();
        $this->assertSame('pro-ukrayinu', $page->fresh()->slag);
        $this->assertSame('pages', $page->fresh()->type);
        $this->get(route('pages.edit', $page))->assertOk()->assertSee('name="type" value="pages"', false);
    }
}
