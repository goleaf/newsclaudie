<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\PageView;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

final class AnalyticsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (! Config::get('analytics.enabled') || app()->environment('testing')) {
            return $response;
        }

        // Use the terminate method to execute code after the response is sent.
        app()->terminating(function () use ($request) {
            $path = $request->path();
            $excludedPaths = Config::get('analytics.excluded_paths', []);

            // Check if the current path matches any excluded paths
            foreach ($excludedPaths as $excludedPath) {
                if (Str::is($excludedPath, $path)) {
                    return;
                }
            }

            PageView::fromRequest($request);
        });

        return $response;
    }
}
