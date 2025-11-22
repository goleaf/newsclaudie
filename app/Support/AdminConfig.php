<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Helper class for accessing admin interface configuration values.
 *
 * Provides type-safe access to admin configuration with sensible defaults.
 * All timing values are in milliseconds unless otherwise specified.
 */
class AdminConfig
{
    /**
     * Get the search debounce timing in milliseconds.
     *
     * Used for search inputs to delay query execution until user stops typing.
     * Default: 300ms
     */
    public static function searchDebounceMs(): int
    {
        return (int) config('interface.admin.search_debounce_ms', 300);
    }

    /**
     * Get the form input debounce timing in milliseconds.
     *
     * Used for real-time validation on form inputs like name, slug, email.
     * Default: 300ms
     */
    public static function formDebounceMs(): int
    {
        return (int) config('interface.admin.form_debounce_ms', 300);
    }

    /**
     * Get the textarea debounce timing in milliseconds.
     *
     * Used for textarea fields (descriptions, content) which typically
     * have longer input.
     * Default: 400ms
     */
    public static function textareaDebounceMs(): int
    {
        return (int) config('interface.admin.textarea_debounce_ms', 400);
    }

    /**
     * Get the maximum number of items allowed in a bulk action.
     *
     * Returns null if no limit is configured.
     * Default: 100
     */
    public static function bulkActionLimit(): ?int
    {
        $limit = config('interface.admin.bulk_action_limit');
        
        return $limit !== null ? (int) $limit : null;
    }

    /**
     * Get the threshold for showing bulk action warnings.
     *
     * When selection count exceeds this, show a warning to the user.
     * Default: 50
     */
    public static function bulkActionWarningThreshold(): int
    {
        return (int) config('interface.admin.bulk_action_warning_threshold', 50);
    }

    /**
     * Check if optimistic UI updates are enabled.
     *
     * When enabled, UI updates immediately before server confirmation.
     * Default: true
     */
    public static function optimisticUiEnabled(): bool
    {
        return (bool) config('interface.admin.optimistic_ui_enabled', true);
    }

    /**
     * Get the loading indicator delay in milliseconds.
     *
     * Delay before showing loading spinners to prevent flashing.
     * Default: 500ms (matches Requirement 12.4)
     */
    public static function loadingIndicatorDelayMs(): int
    {
        return (int) config('interface.admin.loading_indicator_delay_ms', 500);
    }

    /**
     * Check if filters should be persisted in URL query strings.
     *
     * Enables bookmarkable and shareable filtered views.
     * Default: true
     */
    public static function persistFiltersInUrl(): bool
    {
        return (bool) config('interface.admin.persist_filters_in_url', true);
    }

    /**
     * Get the inline edit auto-save delay in milliseconds.
     *
     * Returns null if auto-save is disabled (requires explicit save).
     * Default: null (disabled)
     */
    public static function inlineEditAutosaveMs(): ?int
    {
        $delay = config('interface.admin.inline_edit_autosave_ms');
        
        return $delay !== null ? (int) $delay : null;
    }

    /**
     * Check if delete confirmation dialogs are required.
     *
     * Should be true in production for data safety.
     * Default: true
     */
    public static function requireDeleteConfirmation(): bool
    {
        return (bool) config('interface.admin.require_delete_confirmation', true);
    }

    /**
     * Get the session timeout warning threshold in minutes.
     *
     * Returns null if warnings are disabled.
     * Default: 5 minutes
     */
    public static function sessionTimeoutWarningMinutes(): ?int
    {
        $minutes = config('interface.admin.session_timeout_warning_minutes');
        
        return $minutes !== null ? (int) $minutes : null;
    }

    /**
     * Check if a bulk action selection count exceeds the configured limit.
     *
     * @param  int  $count  Number of selected items
     * @return bool True if count exceeds limit (when limit is set)
     */
    public static function exceedsBulkActionLimit(int $count): bool
    {
        $limit = self::bulkActionLimit();
        
        return $limit !== null && $count > $limit;
    }

    /**
     * Check if a bulk action selection count should trigger a warning.
     *
     * @param  int  $count  Number of selected items
     * @return bool True if count exceeds warning threshold
     */
    public static function shouldWarnBulkAction(int $count): bool
    {
        return $count >= self::bulkActionWarningThreshold();
    }

    /**
     * Get the wire:model.live.debounce directive for search inputs.
     *
     * Returns the full Livewire directive string for use in Blade templates.
     * Example: "wire:model.live.debounce.300ms"
     */
    public static function searchDebounceDirective(): string
    {
        return sprintf('wire:model.live.debounce.%dms', self::searchDebounceMs());
    }

    /**
     * Get the wire:model.live.debounce directive for form inputs.
     *
     * Returns the full Livewire directive string for use in Blade templates.
     * Example: "wire:model.live.debounce.300ms"
     */
    public static function formDebounceDirective(): string
    {
        return sprintf('wire:model.live.debounce.%dms', self::formDebounceMs());
    }

    /**
     * Get the wire:model.live.debounce directive for textarea inputs.
     *
     * Returns the full Livewire directive string for use in Blade templates.
     * Example: "wire:model.live.debounce.400ms"
     */
    public static function textareaDebounceDirective(): string
    {
        return sprintf('wire:model.live.debounce.%dms', self::textareaDebounceMs());
    }
}

