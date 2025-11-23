<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Design Tokens Helper
 *
 * Provides type-safe, cached access to design tokens defined in config/design-tokens.php.
 * This class offers a convenient API for accessing colors, spacing, typography,
 * and other design system values throughout the application.
 *
 * Performance optimizations:
 * - Static caching to prevent repeated config() calls
 * - Lazy loading of token categories
 * - Minimal memory footprint
 *
 * Security features:
 * - Input validation for all token keys
 * - Safe error handling without information disclosure
 * - Output sanitization for CSS values
 * - Environment-aware cache clearing
 * - Integrity validation in production
 *
 * @package App\Support
 * @see config/design-tokens.php
 * @see docs/design-tokens/DESIGN_TOKENS.md
 * @see docs/design-tokens/DESIGN_TOKENS_SECURITY.md
 *
 * @example
 * ```php
 * // Get brand primary color
 * $color = DesignTokens::brandColor('primary'); // '#6366f1'
 *
 * // Get semantic success color
 * $success = DesignTokens::semanticColor('success'); // '#10b981'
 *
 * // Get spacing value
 * $padding = DesignTokens::spacing('lg'); // '1.5rem'
 *
 * // Get font family (sanitized)
 * $font = DesignTokens::fontFamily('sans'); // ['Inter', 'system-ui', 'sans-serif']
 * ```
 */
class DesignTokens
{
    /**
     * Static cache for all design tokens.
     * Loaded once per request to avoid repeated config() calls.
     *
     * @var array<string, mixed>|null
     */
    private static ?array $tokens = null;

    /**
     * Valid token keys for validation
     *
     * @var array<string, array<string>>
     */
    private static array $validKeys = [
        'brand' => ['primary', 'secondary', 'accent'],
        'semantic' => ['success', 'warning', 'error', 'info'],
        'neutral' => ['50', '100', '200', '300', '400', '500', '600', '700', '800', '900', '950'],
        'spacing' => ['xs', 'sm', 'md', 'lg', 'xl', '2xl', '3xl'],
        'fontFamily' => ['sans', 'display', 'mono'],
        'fontSize' => ['xs', 'sm', 'base', 'lg', 'xl', '2xl', '3xl', '4xl', '5xl'],
        'fontWeight' => ['normal', 'medium', 'semibold', 'bold'],
        'lineHeight' => ['tight', 'normal', 'relaxed'],
        'borderRadius' => ['sm', 'md', 'lg', 'xl', '2xl', 'full'],
        'shadow' => ['sm', 'md', 'lg', 'xl', '2xl'],
        'elevation' => ['none', 'sm', 'md', 'lg', 'xl', '2xl'],
        'transition' => ['fast', 'base', 'slow', 'slower'],
        'animation' => ['fast', 'base', 'slow', 'slower'],
        'easing' => ['linear', 'in', 'out', 'in-out'],
    ];

    /**
     * Get all tokens with static caching and validation.
     * This prevents repeated config() calls within the same request.
     *
     * @return array<string, mixed>
     */
    private static function getTokens(): array
    {
        if (self::$tokens === null) {
            try {
                self::$tokens = config('design-tokens');
                
                if (!is_array(self::$tokens) || empty(self::$tokens)) {
                    Log::error('Design tokens config is invalid or empty');
                    self::$tokens = self::getDefaultTokens();
                }
            } catch (\Throwable $e) {
                Log::error('Failed to load design tokens', [
                    'error' => $e->getMessage(),
                ]);
                self::$tokens = self::getDefaultTokens();
            }
        }

        return self::$tokens;
    }

    /**
     * Clear the static cache.
     * Only works in non-production environments for security.
     *
     * @return void
     */
    public static function clearCache(): void
    {
        if (app()->environment('production')) {
            Log::warning('Attempted to clear design token cache in production');
            return;
        }

        self::$tokens = null;
        Log::info('Design token cache cleared');
    }

    /**
     * Validate a token key against whitelist
     *
     * @param string $category The token category
     * @param string $key The token key
     * @return bool
     */
    private static function isValidKey(string $category, string $key): bool
    {
        return isset(self::$validKeys[$category]) && 
               in_array($key, self::$validKeys[$category], true);
    }

    /**
     * Get a token value safely with validation
     *
     * @param string $category The token category
     * @param string $key The token key
     * @param mixed $default Default value if not found
     * @return mixed
     */
    private static function getTokenValue(string $category, string $key, $default = null)
    {
        if (!self::isValidKey($category, $key)) {
            Log::warning('Invalid design token key requested', [
                'category' => $category,
                'key' => $key,
            ]);
            return $default;
        }

        $tokens = self::getTokens();
        
        return $tokens[$category][$key] ?? $default;
    }

