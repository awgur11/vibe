<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\SettingsService;
use App\Services\ImageService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class SettingsCacheTest extends TestCase
{
    use DatabaseMigrations;

    public function test_all_settings_are_decoded_and_cached_with_one_database_query(): void
    {
        Setting::factory()->create(['key' => 'contacts', 'value' => '{"phone":"+380123456789"}']);
        Setting::factory()->create(['key' => 'enabled', 'value' => 'false']);
        Setting::factory()->create(['key' => 'count', 'value' => '0']);
        Setting::factory()->create(['key' => 'empty', 'value' => 'null']);
        DB::enableQueryLog();
        DB::flushQueryLog();

        $this->assertSame(['phone' => '+380123456789'], setting('contacts'));
        $this->assertFalse(setting('enabled', true));
        $this->assertSame(0, setting('count', 10));
        $this->assertNull(setting('empty', 'fallback'));
        $this->assertSame('fallback', setting('missing', 'fallback'));
        $this->assertSame(0, (new SettingsService)->get('count'));
        $queries = array_filter(DB::getQueryLog(), fn (array $query): bool => str_contains($query['query'], '"settings"') || str_contains($query['query'], '`settings`'));
        $this->assertCount(1, $queries);
        DB::disableQueryLog();
    }

    public function test_creating_updating_renaming_and_deleting_settings_invalidates_cache(): void
    {
        $this->assertNull(setting('example'));
        $setting = Setting::factory()->create(['key' => 'example', 'value' => '"initial"']);
        $this->assertSame('initial', setting('example'));
        $setting->update(['value' => '"updated"']);
        $this->assertSame('updated', setting('example'));
        $setting->update(['key' => 'renamed']);
        $this->assertNull(setting('example'));
        $this->assertSame('updated', setting('renamed'));
        $setting->delete();
        $this->assertNull(setting('renamed'));
    }

    public function test_multilingual_setting_uses_current_or_explicit_locale_and_base_fallback(): void
    {
        Setting::factory()->create(['key' => 'site_title', 'value' => '{"uk":"Наш сайт","en":"Our site"}']);
        $this->assertSame('Наш сайт', setting('site_title'));
        app()->setLocale('en');
        $this->assertSame('Our site', setting('site_title'));
        $this->assertSame('Наш сайт', setting('site_title', null, 'uk'));
        $this->assertSame('Наш сайт', setting('site_title', 'default', 'de'));
    }

    public function test_image_setting_supports_all_thumbnail_names_and_original_url(): void
    {
        Storage::fake('public');
        $path = 'settings/img-123456789.webp';
        Setting::factory()->create(['key' => 'favicon', 'value' => json_encode($path, JSON_THROW_ON_ERROR)]);
        foreach (ImageService::paths($path) as $file) {
            Storage::disk('public')->put($file, 'image');
        }

        foreach (array_keys(ImageService::THUMB_WIDTHS) as $size) {
            $this->assertSame(Storage::disk('public')->url(ImageService::thumbnailPath($path, $size)), setting('favicon.'.$size));
        }
        $this->assertSame($path, setting('favicon'));
        $this->assertSame(Storage::disk('public')->url($path), setting('favicon.original'));
        $this->assertSame(Storage::disk('public')->url($path), setting('favicon.origin'));
        Storage::disk('public')->delete(ImageService::thumbnailPath($path, 'one'));
        $this->assertSame(Storage::disk('public')->url($path), setting('favicon.one'));
        $this->assertSame('/fallback.png', setting('favicon.unknown', '/fallback.png'));
        $this->assertSame('/fallback.png', setting('site_logo.one', '/fallback.png'));
    }

    public function test_thumbnail_selection_uses_current_language_and_preserves_literal_dotted_keys(): void
    {
        Storage::fake('public');
        $path = 'settings/img-987654321.webp';
        Storage::disk('public')->put(ImageService::thumbnailPath($path, 'mini'), 'image');
        Setting::factory()->create(['key' => 'site_logo', 'value' => json_encode(['en' => $path], JSON_THROW_ON_ERROR)]);
        $this->assertSame(Storage::disk('public')->url(ImageService::thumbnailPath($path, 'mini')), setting('site_logo.mini', null, 'en'));
        Setting::factory()->create(['key' => 'custom.one', 'value' => '"literal value"']);
        $this->assertSame('literal value', setting('custom.one'));
    }

    public function test_transaction_rollbacks_do_not_publish_uncommitted_settings_to_cache(): void
    {
        $setting = Setting::factory()->create(['key' => 'example', 'value' => '"initial"']);
        $this->assertSame('initial', setting('example'));
        try {
            DB::transaction(function () use ($setting): void {
                $setting->update(['value' => '"uncommitted"']);
                $this->assertSame('uncommitted', setting('example'));
                throw new RuntimeException('Rollback');
            });
        } catch (RuntimeException $exception) {
            $this->assertSame('Rollback', $exception->getMessage());
        }
        $this->assertSame('initial', setting('example'));

        DB::transaction(function () use ($setting): void {
            $setting->refresh()->update(['value' => '"committed"']);
        });
        $this->assertSame('committed', setting('example'));
    }
}
