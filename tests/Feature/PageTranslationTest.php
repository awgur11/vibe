<?php

namespace Tests\Feature;

use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PageTranslationTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_exposes_current_language_fields_without_changing_page_attributes(): void
    {
        $page = Page::factory()->create(['is_menu' => true]);
        $fields = [
            'title' => 'English title', 'menu' => 'English menu', 'content' => 'English content',
            'short_content' => 'English summary', 'tag_title' => 'English SEO title',
            'tag_description' => 'English description',
        ];
        $page->translations()->create(['lacale' => 'en', ...$fields]);
        app()->setLocale('en');

        $loadedPage = Page::with('translation')->findOrFail($page->id);

        $this->assertTrue($loadedPage->relationLoaded('translation'));
        foreach ($fields as $field => $value) {
            $this->assertSame($value, $loadedPage->getAttribute($field));
            $this->assertArrayNotHasKey($field, $loadedPage->getAttributes());
        }
        $this->assertSame($page->id, $loadedPage->id);
        $this->assertTrue($loadedPage->is_menu);
        $this->assertFalse($loadedPage->isDirty());
        $loadedPage->save();
        $this->assertDatabaseHas('pages_languages', ['page_id' => $page->id, 'lacale' => 'en', ...$fields]);
    }

    public function test_missing_current_translation_returns_null(): void
    {
        $page = Page::factory()->create();
        app()->setLocale('de');

        $loadedPage = Page::with('translation')->findOrFail($page->id);

        $this->assertNull($loadedPage->translation);
        $this->assertNull($loadedPage->title);
        $this->assertNull($loadedPage->content);
    }

    public function test_eager_loaded_translated_fields_do_not_query_database_during_rendering(): void
    {
        Page::factory()->count(3)->create();
        $pages = Page::with('translation')->get();
        DB::enableQueryLog();
        DB::flushQueryLog();

        foreach ($pages as $page) {
            $this->assertNotEmpty($page->title);
            $this->assertSame($page->translation->title, $page->title);
        }

        $this->assertSame([], DB::getQueryLog());
        DB::disableQueryLog();
    }
}
