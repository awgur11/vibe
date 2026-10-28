<?php

namespace Tests\Feature;

use App\Models\Language;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LanguageSwitcherTest extends TestCase
{
    use RefreshDatabase;

    public function test_switcher_preserves_article_path_and_query_and_lists_only_active_languages(): void
    {
        Language::ensureBase();
        Language::factory()->create(['locale' => 'en', 'short' => 'ENG', 'title' => 'English']);
        Language::factory()->create(['locale' => 'de', 'active' => false]);
        $blog = Page::factory()->create(['type' => 'pages', 'public' => true, 'template' => 'blog']);
        $article = Page::factory()->create(['type' => 'articles', 'public' => true]);
        $article->translations()->create(['lacale' => 'en', 'title' => 'English article', 'content' => 'English content']);
        $url = route('localized.article', ['locale' => 'en', 'blogSlag' => $blog->slag, 'articleSlag' => $article->slag]);

        $this->get($url.'?page=2')->assertOk()->assertSee('lang="en"', false)
            ->assertSee('English article')->assertSee('English content')->assertSee('Мова сайту')
            ->assertViewHas('languageButtons', function (array $buttons) use ($url): bool {
                return array_column($buttons, 'locale') === ['uk', 'en']
                    && $buttons[0]['url'] === str_replace('/en/', '/', $url).'?page=2'
                    && $buttons[1]['active'] === true;
            });
        $this->get($url)->assertOk()->assertSee('English content')->assertSee('lang="en"', false);
    }

    public function test_switcher_is_available_on_homepage_and_standard_pages(): void
    {
        Language::ensureBase();
        Language::factory()->create(['locale' => 'en', 'short' => 'ENG']);
        $page = Page::factory()->create(['public' => true]);

        foreach ([route('index'), route('page', $page->slag)] as $url) {
            $this->get($url)->assertOk()->assertSee('Мова сайту')->assertSee('ENG')
                ->assertViewHas('languageButtons', fn (array $buttons): bool => count($buttons) === 2);
        }
    }

    public function test_unknown_inactive_and_malformed_locales_return_not_found(): void
    {
        Language::ensureBase();
        Language::factory()->create(['locale' => 'de', 'active' => false]);

        foreach (['zz', 'de', 'invalid-locale'] as $locale) {
            $this->get('/'.$locale)->assertNotFound();
        }
    }

    public function test_base_locale_is_removed_with_permanent_redirect_and_query_is_preserved(): void
    {
        Language::ensureBase();
        Language::factory()->create(['locale' => 'en', 'short' => 'ENG']);

        $this->get('/')->assertOk()->assertSee('lang="uk"', false);
        foreach (['' => '/', '/blog' => '/blog', '/blog/article' => '/blog/article'] as $suffix => $target) {
            $this->get('/uk'.$suffix.'?page=2')->assertStatus(301)->assertRedirect(url($target).'?page=2');
        }
        $this->get('/en')->assertOk()->assertSee('lang="en"', false)
            ->assertSee('href="'.route('localized.index', ['locale' => 'en']).'"', false);
    }

    public function test_base_locale_in_redirect_comes_from_configuration(): void
    {
        config(['app.locale' => 'fr']);
        Language::ensureBase();

        $this->get('/fr/blog')->assertStatus(301)->assertRedirect(url('/blog'));
        $this->get('/')->assertOk()->assertSee('lang="fr"', false);
    }

    public function test_menu_links_preserve_locale_when_following_pages_and_returning_home(): void
    {
        Language::ensureBase();
        Language::factory()->create(['locale' => 'ru', 'short' => 'RUS']);
        $page = Page::factory()->create(['type' => 'pages', 'public' => true, 'is_menu' => true, 'slag' => 'pro-nas']);
        $page->translations()->create(['lacale' => 'ru', 'title' => 'О нас', 'menu' => 'О нас', 'content' => 'Русский текст']);
        $baseLocale = config('app.locale');

        foreach (['uk', 'ru'] as $locale) {
            app()->setLocale($baseLocale);
            $homeUrl = route('index');
            $pageUrl = route('page', ['slag' => $page->slag]);
            foreach ([$homeUrl, $pageUrl, $homeUrl] as $url) {
                app()->setLocale($baseLocale);
                $response = $this->get($url)->assertOk()->assertSee('lang="'.$locale.'"', false);
                preg_match('/<ul class="navbar-nav.*?<\/ul>/s', $response->getContent(), $matches);
                $this->assertNotEmpty($matches);
                $this->assertStringContainsString('href="'.$homeUrl.'"', $matches[0]);
                $this->assertStringContainsString('href="'.$pageUrl.'"', $matches[0]);
                if ($locale === 'ru') {
                    $this->assertStringContainsString('О нас', $matches[0]);
                }
            }
        }
    }

    public function test_single_language_does_not_render_switcher(): void
    {
        Language::ensureBase();

        $this->get(route('index'))->assertOk()->assertDontSee('Мова сайту');
    }
}
