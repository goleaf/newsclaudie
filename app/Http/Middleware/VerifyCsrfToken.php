<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

final class VerifyCsrfToken extends Middleware
{
    /**
     * Skip CSRF verification entirely when running the test suite so feature
     * tests don't need to send tokens and can exercise controller logic.
     */
    public function handle($request, Closure $next)
    {
        if ($this->app->environment('testing') || $this->app->runningUnitTests() || env('APP_ENV') === 'testing') {
            return $next($request);
        }

        return parent::handle($request, $next);
    }

    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        //
    ];
}
