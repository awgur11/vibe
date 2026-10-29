<?php

namespace App\Http\Middleware;

use App\Models\Cart;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsureCartClient
{
    public function handle(Request $request, Closure $next): Response
    {
        $clientId = $request->cookie(Cart::CLIENT_COOKIE);
        if (! is_string($clientId) || ! Str::isUuid($clientId)) {
            $clientId = (string) Str::uuid();
        }
        $request->attributes->set('cart_client_id', $clientId);
        Cart::where('expires_at', '<=', now())->delete();

        $response = $next($request);
        $response->headers->setCookie(Cookie::make(
            name: Cart::CLIENT_COOKIE,
            value: $clientId,
            path: '/',
            domain: config('session.domain'),
            secure: $request->isSecure(),
            httpOnly: true,
            sameSite: 'lax',
        )->withExpires(now()->addMonthsNoOverflow(3)));
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }
}
