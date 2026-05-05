<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleFromSession
{
    /** @var list<string> */
    private const ALLOWED_LOCALES = ['en', 'ar'];

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('patient_locale');

        if (is_string($locale) && in_array($locale, self::ALLOWED_LOCALES, true)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
