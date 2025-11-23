<?php

declare(strict_types=1);

use App\Support\DesignTokens;

describe('DesignTokens Performance', function () {
    beforeEach(function () {
        // Clear cache before each test
        DesignTokens::clearCache();
    });

    it('caches tokens after first access', function () {
        // First call loads from config
        $start = microtime(true);
        $color1 = DesignTokens::brandColor('primary');
        $time1 = microtime(true) - $start;

        // Second call uses cache
        $start = microtime(true);
        $color2 = DesignTokens::brandColor('primary');
        $time2 = microtime(true) - $start;

        expect($color1)->toBe($color2);
        // Cached call should be significantly faster
        expect($time2)->toBeLessThan($time1);
    });

    it('reuses cache across different token types', function () {
        // First call loads entire config
        DesignTokens::brandColor('primary');

        // Subsequent calls to different categories should be fast
        $start = microtime(true);
        DesignTokens::semanticColor('success');
        DesignTokens::spacing('lg');
        DesignTokens::fontSize('base');
        $totalTime = microtime(true) - $start;

        // All cached calls should complete very quickly
        expect($totalTime)->toBeLessThan(0.001); // Less than 1ms
    });

    it('handles multiple rapid calls efficiently', function () {
        $start = microtime(true);

        // Simulate component rendering with many token accesses
        for ($i = 0; $i < 100; $i++) {
            DesignTokens::brandColor('primary');
            DesignTokens::spacing('md');
            DesignTokens::shadow('lg');
        }

        $totalTime = microtime(true) - $start;

        // 300 calls should complete very quickly with caching
        expect($totalTime)->toBeLessThan(0.01); // Less than 10ms
    });

    it('clears cache when requested', function () {
        // Load tokens
        $color1 = DesignTokens::brandColor('primary');

        // Clear cache
        DesignTokens::clearCache();

        // Should reload from config
        $color2 = DesignTokens::brandColor('primary');

        expect($color1)->toBe($color2);
    });

    it('returns correct values after caching', function () {
        expect(DesignTokens::brandColor('primary'))->toBe('#6366f1');
        expect(DesignTokens::semanticColor('success'))->toBe('#10b981');
        expect(DesignTokens::spacing('lg'))->toBe('1.5rem');
        expect(DesignTokens::fontSize('base'))->toBe('1rem');
        expect(DesignTokens::fontWeight('bold'))->toBe('700');
    });

    it('handles all token categories efficiently', function () {
        $start = microtime(true);

        // Access all token categories
        DesignTokens::brandColor('primary');
        DesignTokens::semanticColor('success');
        DesignTokens::neutralColor('500');
        DesignTokens::spacing('md');
        DesignTokens::fontFamily('sans');
        DesignTokens::fontSize('base');
        DesignTokens::fontWeight('normal');
        DesignTokens::lineHeight('normal');
        DesignTokens::borderRadius('lg');
        DesignTokens::shadow('md');
        DesignTokens::elevation('lg');
        DesignTokens::transitionDuration('base');
        DesignTokens::animationDuration('fast');
        DesignTokens::animationEasing('out');

        $totalTime = microtime(true) - $start;

        // All categories accessed should be fast
        expect($totalTime)->toBeLessThan(0.005); // Less than 5ms
    });

    it('category method returns complete arrays', function () {
        $colors = DesignTokens::category('colors');

        expect($colors)->toBeArray();
        expect($colors)->toHaveKeys(['brand', 'semantic', 'neutral']);
    });

    it('all method returns complete token set', function () {
        $tokens = DesignTokens::all();

        expect($tokens)->toBeArray();
        expect($tokens)->toHaveKeys([
            'colors',
            'spacing',
            'typography',
            'radius',
            'shadows',
            'elevation',
            'transitions',
            'animations',
        ]);
    });
});

describe('DesignTokens Memory Efficiency', function () {
    it('uses minimal memory for caching', function () {
        $memoryBefore = memory_get_usage();

        // Load all tokens
        DesignTokens::all();

        $memoryAfter = memory_get_usage();
        $memoryUsed = $memoryAfter - $memoryBefore;

        // Token cache should use less than 50KB
        expect($memoryUsed)->toBeLessThan(50 * 1024);
    });
});
