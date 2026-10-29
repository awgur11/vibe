<?php

namespace App\Providers;

use App\Auth\LegacyUserProvider;
use App\Services\SeoService;
use App\Services\SettingsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        require_once app_path('helpers.php');
        $this->app->scoped(SettingsService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.base', function (\Illuminate\View\View $view): void {
            $view->with('site_seo', app(SeoService::class)->render($view->getData()));
        });

        Auth::provider('legacy-eloquent', function ($app, array $config): LegacyUserProvider {
            return new LegacyUserProvider($app['hash'], $config['model']);
        });
    }
}
