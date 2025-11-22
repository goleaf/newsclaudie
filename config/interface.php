<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Pagination Configuration
    |--------------------------------------------------------------------------
    |
    | Configure pagination defaults and available options for different
    | sections of the application. Each section can have its own default
    | per-page value and a set of options users can choose from.
    |
    */

    'pagination' => [
        // Query parameter name for per-page selection
        'param' => 'per_page',

        // Default items per page for each section
        'defaults' => [
            'admin' => 20,
            'comments' => (int) env('BLOGKIT_COMMENTS_PER_PAGE', 10),
            'posts' => 12,
            'categories' => 15,
            'category_posts' => 12,
        ],

        // Available per-page options for each section
        'options' => [
            'admin' => [10, 20, 50, 100],
            'comments' => [10, 25, 50, (int) env('BLOGKIT_COMMENTS_PER_PAGE', 10)],
            'posts' => [12, 18, 24, 36],
            'categories' => [12, 15, 24, 30],
            'category_posts' => [9, 12, 18, 24],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Interface Configuration
    |--------------------------------------------------------------------------
    |
    | Settings specific to the admin portal Livewire CRUD interface.
    | These control behavior for search, filtering, bulk actions, and
    | optimistic UI updates.
    |
    */

    'admin' => [
        /*
        | Search Debounce Timing
        |
        | Milliseconds to wait after user stops typing before triggering
        | a search query. Lower values = more responsive but more server
        | requests. Higher values = fewer requests but less responsive.
        |
        | Recommended: 300ms for most use cases
        | Fast networks: 200-250ms
        | Slow networks or heavy queries: 400-500ms
        */
        'search_debounce_ms' => (int) env('ADMIN_SEARCH_DEBOUNCE_MS', 300),

        /*
        | Form Input Debounce Timing
        |
        | Milliseconds to wait for real-time validation on form inputs.
        | Used for fields like name, slug, email that need live validation.
        |
        | Recommended: 300ms for text inputs, 400ms for textareas
        */
        'form_debounce_ms' => (int) env('ADMIN_FORM_DEBOUNCE_MS', 300),

        /*
        | Textarea Debounce Timing
        |
        | Slightly longer debounce for textarea fields (descriptions, content)
        | since users typically type longer content.
        */
        'textarea_debounce_ms' => (int) env('ADMIN_TEXTAREA_DEBOUNCE_MS', 400),

        /*
        | Bulk Action Limits
        |
        | Maximum number of items that can be selected and processed in a
        | single bulk action. Prevents performance issues and timeouts.
        |
        | Set to null for no limit (not recommended for production)
        | Recommended: 100-500 depending on operation complexity
        */
        'bulk_action_limit' => (int) env('ADMIN_BULK_ACTION_LIMIT', 100),

        /*
        | Bulk Action Warning Threshold
        |
        | Show a warning when bulk action selection exceeds this threshold.
        | Helps users understand they're performing a large operation.
        */
        'bulk_action_warning_threshold' => (int) env('ADMIN_BULK_WARNING_THRESHOLD', 50),

        /*
        | Optimistic UI Updates
        |
        | Enable optimistic UI updates for admin actions. When enabled,
        | the UI updates immediately before server confirmation, then
        | reverts if the action fails.
        |
        | Provides better perceived performance but requires proper
        | error handling and reversion logic.
        */
        'optimistic_ui_enabled' => (bool) env('ADMIN_OPTIMISTIC_UI', true),

        /*
        | Loading Indicator Delay
        |
        | Milliseconds to wait before showing loading indicators for
        | Livewire actions. Prevents flashing spinners for fast actions.
        |
        | Recommended: 500ms (matches Requirement 12.4)
        */
        'loading_indicator_delay_ms' => (int) env('ADMIN_LOADING_DELAY_MS', 500),

        /*
        | Query String Persistence
        |
        | Enable URL query string persistence for filters, search, and sort.
        | Allows bookmarking and sharing filtered views.
        */
        'persist_filters_in_url' => (bool) env('ADMIN_PERSIST_FILTERS', true),

        /*
        | Auto-save Inline Edits
        |
        | Automatically save inline edits after this many milliseconds of
        | inactivity. Set to null to require explicit save action.
        |
        | Recommended: null (require explicit save for data safety)
        */
        'inline_edit_autosave_ms' => env('ADMIN_INLINE_AUTOSAVE_MS', null),

        /*
        | Confirmation Dialogs
        |
        | Require confirmation for destructive actions (delete, bulk delete).
        | Disable only for development/testing.
        */
        'require_delete_confirmation' => (bool) env('ADMIN_REQUIRE_DELETE_CONFIRM', true),

        /*
        | Session Timeout Warning
        |
        | Minutes before session timeout to show a warning. Set to null
        | to disable warnings.
        */
        'session_timeout_warning_minutes' => (int) env('ADMIN_SESSION_WARNING_MIN', 5),
    ],
];
