<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectBaseLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        if(config('app.url_prefix') === config('app.fallback_locale'))
            return redirect()->to(config('app.url_no_locale'), 301);
        
        return $next($request);
    }
}
