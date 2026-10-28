<?php

namespace Tests\Feature;

use App\Models\Language;
use App\Models\Setting;
use App\Models\User;
use App\Services\ImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SettingFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_open_sections_or_save_fields(): void
    {
        $this->get(route('settings.seo'))->assertRedirect(route('login'));
        $this->get(route('settings.design'))->assertRedirect(route('login'));
        $this->get(route('settings.contacts'))->assertRedirect(route('login'));
        $this->postJson(route('settings.fields.save', 'theme'), ['value' => 'dark'])->assertUnauthorized();
    }

    public function test_sections_render_multilingual_fields_and_correct_controls(): void
    {
        Language::ensureBase();
        $other = Language::factory()->create(['locale' => 'en', 'active' => false]);
        $this->actingAs(User::factory()->create());
        $this->get(route('settings.index'))->assertRedirect(route('settings.seo'));
        $this->get(route('settings.seo'))->assertOk()->assertSee('name="value[en]"', false)
            ->assertSee('data-translate-fields', false)->assertSee('Favicon')->assertSee($other->title);
        $this->get(route('settings.design'))->assertOk()->assertSee('Логотип сайту')
            ->assertSee('type="radio"', false)->assertSee('type="checkbox"', false)->assertDontSee('data-translate-fields', false);
    }

    public function test_multilingual_values_preserve_omitted_languages_and_validate_limits_from_include(): void
    {
        $base = Language::ensureBase();
        Language::factory()->create(['locale' => 'en', 'active' => false]);
        $this->actingAs(User::factory()->create());
        $url = route('settings.fields.save', 'site_title');
        $token = $this->token('site_title');
        $this->postJson($url, ['_definition' => $token, 'value' => [$base->locale => 'Наш сайт', 'en' => 'Our site']])->assertOk();
        $this->postJson($url, ['_definition' => $token, 'value' => [$base->locale => 'Оновлено']])->assertOk();
        $this->assertSame([$base->locale => 'Оновлено', 'en' => 'Our site'], json_decode(Setting::where('key', 'site_title')->sole()->value, true));
        $this->postJson($url, ['_definition' => $token, 'value' => ['unknown' => 'Bad']])->assertUnprocessable();
        $this->postJson($url, ['_definition' => $token, 'value' => [$base->locale => str_repeat('x', 256)]])->assertUnprocessable();
    }

    public function test_boolean_radio_and_color_values_are_validated_from_signed_includes(): void
    {
        $this->actingAs(User::factory()->create());
        $checkboxToken = $this->token('robots_index');
        foreach ([true, false] as $value) {
            $this->postJson(route('settings.fields.save', 'robots_index'), ['_definition' => $checkboxToken, 'value' => $value])
                ->assertOk()->assertJsonPath('value', $value);
        }
        $token = $this->token('theme');
        $this->postJson(route('settings.fields.save', 'theme'), ['_definition' => $token, 'value' => 'dark'])->assertOk();
        $this->postJson(route('settings.fields.save', 'theme'), ['_definition' => $token, 'value' => 'unknown'])->assertUnprocessable();
        $this->postJson(route('settings.fields.save', 'accent_color'), ['_definition' => $this->token('accent_color'), 'value' => 'red'])->assertUnprocessable();
    }

    public function test_modified_missing_and_reused_definitions_are_rejected(): void
    {
        $this->actingAs(User::factory()->create());
        $token = $this->token('theme');
        $url = route('settings.fields.save', 'theme');
        $this->postJson($url, ['value' => 'dark'])->assertUnprocessable();
        $this->postJson($url, ['_definition' => $token.'changed', 'value' => 'dark'])->assertUnprocessable();
        $this->postJson(route('settings.fields.save', 'site_title'), ['_definition' => $token, 'value' => 'Bad'])->assertUnprocessable();
        $this->actingAs(User::factory()->create())->postJson($url, ['_definition' => $token, 'value' => 'dark'])->assertUnprocessable();
        $this->assertDatabaseCount('settings', 0);
    }

    public function test_image_settings_save_replace_remove_and_clean_thumbnails(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create());
        $url = route('settings.fields.save', 'site_logo');
        $token = $this->token('site_logo');
        $this->post($url, ['_definition' => $token, 'preview' => UploadedFile::fake()->image('logo.png')], ['Accept' => 'application/json'])->assertOk();
        $oldPath = json_decode(Setting::where('key', 'site_logo')->sole()->value, true);
        $this->assertCount(8, Storage::disk('public')->allFiles());
        $this->post($url, ['_definition' => $token, 'preview' => UploadedFile::fake()->image('new.png')], ['Accept' => 'application/json'])->assertOk();
        Storage::disk('public')->assertMissing(ImageService::paths($oldPath));
        $this->assertCount(8, Storage::disk('public')->allFiles());
        $this->postJson($url, ['_definition' => $token, 'remove_preview' => true])->assertOk()->assertJsonPath('value', null);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_custom_includes_support_new_keys_and_multilingual_images_without_configuration(): void
    {
        Storage::fake('public');
        $base = Language::ensureBase();
        Language::factory()->create(['locale' => 'en']);
        $this->actingAs(User::factory()->create());
        $html = view('admin.details.settings.preview', [
            'key' => 'custom_logo', 'title' => 'Logo', 'languages' => true,
            'settingValues' => [], 'site_languages' => Language::all(),
        ])->render();
        preg_match('/name="_definition" value="([^"]+)"/', $html, $matches);
        $token = html_entity_decode($matches[1], ENT_QUOTES);
        $url = route('settings.fields.save', 'custom_logo');
        foreach ([$base->locale, 'en'] as $locale) {
            $this->post($url, ['_definition' => $token, 'locale' => $locale, 'preview' => UploadedFile::fake()->image('logo.png')], ['Accept' => 'application/json'])->assertOk();
        }
        $stored = json_decode(Setting::where('key', 'custom_logo')->sole()->value, true);
        $this->assertCount(2, $stored);
        $this->assertCount(16, Storage::disk('public')->allFiles());
        $this->postJson($url, ['_definition' => $token, 'locale' => 'en', 'remove_preview' => true])->assertOk();
        Storage::disk('public')->assertExists(ImageService::paths($stored[$base->locale]));
        $this->assertCount(8, Storage::disk('public')->allFiles());
    }

    public function test_setting_text_is_escaped_in_sections(): void
    {
        $base = Language::ensureBase();
        $unsafe = '</textarea><script>alert(1)</script>';
        Setting::factory()->create(['key' => 'site_description', 'value' => json_encode([$base->locale => $unsafe], JSON_THROW_ON_ERROR)]);
        $this->actingAs(User::factory()->create())->get(route('settings.seo'))->assertOk()->assertDontSee($unsafe, false)->assertSee('&lt;/textarea&gt;', false);
    }

    public function test_contacts_render_and_phone_lists_can_be_replaced_and_cleared(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get(route('settings.contacts'))->assertOk()
            ->assertSee('data-setting-phones', false)
            ->assertSee('type="email"', false)
            ->assertSee('Координати магазину');
        $url = route('settings.fields.save', 'phones');
        $token = $this->token('phones');
        $phones = ['+380 (50) 123-45-67', '+380671234567'];
        $this->postJson($url, ['_definition' => $token, 'value' => $phones])
            ->assertOk()->assertJsonPath('value', $phones);
        $this->get(route('settings.contacts'))->assertOk()->assertSee($phones[0]);
        $this->postJson($url, ['_definition' => $token, 'value' => [$phones[1]]])
            ->assertOk()->assertJsonPath('value', [$phones[1]]);
        $this->post($url, ['_definition' => $token, 'value' => ''], ['Accept' => 'application/json'])
            ->assertOk()->assertJsonPath('value', []);
        $this->assertSame([], json_decode(Setting::where('key', 'phones')->sole()->value, true));
    }

    public function test_contacts_reject_invalid_values_and_escape_map_markup(): void
    {
        $this->actingAs(User::factory()->create());
        foreach ([
            'phones' => [['invalid'], array_fill(0, 21, '+380501234567'), 'not-an-array'],
            'site_email' => ['invalid-email'],
            'admin_email' => ['invalid-email'],
            'facebook_url' => ['javascript:alert(1)'],
            'store_coordinates' => ['91, 30', '50, 181', 'invalid'],
        ] as $key => $values) {
            $token = $this->token($key);
            foreach ($values as $value) {
                $this->postJson(route('settings.fields.save', $key), ['_definition' => $token, 'value' => $value])
                    ->assertUnprocessable();
            }
        }
        foreach (['site_email' => 'shop@example.com', 'admin_email' => 'admin@example.com',
            'facebook_url' => 'https://www.facebook.com/shop', 'store_coordinates' => '50.4501, 30.5234'] as $key => $value) {
            $this->postJson(route('settings.fields.save', $key), ['_definition' => $this->token($key), 'value' => $value])
                ->assertOk()->assertJsonPath('value', $value);
        }
        $markup = '<iframe src="https://www.google.com/maps/embed?pb=example"></iframe>';
        $this->postJson(route('settings.fields.save', 'google_maps_iframe'), [
            '_definition' => $this->token('google_maps_iframe'), 'value' => $markup,
        ])->assertOk()->assertJsonPath('value', $markup);
        $this->get(route('settings.contacts'))->assertOk()->assertDontSee($markup, false)
            ->assertSee('&lt;iframe', false);
    }

    private function token(string $key): string
    {
        $section = in_array($key, ['site_title', 'site_description', 'favicon', 'robots_index', 'robots_follow'], true) ? 'seo' : 'design';
        if (in_array($key, ['phones', 'site_email', 'admin_email', 'address', 'google_maps_iframe', 'store_coordinates', 'working_hours', 'facebook_url'], true)) {
            $section = 'contacts';
        }
        $html = $this->get(route('settings.'.$section))->assertOk()->getContent();
        $action = preg_quote(route('settings.fields.save', $key), '~');
        preg_match('~<form[^>]+action="'.$action.'"[^>]*>(.*?)</form>~s', $html, $forms);
        $this->assertNotEmpty($forms);
        preg_match('/name="_definition" value="([^"]+)"/', $forms[1], $tokens);
        $this->assertNotEmpty($tokens);

        return html_entity_decode($tokens[1], ENT_QUOTES);
    }
}