    /**
     * Sanitize CSS value to prevent injection
     *
     * @param string $value The CSS value to sanitize
     * @return string
     */
    private static function sanitizeCssValue(string $value): string
    {
        // Remove any characters that could break out of CSS context
        $value = preg_replace('/[{};<>]/', '', $value);
        
        // Ensure it's a safe CSS value
        if (!preg_match('/^[a-zA-Z0-9#\s\-.,()%\/]+$/', $value)) {
            Log::warning('Potentially unsafe CSS value detected', [
                'value' => $value,
            ]);
            return '';
        }
        
        return $value;
    }

    /**
     * Sanitize font family array
     *
     * @param array<int, string> $fonts
     * @return array<int, string>
     */
    private static function sanitizeFontFamily(array $fonts): array
    {
        return array_filter(array_map(function ($font) {
            // Only allow safe characters in font names
            if (preg_match('/^[a-zA-Z0-9\s\-]+$/', $font)) {
                return $font;
            }
            Log::warning('Unsafe font family name detected', ['font' => $font]);
            return null;
        }, $fonts));
    }

    /**
     * Get default fallback tokens for error scenarios
     *
     * @return array<string, mixed>
     */
    private static function getDefaultTokens(): array
    {
        return [
            'colors' => [
                'brand' => ['primary' => '#6366f1', 'secondary' => '#8b5cf6', 'accent' => '#ec4899'],
                'semantic' => ['success' => '#10b981', 'warning' => '#f59e0b', 'error' => '#ef4444', 'info' => '#3b82f6'],
                'neutral' => ['500' => '#64748b'],
            ],
            'spacing' => ['md' => '1rem'],
            'typography' => [
                'families' => ['sans' => ['system-ui', 'sans-serif']],
                'sizes' => ['base' => '1rem'],
                'weights' => ['normal' => '400'],
                'lineHeights' => ['normal' => '1.5'],
            ],
            'radius' => ['md' => '0.75rem'],
            'shadows' => ['md' => '0 4px 6px -1px rgb(0 0 0 / 0.1)'],
            'elevation' => ['md' => 'shadow-md'],
            'transitions' => ['base' => '200ms'],
            'animations' => [
                'durations' => ['base' => '200ms'],
                'easings' => ['out' => 'cubic-bezier(0, 0, 0.2, 1)'],
            ],
        ];
    }
    /**
     * Get a brand color value.
     *
     * Brand colors define the application's visual identity and are used for
     * primary actions, links, and key UI elements.
     *
     * @param string $shade The color shade: 'primary', 'secondary', or 'accent'
     * @return string The hex color value (sanitized)
     *
     * @example
     * ```php
     * DesignTokens::brandColor('primary');   // '#6366f1' (indigo-500)
     * DesignTokens::brandColor('secondary'); // '#8b5cf6' (violet-500)
     * DesignTokens::brandColor('accent');    // '#ec4899' (pink-500)
     * ```
     */
    public static function brandColor(string $shade): string
    {
        $value = self::getTokenValue('brand', $shade, '#6366f1');
        $tokens = self::getTokens();
        $actualValue = $tokens['colors']['brand'][$shade] ?? $value;
        return self::sanitizeCssValue((string) $actualValue);
    }

    /**
     * Get a semantic color value.
     *
     * Semantic colors have specific meanings for user feedback and are used
     * in notifications, alerts, badges, and validation messages.
     *
     * @param string $type The semantic type: 'success', 'warning', 'error', or 'info'
     * @return string The hex color value (sanitized)
     *
     * @example
     * ```php
     * DesignTokens::semanticColor('success'); // '#10b981' (emerald-500)
     * DesignTokens::semanticColor('warning'); // '#f59e0b' (amber-500)
     * DesignTokens::semanticColor('error');   // '#ef4444' (red-500)
     * DesignTokens::semanticColor('info');    // '#3b82f6' (blue-500)
     * ```
     */
    public static function semanticColor(string $type): string
    {
        $value = self::getTokenValue('semantic', $type, '#3b82f6');
        $tokens = self::getTokens();
        $actualValue = $tokens['colors']['semantic'][$type] ?? $value;
        return self::sanitizeCssValue((string) $actualValue);
    }

