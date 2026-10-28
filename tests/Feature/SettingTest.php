<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_manage_settings(): void
    {
        $setting = Setting::factory()->create();
        $payload = ['key' => 'site.title', 'value' => '"Vibe"'];
        $this->get(route('settings.index'))->assertRedirect(route('login'));
        $this->post(route('settings.store'), $payload)->assertRedirect(route('login'));
        $this->put(route('settings.update', $setting), $payload)->assertRedirect(route('login'));
        $this->delete(route('settings.destroy', $setting))->assertRedirect(route('login'));
        $this->assertModelExists($setting);
    }

    public function test_setting_can_be_created_updated_and_deleted_on_index(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post(route('settings.store'), ['key' => 'site.contacts', 'value' => '{"phone":"+380123456789"}'])
            ->assertSessionHasNoErrors()->assertRedirect(route('settings.index'));
        $setting = Setting::sole();
        $this->assertSame('{"phone":"+380123456789"}', $setting->value);
        $this->put(route('settings.update', $setting), ['key' => 'site.contact', 'value' => '{"phone":"+380987654321"}'])
            ->assertSessionHasNoErrors()->assertRedirect(route('settings.index'));
        $this->assertDatabaseHas('settings', ['id' => $setting->id, 'key' => 'site.contact', 'value' => '{"phone":"+380987654321"}']);
        $this->get(route('settings.index'))->assertRedirect(route('settings.seo'));
        $this->delete(route('settings.destroy', $setting))->assertRedirect(route('settings.index'));
        $this->assertModelMissing($setting);
        $this->assertFalse(Route::has('settings.create'));
        $this->assertFalse(Route::has('settings.edit'));
    }

    public function test_invalid_json_and_duplicate_keys_are_rejected(): void
    {
        $first = Setting::factory()->create(['key' => 'site.title']);
        $second = Setting::factory()->create();
        $this->actingAs(User::factory()->create());
        $this->post(route('settings.store'), ['key' => '', 'value' => '{invalid}'])->assertSessionHasErrors(['key', 'value']);
        $this->post(route('settings.store'), ['key' => $first->key, 'value' => '{}'])->assertSessionHasErrors('key');
        $this->put(route('settings.update', $second), ['key' => $first->key, 'value' => '{}'])->assertSessionHasErrors('key');
        $this->put(route('settings.update', $first), ['key' => $first->key, 'value' => '{}'])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('settings', 2);
    }

    public function test_json_scalar_values_and_arrays_are_preserved(): void
    {
        $this->actingAs(User::factory()->create());
        foreach (['null', 'false', '0', '"Текст"', '[1,2,3]'] as $index => $json) {
            $this->post(route('settings.store'), ['key' => 'test.'.$index, 'value' => $json])->assertSessionHasNoErrors();
            $this->assertDatabaseHas('settings', ['key' => 'test.'.$index, 'value' => $json]);
        }
    }

    public function test_settings_navigation_contains_seo_and_design_sections(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get(route('settings.index'))->assertRedirect(route('settings.seo'));
        $this->get(route('dashboard'))->assertOk()->assertSee(route('settings.seo'))->assertSee(route('settings.design'));
    }
}
