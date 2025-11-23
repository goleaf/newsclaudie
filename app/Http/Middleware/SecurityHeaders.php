<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Security Headers Middleware
 * 
 * Adds security headers to all responses to protect against common attacks:
 * - XSS (Cross-Site Scripting)
 * - Clickjacking
 * - MIME sniffing
 * - Information disclosure
 * 
 * @see https://owasp.org/www-project-secure-headers/
 */
final class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // X-Frame-Options: Prevent clickjacking attacks
        $response->headers->set(
            'X-Frame-Options',
            config('security.headers.x_frame_options', 'SAMEORIGIN')
        );

        // X-Content-Type-Options: Prevent MIME sniffing
        $response->headers->set(
            'X-Content-Type-Options',
            config('security.headers.x_content_type_options', 'nosniff')
        );

        // X-XSS-Protection: Enable browser XSS protection
        $response->headers->set(
            'X-XSS-Protection',
            config('security.headers.x_xss_protection', '1; mode=block')
        );

        // Referrer-Policy: Control referrer information
        $response->headers->set(
            'Referrer-Policy',
            config('security.headers.referrer_policy', 'strict-origin-when-cross-origin')
        );

        // Strict-Transport-Security (HSTS): Force HTTPS
        if (config('security.headers.hsts.enabled', true) && $request->secure()) {
            $hsts = sprintf(
                'max-age=%d%s%s',
                config('security.headers.hsts.max_age', 31536000),
                config('security.headers.hsts.include_subdomains', true) ? '; includeSubDomains' : '',
                config('security.headers.hsts.preload', false) ? '; preload' : ''
            );
            
            $response->headers->set('Strict-Transport-Security', $hsts);
        }

        // Content-Security-Policy: Prevent XSS and data injection attacks
        if (config('security.csp.enabled', true)) {
            $csp = $this->buildContentSecurityPolicy();
            $header = config('security.csp.report_only', false) 
                ? 'Content-Security-Policy-Report-Only' 
                : 'Content-Security-Policy';
            
            $response->headers->set($header, $csp);
        }

        // Permissions-Policy: Control browser features
        $response->headers->set(
            'Permissions-Policy',
            'geolocation=(), microphone=(), camera=()'
        );

        return $response;
    }

    /**
     * Build Content Security Policy header value.
     */
    private function buildContentSecurityPolicy(): string
    {
        $directives = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'", // Note: unsafe-inline/eval needed for Alpine.js
            "style-src 'self' 'unsafe-inline'", // Note: unsafe-inline needed for Tailwind
            "img-src 'self' data: https:",
            "font-src 'self' data:",
            "connect-src 'self'",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'",
        ];

        // Add report URI if configured
        if ($reportUri = config('security.csp.report_uri')) {
            $directives[] = "report-uri {$reportUri}";
        }

        return implode('; ', $directives);
    }
}

