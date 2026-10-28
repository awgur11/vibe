<?php

namespace App\Http\Middleware;

use App\Models\Language;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetSiteLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $baseLocale = config('app.locale');
        $request->attributes->set('site_base_locale', $baseLocale);
        Language::ensureBase();
        $locale = $request->route('locale') ?? $baseLocale;
        abort_unless(is_string($locale) && Language::where('locale', $locale)->where('active', true)->exists(), 404);

        app()->setLocale($locale);

        return $next($request);
    }
}
