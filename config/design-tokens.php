<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Design Tokens
    |--------------------------------------------------------------------------
    |
    | This file contains the design tokens for the application's design system.
    | These tokens define colors, spacing, typography, borders, and shadows
    | that ensure visual consistency across all components.
    |
    */

    'colors' => [
        'brand' => [
            'primary' => '#6366f1',    // indigo-500
            'secondary' => '#8b5cf6',  // violet-500
            'accent' => '#ec4899',     // pink-500
        ],
        'semantic' => [
            'success' => '#10b981',    // emerald-500
            'warning' => '#f59e0b',    // amber-500
            'error' => '#ef4444',      // red-500
            'info' => '#3b82f6',       // blue-500
        ],
        'neutral' => [
            '50' => '#f8fafc',         // slate-50
            '100' => '#f1f5f9',        // slate-100
            '200' => '#e2e8f0',        // slate-200
            '300' => '#cbd5e1',        // slate-300
            '400' => '#94a3b8',        // slate-400
            '500' => '#64748b',        // slate-500
            '600' => '#475569',        // slate-600
            '700' => '#334155',        // slate-700
            '800' => '#1e293b',        // slate-800
            '900' => '#0f172a',        // slate-900
            '950' => '#020617',        // slate-950
        ],
    ],

    'spacing' => [
        'xs' => '0.5rem',   // 8px
        'sm' => '0.75rem',  // 12px
        'md' => '1rem',     // 16px
        'lg' => '1.5rem',   // 24px
        'xl' => '2rem',     // 32px
        '2xl' => '3rem',    // 48px
        '3xl' => '4rem',    // 64px
    ],

    'typography' => [
        'families' => [
            'sans' => ['Inter', 'system-ui', 'sans-serif'],
            'display' => ['Cal Sans', 'Inter', 'sans-serif'],
            'mono' => ['JetBrains Mono', 'Menlo', 'Monaco', 'Courier New', 'monospace'],
        ],
        'sizes' => [
            'xs' => '0.75rem',      // 12px
            'sm' => '0.875rem',     // 14px
            'base' => '1rem',       // 16px
            'lg' => '1.125rem',     // 18px
            'xl' => '1.25rem',      // 20px
            '2xl' => '1.5rem',      // 24px
            '3xl' => '1.875rem',    // 30px
            '4xl' => '2.25rem',     // 36px
            '5xl' => '3rem',        // 48px
        ],
        'weights' => [
            'normal' => '400',
            'medium' => '500',
            'semibold' => '600',
            'bold' => '700',
        ],
        'lineHeights' => [
            'tight' => '1.25',
            'normal' => '1.5',
            'relaxed' => '1.75',
        ],
    ],

    'radius' => [
        'sm' => '0.5rem',    // 8px
        'md' => '0.75rem',   // 12px
        'lg' => '1rem',      // 16px
        'xl' => '1.5rem',    // 24px
        '2xl' => '2rem',     // 32px
        'full' => '9999px',
    ],

    'shadows' => [
        'sm' => '0 1px 2px 0 rgb(0 0 0 / 0.05)',
        'md' => '0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1)',
        'lg' => '0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1)',
        'xl' => '0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1)',
        '2xl' => '0 25px 50px -12px rgb(0 0 0 / 0.25)',
    ],

    'elevation' => [
        'none' => '',
        'sm' => 'shadow-sm',
        'md' => 'shadow-md',
        'lg' => 'shadow-lg shadow-slate-200/50 dark:shadow-slate-950/50',
        'xl' => 'shadow-xl shadow-slate-200/50 dark:shadow-slate-950/50',
        '2xl' => 'shadow-2xl shadow-slate-200/50 dark:shadow-slate-950/50',
    ],

    'transitions' => [
        'fast' => '150ms',
        'base' => '200ms',
        'slow' => '300ms',
        'slower' => '500ms',
    ],

    'animations' => [
        'durations' => [
            'fast' => '150ms',
            'base' => '200ms',
            'slow' => '300ms',
            'slower' => '500ms',
        ],
        'easings' => [
            'linear' => 'linear',
            'in' => 'cubic-bezier(0.4, 0, 1, 1)',
            'out' => 'cubic-bezier(0, 0, 0.2, 1)',
            'in-out' => 'cubic-bezier(0.4, 0, 0.2, 1)',
        ],
    ],
];
