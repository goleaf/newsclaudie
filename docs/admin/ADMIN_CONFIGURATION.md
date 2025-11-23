# Admin Interface Configuration Guide

This document describes all configuration options available for the admin Livewire CRUD interface.

## Table of Contents

- [Overview](#overview)
- [Configuration File](#configuration-file)
- [Pagination Settings](#pagination-settings)
- [Admin Interface Settings](#admin-interface-settings)
- [Environment Variables](#environment-variables)
- [Helper Class Usage](#helper-class-usage)
- [Performance Tuning](#performance-tuning)
- [Best Practices](#best-practices)

## Overview

The admin interface configuration is located in `config/interface.php` and controls behavior for:

- Pagination defaults and options
- Search and filter debounce timing
- Bulk action limits and warnings
- Optimistic UI updates
- Loading indicators
- URL query string persistence
- Inline editing behavior
- Confirmation dialogs

## Configuration File

All admin settings are in `config/interface.php` under the `admin` key:

```php
'admin' => [
    'search_debounce_ms' => 300,
    'form_debounce_ms' => 300,
    'textarea_debounce_ms' => 400,
    'bulk_action_limit' => 100,
    'bulk_action_warning_threshold' => 50,
    'optimistic_ui_enabled' => true,
    'loading_indicator_delay_ms' => 500,
    'persist_filters_in_url' => true,
    'inline_edit_autosave_ms' => null,
    'require_delete_confirmation' => true,
    'session_timeout_warning_minutes' => 5,
]
```

## Pagination Settings

### Per-Page Defaults

Default number of items per page for each section:

```php
'defaults' => [
    'admin' => 20,        // Admin tables (users, posts, etc.)
    'comments' => 10,     // Comment listings
    'posts' => 12,        // Public post listings
    'categories' => 15,   // Category listings
    'category_posts' => 12, // Posts within a category
]
```

### Per-Page Options

Available options users can select from:

```php
'options' => [
    'admin' => [10, 20, 50, 100],
    'comments' => [10, 25, 50],
    'posts' => [12, 18, 24, 36],
    'categories' => [12, 15, 24, 30],
    'category_posts' => [9, 12, 18, 24],
]
```

## Admin Interface Settings

### Search Debounce Timing

**Key:** `search_debounce_ms`  
**Default:** `300`  
**Type:** Integer (milliseconds)

Delay after user stops typing before triggering a search query.

**Recommendations:**
- **Fast networks:** 200-250ms
- **Standard use:** 300ms (default)
- **Slow networks or heavy queries:** 400-500ms

**Example:**
```php
'search_debounce_ms' => 300,
```

### Form Input Debounce Timing

**Key:** `form_debounce_ms`  
**Default:** `300`  
**Type:** Integer (milliseconds)

Delay for real-time validation on form inputs (name, slug, email).

**Recommendations:**
- **Text inputs:** 300ms (default)
- **Email validation:** 300-400ms
- **Slug generation:** 300ms

**Example:**
```php
'form_debounce_ms' => 300,
```

### Textarea Debounce Timing

**Key:** `textarea_debounce_ms`  
**Default:** `400`  
**Type:** Integer (milliseconds)

Slightly longer debounce for textarea fields (descriptions, content).

**Recommendations:**
- **Short descriptions:** 300-400ms
- **Long content:** 400-500ms (default)

**Example:**
```php
'textarea_debounce_ms' => 400,
```

### Bulk Action Limit

**Key:** `bulk_action_limit`  
**Default:** `100`  
**Type:** Integer or null

Maximum number of items that can be selected and processed in a single bulk action.

**Recommendations:**
- **Small datasets:** 100-200
- **Large datasets:** 50-100 (default)
- **No limit:** `null` (not recommended for production)

**Example:**
```php
'bulk_action_limit' => 100,
```

**Validation:** Requirements 7.1, 8.3

### Bulk Action Warning Threshold

**Key:** `bulk_action_warning_threshold`  
**Default:** `50`  
**Type:** Integer

Show a warning when bulk action selection exceeds this threshold.

**Recommendations:**
- Set to 50-70% of bulk_action_limit
- Helps users understand they're performing a large operation

**Example:**
```php
'bulk_action_warning_threshold' => 50,
```

### Optimistic UI Updates

**Key:** `optimistic_ui_enabled`  
**Default:** `true`  
**Type:** Boolean

Enable optimistic UI updates for admin actions. UI updates immediately before server confirmation.

**Recommendations:**
- **Production:** `true` (better UX)
- **Development/Testing:** Can be disabled for debugging

**Example:**
```php
'optimistic_ui_enabled' => true,
```

**Validation:** Requirements 12.1, 12.2, 12.3

### Loading Indicator Delay

**Key:** `loading_indicator_delay_ms`  
**Default:** `500`  
**Type:** Integer (milliseconds)

Delay before showing loading indicators. Prevents flashing spinners for fast actions.

**Recommendations:**
- **Fast actions:** 500ms (default, matches Requirement 12.4)
- **Slow actions:** 300-400ms (show sooner)

**Example:**
```php
'loading_indicator_delay_ms' => 500,
```

**Validation:** Requirement 12.4

### Query String Persistence

**Key:** `persist_filters_in_url`  
**Default:** `true`  
**Type:** Boolean

Enable URL query string persistence for filters, search, and sort. Allows bookmarking and sharing filtered views.

**Recommendations:**
- **Production:** `true` (better UX)
- **Privacy concerns:** `false` (prevents URL sharing with filters)

**Example:**
```php
'persist_filters_in_url' => true,
```

**Validation:** Requirements 7.5, 9.4

### Inline Edit Auto-save

**Key:** `inline_edit_autosave_ms`  
**Default:** `null`  
**Type:** Integer (milliseconds) or null

Automatically save inline edits after this many milliseconds of inactivity.

**Recommendations:**
- **Default:** `null` (require explicit save for data safety)
- **Auto-save enabled:** 2000-3000ms

**Example:**
```php
'inline_edit_autosave_ms' => null, // Disabled
// or
'inline_edit_autosave_ms' => 2000, // 2 seconds
```

### Delete Confirmation

**Key:** `require_delete_confirmation`  
**Default:** `true`  
**Type:** Boolean

Require confirmation dialogs for destructive actions (delete, bulk delete).

**Recommendations:**
- **Production:** `true` (data safety)
- **Development/Testing:** Can be disabled for faster testing

**Example:**
```php
'require_delete_confirmation' => true,
```

### Session Timeout Warning

**Key:** `session_timeout_warning_minutes`  
**Default:** `5`  
**Type:** Integer (minutes) or null

Minutes before session timeout to show a warning.

**Recommendations:**
- **Standard:** 5 minutes (default)
- **Disabled:** `null`

**Example:**
```php
'session_timeout_warning_minutes' => 5,
```

## Environment Variables

All configuration values can be overridden via environment variables:

```env
# Search and form debounce timing
ADMIN_SEARCH_DEBOUNCE_MS=300
ADMIN_FORM_DEBOUNCE_MS=300
ADMIN_TEXTAREA_DEBOUNCE_MS=400

# Bulk actions
ADMIN_BULK_ACTION_LIMIT=100
ADMIN_BULK_WARNING_THRESHOLD=50

# UI behavior
ADMIN_OPTIMISTIC_UI=true
ADMIN_LOADING_DELAY_MS=500
ADMIN_PERSIST_FILTERS=true

# Inline editing
ADMIN_INLINE_AUTOSAVE_MS=null

# Confirmations and warnings
ADMIN_REQUIRE_DELETE_CONFIRM=true
ADMIN_SESSION_WARNING_MIN=5
```

## Helper Class Usage

The `App\Support\AdminConfig` helper class provides type-safe access to configuration values:

### Basic Usage

```php
use App\Support\AdminConfig;

// Get debounce timings
$searchDebounce = AdminConfig::searchDebounceMs(); // 300
$formDebounce = AdminConfig::formDebounceMs(); // 300
$textareaDebounce = AdminConfig::textareaDebounceMs(); // 400

// Get bulk action settings
$limit = AdminConfig::bulkActionLimit(); // 100 or null
$threshold = AdminConfig::bulkActionWarningThreshold(); // 50

// Check UI settings
$optimisticUi = AdminConfig::optimisticUiEnabled(); // true/false
$loadingDelay = AdminConfig::loadingIndicatorDelayMs(); // 500
```

### Validation Helpers

```php
use App\Support\AdminConfig;

// Check if selection exceeds limit
$selectedCount = count($this->selected);
if (AdminConfig::exceedsBulkActionLimit($selectedCount)) {
    session()->flash('error', 'Selection exceeds maximum limit');
    return;
}

// Check if warning should be shown
if (AdminConfig::shouldWarnBulkAction($selectedCount)) {
    session()->flash('warning', 'You are about to process many items');
}
```

### Blade Directive Helpers

```php
use App\Support\AdminConfig;

// Get full wire:model directive strings
$searchDirective = AdminConfig::searchDebounceDirective();
// Returns: "wire:model.live.debounce.300ms"

$formDirective = AdminConfig::formDebounceDirective();
// Returns: "wire:model.live.debounce.300ms"

$textareaDirective = AdminConfig::textareaDebounceDirective();
// Returns: "wire:model.live.debounce.400ms"
```

### In Livewire Components

```php
use App\Support\AdminConfig;
use Livewire\Component;

class PostsIndex extends Component
{
    public function bulkDelete(): void
    {
        // Validate against limit
        if (AdminConfig::exceedsBulkActionLimit($this->selectedCount)) {
            $this->addError('selected', 'Too many items selected');
            return;
        }

        // Show warning if needed
        if (AdminConfig::shouldWarnBulkAction($this->selectedCount)) {
            $this->dispatch('show-bulk-warning', count: $this->selectedCount);
        }

        // Proceed with bulk delete...
    }
}
```

### In Blade Templates

```blade
{{-- Using helper in Blade --}}
<input
    type="search"
    wire:model.live.debounce.{{ \App\Support\AdminConfig::searchDebounceMs() }}ms="search"
    placeholder="Search..."
/>

{{-- Or use the directive helper --}}
@php
    $directive = \App\Support\AdminConfig::searchDebounceDirective();
@endphp
<input
    type="search"
    {{ $directive }}="search"
    placeholder="Search..."
/>
```

## Performance Tuning

### Network Speed Considerations

**Fast Networks (< 50ms latency):**
```php
'search_debounce_ms' => 200,
'form_debounce_ms' => 200,
'textarea_debounce_ms' => 300,
```

**Standard Networks (50-150ms latency):**
```php
'search_debounce_ms' => 300, // Default
'form_debounce_ms' => 300,
'textarea_debounce_ms' => 400,
```

**Slow Networks (> 150ms latency):**
```php
'search_debounce_ms' => 500,
'form_debounce_ms' => 400,
'textarea_debounce_ms' => 500,
```

### Database Size Considerations

**Small Datasets (< 10,000 records):**
```php
'bulk_action_limit' => 200,
'bulk_action_warning_threshold' => 100,
```

**Medium Datasets (10,000 - 100,000 records):**
```php
'bulk_action_limit' => 100, // Default
'bulk_action_warning_threshold' => 50,
```

**Large Datasets (> 100,000 records):**
```php
'bulk_action_limit' => 50,
'bulk_action_warning_threshold' => 25,
```

### Server Resources

**High-Performance Servers:**
- Lower debounce times (200-300ms)
- Higher bulk action limits (150-200)
- Enable optimistic UI

**Standard Servers:**
- Default settings work well
- Monitor for timeout issues with bulk actions

**Resource-Constrained Servers:**
- Higher debounce times (400-500ms)
- Lower bulk action limits (50-75)
- Consider disabling optimistic UI for complex operations

## Best Practices

### Development Environment

```php
'admin' => [
    'search_debounce_ms' => 100,  // Faster feedback
    'form_debounce_ms' => 100,
    'bulk_action_limit' => 10,    // Easier testing
    'require_delete_confirmation' => false, // Faster testing
    'optimistic_ui_enabled' => true, // Test both paths
]
```

### Production Environment

```php
'admin' => [
    'search_debounce_ms' => 300,  // Balanced
    'form_debounce_ms' => 300,
    'bulk_action_limit' => 100,   // Safe limit
    'require_delete_confirmation' => true, // Data safety
    'optimistic_ui_enabled' => true, // Better UX
]
```

### Testing Environment

```php
'admin' => [
    'search_debounce_ms' => 0,    // No delay for tests
    'form_debounce_ms' => 0,
    'bulk_action_limit' => 1000,  // No limit for tests
    'require_delete_confirmation' => false, // Automated tests
    'optimistic_ui_enabled' => false, // Predictable behavior
]
```

### Security Considerations

1. **Always enable delete confirmation in production:**
   ```php
   'require_delete_confirmation' => true,
   ```

2. **Set reasonable bulk action limits:**
   ```php
   'bulk_action_limit' => 100, // Prevents abuse
   ```

3. **Consider disabling URL persistence for sensitive data:**
   ```php
   'persist_filters_in_url' => false, // If filters contain sensitive info
   ```

### Monitoring and Optimization

1. **Monitor search query performance:**
   - If searches are slow, increase `search_debounce_ms`
   - Add database indexes for searchable columns

2. **Track bulk action completion rates:**
   - If many timeouts, reduce `bulk_action_limit`
   - Optimize bulk operation queries

3. **Monitor user feedback:**
   - If UI feels sluggish, reduce debounce times
   - If too many requests, increase debounce times

4. **A/B test optimistic UI:**
   - Compare user satisfaction with/without
   - Monitor error rates and reversion frequency

## Related Documentation

- [Admin Accessibility Guide](ADMIN_ACCESSIBILITY.md)
- [Optimistic UI Implementation](../optimistic-ui/OPTIMISTIC_UI.md)
- [Property Testing Guide](../../tests/PROPERTY_TESTING.md)

## Requirements Validation

This configuration supports the following requirements:

- **Requirement 7.1:** Search debounce timing for real-time filtering
- **Requirement 8.3:** Bulk action limits and processing
- **Requirement 9.4:** URL query string persistence
- **Requirement 12.1-12.5:** Optimistic UI updates and loading indicators

