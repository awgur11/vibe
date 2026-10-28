<?php

namespace App\Services;

use App\Models\Language;
use Illuminate\Http\Request;

class LanguageSwitcherService 
{
    /** @return list<array{locale: string, title: string, short: string, url: string, active: bool}> */
    public function forRequest(): array
    {       
        return Language::where('active', true)->get()->map(fn (Language $language): array => [
            'locale' => str_replace('_', '-', $language->locale),
            'title' => $language->title,
            'short' => $language->short,
            'url' => url(($language->locale == config('app.fallback_locale') ? '' : $language->locale.'/').config('app.url_no_locale')),
            'active' => $language->locale === app()->getLocale(),
        ])->all();
    }
}
