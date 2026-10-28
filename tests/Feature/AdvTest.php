<?php

namespace Tests\Feature;

use App\Models\Adv;
use App\Models\Language;
use App\Models\User;
use Database\Seeders\AdvSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_manage_advantages(): void
    {
        $adv = Adv::factory()->create();

        $this->get(route('advs.index'))->assertRedirect(route('login'));
        $this->post(route('advs.store'), [])->assertRedirect(route('login'));
        $this->put(route('advs.update', $adv), [])->assertRedirect(route('login'));
        $this->delete(route('advs.destroy', $adv))->assertRedirect(route('login'));
        $this->patchJson(route('reorder.update', 'advs'), ['ids' => [$adv->id]])->assertUnauthorized();
        $this->assertModelExists($adv);
    }

    public function test_advantages_can_be_created_and_edited_with_translations_in_a_modal(): void
    {
        $base = Language::ensureBase();
        $other = Language::factory()->create(['locale' => 'en', 'active' => true]);
        $this->actingAs(User::factory()->create());
        $payload = [
            'icon' => 'i-truck', 'public' => true,
            'languages' => [
                $base->id => ['title' => 'Швидка доставка', 'description' => 'По всій Україні'],
                $other->id => ['title' => 'Fast delivery', 'description' => 'Across Ukraine'],
            ],
        ];

        $this->post(route('advs.store'), $payload)->assertRedirect(route('advs.index'))->assertSessionHasNoErrors();
        $adv = Adv::sole();
        $this->assertTrue($adv->public);
        $this->assertSame(0, $adv->position);
        $this->assertSame('Fast delivery', $adv->translated('title', $other));
        $this->assertSame('Across Ukraine', $adv->translated('description', $other));
        $this->get(route('advs.index'))->assertOk()->assertSee('id="adv-dialog"', false)
            ->assertSee('data-adv-edit', false)->assertSee('showModal()', false)
            ->assertSee('languages['.$other->id.'][description]', false);

        $this->put(route('advs.update', $adv), [
            'icon' => 'i-shield', 'position' => 7, 'public' => false,
            'languages' => [$base->id => ['title' => 'Гарантія', 'description' => 'Перевірена якість']],
        ])->assertRedirect(route('advs.index'))->assertSessionHasNoErrors();
        $adv->refresh();
        $this->assertSame('i-shield', $adv->icon);
        $this->assertSame(7, $adv->position);
        $this->assertFalse($adv->public);
        $this->assertSame('Гарантія', $adv->translated('title', $base));
        $this->assertSame('Fast delivery', $adv->translated('title', $other));
    }

    public function test_invalid_advantages_preserve_modal_input_and_do_not_create_records(): void
    {
        $base = Language::ensureBase();
        $this->actingAs(User::factory()->create())->from(route('advs.index'))
            ->post(route('advs.store'), [
                'icon' => '<script>', 'public' => 'invalid', 'position' => -1,
                'languages' => [$base->id => ['title' => '', 'description' => str_repeat('x', 2001)]],
            ])->assertRedirect(route('advs.index'))
            ->assertSessionHasErrors(['icon', 'public', 'position', 'languages.'.$base->id.'.title', 'languages.'.$base->id.'.description'])
            ->assertSessionHasInput('icon', '<script>');
        $this->assertDatabaseCount('advs', 0);
    }

    public function test_deleting_an_advantage_removes_its_translations(): void
    {
        $adv = Adv::factory()->create();
        $this->actingAs(User::factory()->create())->delete(route('advs.destroy', $adv))->assertRedirect(route('advs.index'));
        $this->assertModelMissing($adv);
        $this->assertDatabaseMissing('advs_languages', ['adv_id' => $adv->id]);
    }

    public function test_homepage_shows_only_published_advantages_in_position_order_and_escapes_text(): void
    {
        $second = Adv::factory()->create(['public' => true, 'position' => 2]);
        $first = Adv::factory()->create(['public' => true, 'position' => 1]);
        $hidden = Adv::factory()->create(['public' => false]);
        $first->translations()->update(['title' => '<script>First</script>']);

        $response = $this->get(route('index'))->assertOk()
            ->assertViewHas('advs', fn ($advs): bool => $advs->modelKeys() === [$first->id, $second->id]);
        $response->assertSee('&lt;script&gt;First&lt;/script&gt;', false)->assertDontSee('<script>First</script>', false);
        $response->assertDontSee($hidden->title);
    }

    public function test_advantages_can_be_reordered(): void
    {
        $first = Adv::factory()->create(['position' => 0]);
        $second = Adv::factory()->create(['position' => 1]);
        $this->actingAs(User::factory()->create())->patchJson(route('reorder.update', 'advs'), [
            'ids' => [$second->id, $first->id],
        ])->assertOk();
        $this->assertSame(0, $second->fresh()->position);
        $this->assertSame(1, $first->fresh()->position);
    }

    public function test_seeder_imports_all_four_existing_advantages_and_can_be_run_again(): void
    {
        $this->seed(AdvSeeder::class);
        $this->seed(AdvSeeder::class);
        $this->assertDatabaseCount('advs', 4);
        $this->assertSame(['i-truck', 'i-box', 'i-shield', 'i-headset'], Adv::orderBy('position')->pluck('icon')->all());
        $this->assertSame(4, Adv::where('public', true)->count());
        $this->assertDatabaseHas('advs_languages', ['lacale' => 'ru', 'title' => 'Быстрая доставка', 'description' => 'Отправка по всей Украине удобной службой доставки']);
        $this->assertDatabaseHas('advs_languages', ['lacale' => 'uk', 'title' => 'Швидка доставка']);
    }
}
