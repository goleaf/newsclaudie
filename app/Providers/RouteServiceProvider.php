<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

final class RouteServiceProvider extends ServiceProvider
{
    public const HOME = '/';

    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('api', static fn (Request $request): Limit => Limit::perMinute(60)->by(
            $request->user()?->id ?? $request->ip()
        ));

        // Rate limiter for news page (security: prevent scraping and DoS)
        RateLimiter::for('news', function (Request $request) {
            // Anonymize IP for GDPR compliance
            $ip = $request->ip();
            $anonymizedIp = substr($ip, 0, (int) strrpos($ip, '.')) . '.0';

            return Limit::perMinute(60)
                ->by($anonymizedIp)
                ->response(function () {
                    return response()->json([
                        'message' => 'Too many requests. Please try again later.',
                    ], 429);
                });
        });
    }
}
