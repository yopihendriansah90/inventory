<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetInventoryLocale
{
    public const COOKIE = 'inventory_locale';

    public const LANGUAGES = ['en', 'id'];

    public function handle(Request $request, Closure $next): Response
    {
        $previous = app()->getLocale();
        $locale = $request->cookie(self::COOKIE);
        app()->setLocale(in_array($locale, self::LANGUAGES, true) ? $locale : 'en');

        try {
            return $next($request);
        } finally {
            app()->setLocale($previous);
        }
    }
}
