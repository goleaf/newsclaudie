<?php

declare(strict_types=1);

namespace App\Support\Pagination;

use Illuminate\Http\Request;

/**
 * Lightweight helpers for normalizing per-page selections across controllers and Livewire components.
 */
final class PageSize
{
    /**
     * Lowest-common per-page fallback used when no options are configured.
     */
    public const FALLBACK = 15;

    /**
     * Default query-string parameter used for HTTP-based pagination.
     */
    public static function queryParam(): string
    {
        return config('interface.pagination.param', 'per_page');
    }

    /**
     * Resolve a requested per-page value against the allowed options.
     *
     * @param  array<int>  $allowed
     */
    public static function resolve(?int $requested, array $allowed, int $default): int
    {
        $options = self::options($allowed, $default);

        if ($requested === null) {
            return $default;
        }

        return in_array($requested, $options, true) ? $requested : $default;
    }

    /**
     * Resolve a per-page value from a named context defined in config/interface.php.
     */
    public static function resolveForContext(?int $requested, string $context): int
    {
        $default = self::contextDefault($context);

        return self::resolve($requested, self::contextOptions($context), $default);
    }

    /**
     * Resolve a per-page value directly from the incoming request.
     *
     * @param  array<int>  $allowed
     */
    public static function resolveFromRequest(Request $request, string $input, array $allowed, int $default): int
    {
        $value = $request->input($input);
        $requested = is_numeric($value) ? (int) $value : null;

        return self::resolve($requested, $allowed, $default);
    }

    /**
     * Sanitize, deduplicate, and sort page-size options while guaranteeing that the default exists.
     *
     * @param  array<int>  $allowed
     * @return array<int>
     */
    public static function options(array $allowed, int $default): array
    {
        $options = array_values(array_unique(array_map(
            static fn ($value): int => (int) $value,
            $allowed
        )));

        $options = array_values(array_filter($options, static fn (int $value): bool => $value > 0));

        if (! in_array($default, $options, true)) {
            $options[] = $default;
        }

        sort($options);

        return $options;
    }

    /**
     * Fetch and sanitize the configured options for a named context.
     *
     * @return array<int>
     */
    public static function contextOptions(string $context): array
    {
        $configured = config("interface.pagination.options.{$context}", []);
        $default = self::contextDefault($context);

        return self::options($configured, $default);
    }

    public static function contextDefault(string $context): int
    {
        $defaults = config('interface.pagination.defaults', []);

        $fallback = self::FALLBACK;

        if ($context === 'comments') {
            $fallback = (int) config('blog.commentsPerPage', $fallback);
        }

        return (int) ($defaults[$context] ?? $fallback);
    }
}
