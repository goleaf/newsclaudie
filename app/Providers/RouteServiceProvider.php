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

        // SECURITY: Rate limiter for comment creation (prevent spam and abuse)
        RateLimiter::for('comments', function (Request $request) {
            // Authenticated users: 10 comments per minute per user
            // Anonymous/Guest: Should not reach here (auth required)
            $key = $request->user()?->id ?? $request->ip();
            
            return [
                // Primary limit: 10 comments per minute
                Limit::perMinute(10)
                    ->by($key)
                    ->response(function () {
                        return back()->with('error', __('comments.rate_limit_exceeded'));
                    }),
                
                // Secondary limit: 50 comments per hour (prevent sustained abuse)
                Limit::perHour(50)
                    ->by($key)
                    ->response(function () {
                        return back()->with('error', __('comments.hourly_limit_exceeded'));
                    }),
            ];
        });
    }
}
