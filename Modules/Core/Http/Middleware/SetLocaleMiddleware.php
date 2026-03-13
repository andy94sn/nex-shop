<?php

declare(strict_types=1);

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reads the Accept-Language header (or ?lang= query param / GraphQL argument)
 * and sets the active application locale for every request.
 *
 * Priority: GraphQL `locale` argument → ?lang= query → Accept-Language header → default
 */
class SetLocaleMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = config('core.locales', ['ro', 'ru']);
        $default   = config('core.default_locale', 'ro');

        $locale = $this->resolveLocale($request, $supported, $default);

        App::setLocale($locale);

        // Make locale available to GraphQL context
        $request->attributes->set('locale', $locale);

        return $next($request);
    }

    private function resolveLocale(Request $request, array $supported, string $default): string
    {
        // 1. Query param ?lang=ro  (highest priority – explicit per-request override)
        if ($lang = $request->query('lang')) {
            if (in_array($lang, $supported, true)) {
                return $lang;
            }
        }

        // 2. X-Locale custom header
        if ($xloc = $request->header('X-Locale')) {
            if (in_array($xloc, $supported, true)) {
                return $xloc;
            }
        }

        // 3. Session-persisted locale (set by switchLanguage mutation)
        if ($request->hasSession()) {
            $sessionLocale = $request->session()->get('locale');
            // dd($request->session()->all());
            if ($sessionLocale && in_array($sessionLocale, $supported, true)) {
                return $sessionLocale;
            }
        }

        // 4. Accept-Language header (browser default – lowest explicit priority)
        $headerLang = $request->getPreferredLanguage($supported);
        if ($headerLang && in_array($headerLang, $supported, true)) {
            return $headerLang;
        }

        return $default;
    }
}