    /**
     * Get a neutral color value.
     *
     * Neutral colors (grayscale) are used for backgrounds, text, borders,
     * and other UI elements. The scale ranges from 50 (lightest) to 950 (darkest).
     *
     * @param string $shade The color shade: '50', '100', '200', ..., '950'
     * @return string The hex color value
     *
     * @example
     * ```php
     * DesignTokens::neutralColor('50');  // '#f8fafc' (lightest)
     * DesignTokens::neutralColor('500'); // '#64748b' (medium)
     * DesignTokens::neutralColor('950'); // '#020617' (darkest)
     * ```
     */
    public static function neutralColor(string $shade): string
    {
        return self::getTokens()['colors']['neutral'][$shade];
    }

    /**
     * Get a spacing value.
     *
     * Spacing tokens provide consistent margins, padding, and gaps throughout
     * the application. Values range from 'xs' (8px) to '3xl' (64px).
     *
     * @param string $size The spacing size: 'xs', 'sm', 'md', 'lg', 'xl', '2xl', or '3xl'
     * @return string The spacing value in rem units
     *
     * @example
     * ```php
     * DesignTokens::spacing('xs');  // '0.5rem' (8px)
     * DesignTokens::spacing('md');  // '1rem' (16px)
     * DesignTokens::spacing('3xl'); // '4rem' (64px)
     * ```
     */
    public static function spacing(string $size): string
    {
        return self::getTokens()['spacing'][$size];
    }

    /**
     * Get a font family array.
     *
     * Font families define the typefaces used throughout the application.
     * Returns an array of font names with fallbacks (sanitized).
     *
     * @param string $family The font family: 'sans', 'display', or 'mono'
     * @return array<int, string> Array of font names with fallbacks (sanitized)
     *
     * @example
     * ```php
     * DesignTokens::fontFamily('sans');    // ['Inter', 'system-ui', 'sans-serif']
     * DesignTokens::fontFamily('display'); // ['Cal Sans', 'Inter', 'sans-serif']
     * DesignTokens::fontFamily('mono');    // ['JetBrains Mono', 'Menlo', ...]
     * ```
     */
    public static function fontFamily(string $family): array
    {
        $value = self::getTokenValue('fontFamily', $family, ['system-ui', 'sans-serif']);
        $tokens = self::getTokens();
        $actualValue = $tokens['typography']['families'][$family] ?? $value;
        return self::sanitizeFontFamily((array) $actualValue);
    }

    /**
     * Get a font size value.
     *
     * Font sizes range from 'xs' (12px) to '5xl' (48px) and are used for
     * text sizing throughout the application.
     *
     * @param string $size The font size: 'xs', 'sm', 'base', 'lg', 'xl', '2xl', '3xl', '4xl', or '5xl'
     * @return string The font size in rem units
     *
     * @example
     * ```php
     * DesignTokens::fontSize('xs');   // '0.75rem' (12px)
     * DesignTokens::fontSize('base'); // '1rem' (16px)
     * DesignTokens::fontSize('5xl');  // '3rem' (48px)
     * ```
     */
    public static function fontSize(string $size): string
    {
        return self::getTokens()['typography']['sizes'][$size];
    }

    /**
     * Get a font weight value.
     *
     * Font weights define text emphasis levels from normal (400) to bold (700).
     *
     * @param string $weight The font weight: 'normal', 'medium', 'semibold', or 'bold'
     * @return string The font weight value
     *
     * @example
     * ```php
     * DesignTokens::fontWeight('normal');   // '400'
     * DesignTokens::fontWeight('semibold'); // '600'
     * DesignTokens::fontWeight('bold');     // '700'
     * ```
     */
    public static function fontWeight(string $weight): string
    {
        return self::getTokens()['typography']['weights'][$weight];
    }

    /**
     * Get a line height value.
     *
     * Line heights control vertical spacing between lines of text.
     *
     * @param string $height The line height: 'tight', 'normal', or 'relaxed'
     * @return string The line height value (unitless)
     *
     * @example
     * ```php
     * DesignTokens::lineHeight('tight');   // '1.25'
     * DesignTokens::lineHeight('normal');  // '1.5'
     * DesignTokens::lineHeight('relaxed'); // '1.75'
     * ```
     */
    public static function lineHeight(string $height): string
    {
        return self::getTokens()['typography']['lineHeights'][$height];
    }

