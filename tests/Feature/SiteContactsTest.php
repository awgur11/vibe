<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteContactsTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_details_and_routes_use_settings_instead_of_demo_values(): void
    {
        $page = Page::factory()->create(['template' => 'contacts', 'public' => true]);
        foreach ([
            'phones' => ['+38 (050) 111 22 33', '+38 (067) 444 55 66'],
            'site_email' => 'shop@example.test',
            'address' => [config('app.locale') => 'Львів, вул. Тестова, 42'],
            'working_hours' => [config('app.locale') => "Пн–Пт 08:00–19:00\nСб 10:00–15:00"],
            'store_coordinates' => '49.84,24.03',
        ] as $key => $value) {
            Setting::factory()->create(['key' => $key, 'value' => json_encode($value, JSON_UNESCAPED_UNICODE)]);
        }

        $response = $this->get(route('page', $page->slag))->assertOk()->assertViewIs('site.contacts')
            ->assertSee('href="tel:+380501112233"', false)->assertSee('href="tel:+380674445566"', false)
            ->assertSee('shop@example.test')->assertSee('Львів, вул. Тестова, 42')
            ->assertSee("Пн–Пт 08:00–19:00\nСб 10:00–15:00")
            ->assertSee('destination=49.84%2C24.03', false)->assertSee('q=49.84%2C24.03', false)
            ->assertDontSee('awgurweb@gmail.com')->assertDontSee('+380673752355')
            ->assertDontSee('50.450336,30.358316');
        preg_match('/<main[^>]*>(.*?)<\/main>/s', $response->getContent(), $matches);
        $this->assertNotEmpty($matches);
        $this->assertStringNotContainsString('<style', $matches[1]);
        $this->assertStringNotContainsString('<script', $matches[1]);
        $this->assertStringContainsString('id="copyAddressStatus"', $matches[1]);
    }

    public function test_saved_map_embed_is_used_and_address_is_the_route_fallback(): void
    {
        $page = Page::factory()->create(['template' => 'contacts', 'public' => true]);
        $embed = '<iframe src="https://www.google.com/maps/embed?pb=test" title="Shop location"></iframe>';
        Setting::factory()->create(['key' => 'google_maps_iframe', 'value' => json_encode($embed)]);
        Setting::factory()->create(['key' => 'address', 'value' => json_encode([config('app.locale') => 'Test Street 42'])]);
        $this->get(route('page', $page->slag))->assertOk()->assertSee($embed, false)
            ->assertSee('destination=Test%20Street%2042', false)
            ->assertSee('data-address="Test Street 42"', false);
    }

    public function test_contact_page_renders_when_contact_settings_are_empty(): void
    {
        $page = Page::factory()->create(['template' => 'contacts', 'public' => true]);
        $this->get(route('page', $page->slag))->assertOk()->assertSee('contacts-page', false)
            ->assertDontSee('href="tel:+380673752355"', false)
            ->assertDontSee('09:00 – 20:00');
    }
}
