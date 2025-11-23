<?php

declare(strict_types=1);

if (!function_exists('csp_nonce')) {
    /**
     * Get the Content Security Policy nonce for the current request.
     * 
     * This nonce should be used in inline script and style tags to allow
     * them while maintaining CSP protection.
     * 
     * @return string The CSP nonce value
     * 
     * @example
     * ```blade
     * <script nonce="{{ csp_nonce() }}">
     *     // Inline script
     * </script>
     * 
     * <style nonce="{{ csp_nonce() }}">
     *     :root {
     *         --color-primary: {{ DesignTokens::brandColor('primary') }};
     *     }
     * </style>
     * ```
     */
    function csp_nonce(): string
    {
        return request()->attributes->get('csp_nonce', '');
    }
}