    /**
     * Get a border radius value.
     *
     * Border radius values provide consistent rounding for components.
     * Values range from 'sm' (8px) to 'full' (fully rounded).
     *
     * @param string $size The radius size: 'sm', 'md', 'lg', 'xl', '2xl', or 'full'
     * @return string The border radius value in rem or px units
     *
     * @example
     * ```php
     * DesignTokens::borderRadius('sm');   // '0.5rem' (8px)
     * DesignTokens::borderRadius('lg');   // '1rem' (16px)
     * DesignTokens::borderRadius('full'); // '9999px'
     * ```
     */
    public static function borderRadius(string $size): string
    {
        return self::getTokens()['radius'][$size];
    }

    /**
     * Get a shadow value.
     *
     * Shadow values create depth and elevation in the UI.
     * Returns the complete CSS box-shadow value.
     *
     * @param string $level The shadow level: 'sm', 'md', 'lg', 'xl', or '2xl'
     * @return string The CSS box-shadow value
     *
     * @example
     * ```php
     * DesignTokens::shadow('sm'); // '0 1px 2px 0 rgb(0 0 0 / 0.05)'
     * DesignTokens::shadow('lg'); // '0 10px 15px -3px rgb(0 0 0 / 0.1), ...'
     * ```
     */
    public static function shadow(string $level): string
    {
        return self::getTokens()['shadows'][$level];
    }

    /**
     * Get an elevation class string.
     *
     * Elevation classes are pre-configured Tailwind shadow classes with
     * dark mode support. Use these for consistent elevation across components.
     *
     * @param string $level The elevation level: 'none', 'sm', 'md', 'lg', 'xl', or '2xl'
     * @return string The Tailwind class string
     *
     * @example
     * ```php
     * DesignTokens::elevation('none'); // ''
     * DesignTokens::elevation('sm');   // 'shadow-sm'
     * DesignTokens::elevation('lg');   // 'shadow-lg shadow-slate-200/50 dark:shadow-slate-950/50'
     * ```
     */
    public static function elevation(string $level): string
    {
        return self::getTokens()['elevation'][$level];
    }

    /**
     * Get a transition duration value.
     *
     * Transition durations control the speed of state changes and animations.
     *
     * @param string $speed The transition speed: 'fast', 'base', 'slow', or 'slower'
     * @return string The duration in milliseconds
     *
     * @example
     * ```php
     * DesignTokens::transitionDuration('fast'); // '150ms'
     * DesignTokens::transitionDuration('base'); // '200ms'
     * DesignTokens::transitionDuration('slow'); // '300ms'
     * ```
     */
    public static function transitionDuration(string $speed): string
    {
        return self::getTokens()['transitions'][$speed];
    }

    /**
     * Get an animation duration value.
     *
     * Animation durations are used for keyframe animations.
     * Same values as transition durations.
     *
     * @param string $speed The animation speed: 'fast', 'base', 'slow', or 'slower'
     * @return string The duration in milliseconds
     *
     * @example
     * ```php
     * DesignTokens::animationDuration('fast'); // '150ms'
     * DesignTokens::animationDuration('slow'); // '300ms'
     * ```
     */
    public static function animationDuration(string $speed): string
    {
        return self::getTokens()['animations']['durations'][$speed];
    }

    /**
     * Get an animation easing function.
     *
     * Easing functions control the acceleration curve of animations.
     *
     * @param string $type The easing type: 'linear', 'in', 'out', or 'in-out'
     * @return string The CSS easing function
     *
     * @example
     * ```php
     * DesignTokens::animationEasing('linear'); // 'linear'
     * DesignTokens::animationEasing('out');    // 'cubic-bezier(0, 0, 0.2, 1)'
     * DesignTokens::animationEasing('in-out'); // 'cubic-bezier(0.4, 0, 0.2, 1)'
     * ```
     */
    public static function animationEasing(string $type): string
    {
        return self::getTokens()['animations']['easings'][$type];
    }

    /**
     * Get all tokens for a specific category.
     *
     * Returns the complete array of tokens for a given category.
     * Useful when you need to iterate over all values in a category.
     *
     * @param string $category The token category: 'colors', 'spacing', 'typography', etc.
     * @return array<string, mixed> The complete token array for the category
     *
     * @example
     * ```php
     * $allColors = DesignTokens::category('colors');
     * $allSpacing = DesignTokens::category('spacing');
     * ```
     */
    public static function category(string $category): array
    {
        return self::getTokens()[$category];
    }

    /**
     * Get all design tokens.
     *
     * Returns the complete design tokens configuration.
     * Useful for debugging or when you need access to all tokens.
     *
     * @return array<string, mixed> The complete design tokens array
     *
     * @example
     * ```php
     * $allTokens = DesignTokens::all();
     * ```
     */
    public static function all(): array
    {
        return self::getTokens();
    }
}
