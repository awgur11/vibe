<?php

namespace App\Services;

use App\Models\Language;

class SiteSetLocale
{
    public function handle(): void
    {

        $request = request();

        $segments = $request->segments();

        $urlLocale = $request->segment(1);

        config([
            'app.url_prefix' => '',
            'app.url_no_locale' => implode('/', $segments),
        ]);

        if (!$urlLocale) {
            return;
        }

        $language = Language::where('locale', $urlLocale)->where('active', true)->first();

        if (!$language) {
            return;
        }

        // Устанавливаем язык приложения
        app()->setLocale($language->locale);

        // Удаляем языковой префикс
        array_shift($segments);

        $urlNoLocale = implode('/', $segments);
        
        config([
            'app.url_prefix' => $language->locale,
            'app.url_no_locale' => $urlNoLocale,
        ]);
    }
}