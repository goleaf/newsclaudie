<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

final class SetLocaleFromSession
{
    /**
     * Apply the session locale to the application on every request.
     */
    public function handle(Request $request, Closure $next)
    {
        $availableLocales = config('app.supported_locales', [config('app.locale')]);
        $sessionLocale = $request->session()->get('locale', config('app.locale'));

        if (! in_array($sessionLocale, $availableLocales, true)) {
            $sessionLocale = config('app.fallback_locale');
            $request->session()->put('locale', $sessionLocale);
        }

        app()->setLocale($sessionLocale);

        return $next($request);
    }
}
