<?php

namespace Tests\Feature;

use App\Models\Language;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_interface_and_validation_are_ukrainian_by_default(): void
    {
        $this->assertSame('uk', config('app.locale'));
        $this->get('/login')->assertOk()->assertSee('lang="uk"', false)->assertSee('Увійти')->assertDontSee('Войти');
        $this->get('/register')->assertOk()->assertSee('Реєстрація');
        $this->post('/register', [])->assertSessionHasErrors(['name' => 'Поле ім’я є обов’язковим.', 'email', 'password']);
        $this->actingAs(User::factory()->create())->get(route('languages.index'))->assertOk()->assertSee('Мови')->assertSee('Додати мову')->assertDontSee('Языки');
    }

    public function test_short_rejects_wrong_length_numbers_and_symbols(): void
    {
        $this->actingAs(User::factory()->create());
        foreach (['EN', 'ENGG', 'E1G', '12A', 'E-G', '   '] as $short) {
            $this->post(route('languages.store'), ['locale' => 'de', 'short' => $short, 'title' => 'Німецька', 'active' => 1])->assertSessionHasErrors('short');
        }
        $this->assertDatabaseMissing('languages', ['locale' => 'de']);
        $this->post(route('languages.store'), ['locale' => 'de', 'short' => 'DEU', 'title' => 'Німецька', 'active' => 1])->assertRedirect(route('languages.index'));
        $language = Language::where('locale', 'de')->firstOrFail();
        $this->put(route('languages.update', $language), ['locale' => 'de', 'short' => 'DE', 'title' => 'Німецька', 'active' => 1])->assertSessionHasErrors('short');
        $this->assertSame('DEU', $language->fresh()->short);
        $this->assertSame('UKR', Language::where('locale', 'uk')->firstOrFail()->short);
    }

    public function test_russian_translation_files_render_interface_and_messages(): void
    {
        app()->setLocale('ru');
        $this->get('/login')->assertOk()->assertSee('lang="ru"', false)->assertSee('Войти');
        $this->post('/login', ['email' => 'test@example.com', 'password' => 'wrong'])->assertSessionHasErrors(['email' => 'Неверный email или пароль.']);
        $this->assertSame('Языки', __('Языки'));
        $this->assertSame('Сокращение должно содержать ровно три буквы.', __('Скорочення має містити рівно три літери.'));
    }

    public function test_http_error_messages_are_translated(): void
    {
        $this->get('/missing-page')->assertNotFound()->assertSee('Сторінку не знайдено');
        app()->setLocale('ru');
        $this->get('/missing-page')->assertNotFound()->assertSee('Страница не найдена');
    }

    public function test_translation_catalogs_have_matching_keys(): void
    {
        $uk = json_decode(file_get_contents(lang_path('uk.json')), true, flags: JSON_THROW_ON_ERROR);
        $ru = json_decode(file_get_contents(lang_path('ru.json')), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame(array_keys($uk), array_keys($ru));
        foreach (['auth', 'passwords', 'validation', 'pagination'] as $file) {
            $this->assertSame(array_keys(require lang_path("uk/$file.php")), array_keys(require lang_path("ru/$file.php")));
        }
    }

    public function test_password_reset_email_is_translated_in_both_languages(): void
    {
        $user = User::factory()->create();
        foreach (['uk' => 'Відновлення пароля', 'ru' => 'Восстановление пароля'] as $locale => $subject) {
            app()->setLocale($locale);
            $message = (new ResetPassword('token'))->toMail($user);
            $this->assertSame($subject, $message->subject);
            $html = (string) $message->render();
            $this->assertStringNotContainsString('Hello!', $html);
            $this->assertStringNotContainsString('All rights reserved.', $html);
            $this->assertStringNotContainsString('If you\'re having trouble', $html);
        }
    }
}
